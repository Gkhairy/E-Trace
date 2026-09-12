// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

// =============================================================
//  PaymentGateway v3 — ESCROW multi-penjual (TLKM-locked + fee + arbiter)
//  -------------------------------------------------------------
//  Perubahan dari v2 (sesuai keputusan audit):
//   1. TOKEN DIKUNCI ke TLKM (immutable, di-set saat deploy). Fungsi TIDAK lagi
//      menerima alamat token dari pemanggil -> hilangkan risiko dibayar token lain.
//   2. PLATFORM FEE (mis. 100 bps = 1%): dipotong HANYA saat dana DILEPAS ke penjual
//      (confirmItem / arbiterRelease). TIDAK dipotong saat bayar. Refund = PENUH,
//      tanpa fee. Fee tercatat di event -> transparan di block explorer.
//   3. ARBITER (pengawas platform): bisa memutus item yang bermasalah -> lepas ke
//      penjual (minus fee) atau refund ke pembeli. Semua aksi ter-emit event.
//   4. Buyer tetap bisa confirmItem (terima) atau refundItem (setelah 3 hari), dan
//      bisa disputeItem (minta pengawas menengahi).
//
//  Escrow TETAP TERPISAH PER ITEM (key = keccak256(orderId, index)) -> konfirmasi
//  1 item tidak menyentuh dana item lain.
//
//  Konfirmasi/finality (1 -> 6 conf -> finalized) ditangani OFF-CHAIN oleh indexer,
//  bukan di kontrak ini.
//
//  Deploy (Remix, Solidity 0.8.20, BNB Smart Chain Testnet / chainId 97):
//   constructor(tlkm, feeRecipient, feeBps, arbiter)
//     - tlkm         : alamat kontrak TLKM
//     - feeRecipient : wallet platform penerima fee
//     - feeBps       : 100 = 1% (maks 1000 = 10%)
//     - arbiter      : wallet pengawas platform (boleh sama dg feeRecipient)
// =============================================================

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

contract PaymentGatewayV3 is ReentrancyGuard {
    using SafeERC20 for IERC20;

    enum Status { None, Paid, Completed, Refunded, Disputed }

    IERC20  public immutable token;        // TLKM — dikunci
    address public immutable feeRecipient; // wallet platform penerima fee
    uint256 public immutable feeBps;       // 100 = 1%
    address public owner;                  // deployer (bisa ganti arbiter)
    address public arbiter;                // pengawas platform (penengah sengketa)

    uint256 public constant REFUND_TIMEOUT = 3 days;
    uint256 public constant MAX_FEE_BPS    = 1000; // batas aman 10%
    uint256 public constant BPS_DENOM      = 10000;

    struct Item {
        address buyer;
        address seller;
        uint256 amount;     // nominal penuh yang ditahan (satuan terkecil TLKM)
        string  productId;
        uint256 createdAt;
        Status  status;
    }

    // key = keccak256(abi.encode(orderId, index)) -> Item
    mapping(bytes32 => Item) public items;
    // orderId -> jumlah item (0 = orderId belum dipakai)
    mapping(string => uint256) public itemCount;

    // ---------- EVENTS (untuk indexer & transparansi publik) ----------
    event PurchaseItem(
        string indexed orderId, uint256 index, address indexed buyer,
        address indexed seller, uint256 amount, string productId, uint256 timestamp
    );
    // sellerAmount = amount - fee. resolvedByArbiter = true jika diputus pengawas.
    event ItemCompleted(
        string indexed orderId, uint256 index, address indexed seller,
        uint256 sellerAmount, uint256 fee, bool resolvedByArbiter
    );
    event ItemRefunded(
        string indexed orderId, uint256 index, address indexed buyer,
        uint256 amount, bool resolvedByArbiter
    );
    event ItemDisputed(string indexed orderId, uint256 index, address indexed buyer, uint256 timestamp);
    event ArbiterChanged(address indexed oldArbiter, address indexed newArbiter);

    modifier onlyOwner()   { require(msg.sender == owner,   "Bukan owner");   _; }
    modifier onlyArbiter() { require(msg.sender == arbiter, "Bukan arbiter"); _; }

    constructor(address tlkm, address feeRecipient_, uint256 feeBps_, address arbiter_) {
        require(tlkm != address(0), "TLKM tidak valid");
        require(feeRecipient_ != address(0), "feeRecipient tidak valid");
        require(arbiter_ != address(0), "arbiter tidak valid");
        require(feeBps_ <= MAX_FEE_BPS, "feeBps terlalu besar");
        token        = IERC20(tlkm);
        feeRecipient = feeRecipient_;
        feeBps       = feeBps_;
        arbiter      = arbiter_;
        owner        = msg.sender;
    }

    function _itemKey(string memory orderId, uint256 index) internal pure returns (bytes32) {
        return keccak256(abi.encode(orderId, index));
    }

    // ============================================================
    //  BAYAR CART -> tarik SEKALI total TLKM, simpan tiap item terpisah
    // ============================================================
    function payCart(
        address[] calldata sellers,
        uint256[] calldata amounts,
        string[]  calldata productIds,
        string    calldata orderId
    ) external nonReentrant {
        uint256 n = sellers.length;
        require(n > 0, "Cart kosong");
        require(n == amounts.length && n == productIds.length, "Panjang array tidak sama");
        require(itemCount[orderId] == 0, "orderId sudah dipakai");

        uint256 total = 0;
        for (uint256 i = 0; i < n; i++) {
            require(amounts[i] > 0, "Jumlah item harus > 0");
            require(sellers[i] != address(0), "Seller tidak valid");
            total += amounts[i];
        }

        // Tarik SEKALI total dari pembeli (wajib approve TLKM dulu).
        token.safeTransferFrom(msg.sender, address(this), total);

        itemCount[orderId] = n;
        for (uint256 i = 0; i < n; i++) {
            _recordItem(sellers[i], amounts[i], productIds[i], orderId, i);
        }
    }

    // Simpan 1 item + emit (frame stack terpisah -> hindari "stack too deep").
    function _recordItem(
        address seller, uint256 amount, string calldata productId,
        string calldata orderId, uint256 index
    ) internal {
        items[_itemKey(orderId, index)] = Item({
            buyer: msg.sender, seller: seller, amount: amount,
            productId: productId, createdAt: block.timestamp, status: Status.Paid
        });
        emit PurchaseItem(orderId, index, msg.sender, seller, amount, productId, block.timestamp);
    }

    // ============================================================
    //  PEMBELI: konfirmasi terima / refund (>3 hari) / ajukan sengketa
    // ============================================================
    function confirmItem(string calldata orderId, uint256 index) external nonReentrant {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid, "Item tidak dalam status Paid");
        require(msg.sender == it.buyer, "Hanya pembeli yang boleh konfirmasi");
        _release(it, orderId, index, false);
    }

    function refundItem(string calldata orderId, uint256 index) external nonReentrant {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid, "Item tidak dalam status Paid");
        require(msg.sender == it.buyer, "Hanya pembeli yang boleh refund");
        require(block.timestamp >= it.createdAt + REFUND_TIMEOUT, "Belum lewat batas waktu refund");
        _refund(it, orderId, index, false);
    }

    function disputeItem(string calldata orderId, uint256 index) external {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid, "Item tidak dalam status Paid");
        require(msg.sender == it.buyer, "Hanya pembeli yang boleh sengketa");
        it.status = Status.Disputed;
        emit ItemDisputed(orderId, index, it.buyer, block.timestamp);
    }

    // ============================================================
    //  ARBITER (pengawas): putuskan item Paid/Disputed
    // ============================================================
    function arbiterRelease(string calldata orderId, uint256 index) external nonReentrant onlyArbiter {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid || it.status == Status.Disputed, "Status tidak bisa dilepas");
        _release(it, orderId, index, true);
    }

    function arbiterRefund(string calldata orderId, uint256 index) external nonReentrant onlyArbiter {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid || it.status == Status.Disputed, "Status tidak bisa direfund");
        _refund(it, orderId, index, true);
    }

    // ---------- internal: lepas ke penjual (potong fee) / refund penuh ----------
    function _release(Item storage it, string calldata orderId, uint256 index, bool byArbiter) internal {
        it.status = Status.Completed;
        uint256 fee = (it.amount * feeBps) / BPS_DENOM;   // fee HANYA saat rilis
        uint256 sellerAmount = it.amount - fee;
        if (fee > 0) token.safeTransfer(feeRecipient, fee);
        token.safeTransfer(it.seller, sellerAmount);
        emit ItemCompleted(orderId, index, it.seller, sellerAmount, fee, byArbiter);
    }

    function _refund(Item storage it, string calldata orderId, uint256 index, bool byArbiter) internal {
        it.status = Status.Refunded;
        token.safeTransfer(it.buyer, it.amount);          // refund PENUH, tanpa fee
        emit ItemRefunded(orderId, index, it.buyer, it.amount, byArbiter);
    }

    // ---------- admin & views ----------
    function setArbiter(address newArbiter) external onlyOwner {
        require(newArbiter != address(0), "arbiter tidak valid");
        emit ArbiterChanged(arbiter, newArbiter);
        arbiter = newArbiter;
    }

    function getItem(string calldata orderId, uint256 index) external view returns (Item memory) {
        return items[_itemKey(orderId, index)];
    }
}
