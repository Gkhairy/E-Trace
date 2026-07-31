// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

// =============================================================
//  PaymentGateway (ESCROW) — v2 dengan CHECKOUT MULTI-PENJUAL
//  -------------------------------------------------------------
//  RINGKASAN:
//   - Fungsi LAMA (payWithToken/confirmReceived/refund) TETAP ADA supaya
//     order lama & fitur beli-satuan tidak breaking.
//   - Fungsi BARU (payCart/confirmItem/refundItem) untuk 1 transaksi berisi
//     banyak item dari BANYAK penjual, dengan ESCROW TERPISAH PER ITEM.
//
//  INTI (yang diminta): konfirmasi/refund SATU item hanya melepas dana item
//  itu ke penjualnya; item lain (penjual lain) TIDAK terpengaruh.
//
//  Alur cart:
//   1. Pembeli approve TOTAL token TLKM ke kontrak ini (frontend).
//   2. Pembeli panggil payCart(...) -> kontrak tarik SEKALI total, lalu simpan
//      tiap item sebagai sub-escrow terpisah (key = keccak256(orderId,index)).
//   3. Untuk tiap item: pembeli confirmItem(orderId,index) -> dana item lepas
//      ke penjual item itu; atau refundItem(...) setelah timeout.
//
//  Semua langkah emit EVENT -> bisa diaudit publik di Etherscan Sepolia.
//
//  Deploy: Solidity 0.8.20 di Remix (Injected Provider - MetaMask, Sepolia).
// =============================================================

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

contract PaymentGateway is ReentrancyGuard {
    using SafeERC20 for IERC20;

    // Status sebuah order/item.
    enum Status { None, Paid, Completed, Refunded }

    // Batas waktu sebelum pembeli boleh refund kalau belum konfirmasi. 3 hari.
    uint256 public constant REFUND_TIMEOUT = 3 days;

    // =========================================================
    //  BAGIAN LAMA (single order) — dipertahankan (non-breaking)
    // =========================================================
    struct Order {
        address token;
        address buyer;
        address seller;
        uint256 amount;
        string  productId;
        uint256 createdAt;
        Status  status;
    }
    mapping(string => Order) public orders; // orderId -> Order (single)

    event Purchase(
        string indexed orderId, address indexed buyer, address indexed seller,
        address token, uint256 amount, string productId, uint256 timestamp
    );
    event Completed(string indexed orderId, address seller, uint256 amount);
    event Refunded(string indexed orderId, address buyer, uint256 amount);

    function payWithToken(
        address token, address seller, uint256 amount,
        string calldata productId, string calldata orderId
    ) external nonReentrant {
        require(amount > 0, "Jumlah harus > 0");
        require(seller != address(0), "Seller tidak valid");
        require(orders[orderId].status == Status.None, "orderId sudah dipakai");

        IERC20(token).safeTransferFrom(msg.sender, address(this), amount);

        orders[orderId] = Order({
            token: token, buyer: msg.sender, seller: seller, amount: amount,
            productId: productId, createdAt: block.timestamp, status: Status.Paid
        });
        emit Purchase(orderId, msg.sender, seller, token, amount, productId, block.timestamp);
    }

    function confirmReceived(string calldata orderId) external nonReentrant {
        Order storage o = orders[orderId];
        require(o.status == Status.Paid, "Order tidak dalam status Paid");
        require(msg.sender == o.buyer, "Hanya pembeli yang boleh konfirmasi");
        o.status = Status.Completed;
        IERC20(o.token).safeTransfer(o.seller, o.amount);
        emit Completed(orderId, o.seller, o.amount);
    }

    function refund(string calldata orderId) external nonReentrant {
        Order storage o = orders[orderId];
        require(o.status == Status.Paid, "Order tidak dalam status Paid");
        require(msg.sender == o.buyer, "Hanya pembeli yang boleh refund");
        require(block.timestamp >= o.createdAt + REFUND_TIMEOUT, "Belum lewat batas waktu refund");
        o.status = Status.Refunded;
        IERC20(o.token).safeTransfer(o.buyer, o.amount);
        emit Refunded(orderId, o.buyer, o.amount);
    }

    // =========================================================
    //  BAGIAN BARU (multi-penjual) — escrow TERPISAH PER ITEM
    // =========================================================
    struct Item {
        address token;
        address buyer;
        address seller;
        uint256 amount;
        string  productId;
        uint256 createdAt;
        Status  status;
    }

    // key = keccak256(abi.encode(orderId, index)) -> Item
    mapping(bytes32 => Item) public items;
    // orderId -> jumlah item (0 artinya orderId belum dipakai untuk cart)
    mapping(string => uint256) public itemCount;

    event PurchaseItem(
        string indexed orderId, uint256 index, address indexed buyer,
        address indexed seller, address token, uint256 amount,
        string productId, uint256 timestamp
    );
    event ItemCompleted(string indexed orderId, uint256 index, address seller, uint256 amount);
    event ItemRefunded(string indexed orderId, uint256 index, address buyer, uint256 amount);

    // Kunci unik sub-escrow per item.
    function _itemKey(string memory orderId, uint256 index) internal pure returns (bytes32) {
        return keccak256(abi.encode(orderId, index));
    }

    // ---------------------------------------------------------
    //  1) BAYAR CART -> tarik SEKALI total, simpan tiap item terpisah
    // ---------------------------------------------------------
    function payCart(
        address token,
        address[] calldata sellers,
        uint256[] calldata amounts,
        string[] calldata productIds,
        string calldata orderId
    ) external nonReentrant {
        uint256 n = sellers.length;
        require(n > 0, "Cart kosong");
        require(n == amounts.length && n == productIds.length, "Panjang array tidak sama");
        require(itemCount[orderId] == 0, "orderId sudah dipakai");

        // Hitung total & validasi tiap item.
        uint256 total = 0;
        for (uint256 i = 0; i < n; i++) {
            require(amounts[i] > 0, "Jumlah item harus > 0");
            require(sellers[i] != address(0), "Seller tidak valid");
            total += amounts[i];
        }

        // Tarik SEKALI total dari pembeli (pembeli wajib approve total dulu).
        IERC20(token).safeTransferFrom(msg.sender, address(this), total);

        // Simpan tiap item sebagai sub-escrow terpisah.
        // Catatan: penyimpanan + emit dipisah ke _recordItem() supaya tidak
        // kena "stack too deep" (event punya banyak parameter).
        itemCount[orderId] = n;
        for (uint256 i = 0; i < n; i++) {
            _recordItem(token, sellers[i], amounts[i], productIds[i], orderId, i);
        }
    }

    // Simpan 1 item ke escrow + emit event (frame stack terpisah).
    function _recordItem(
        address token,
        address seller,
        uint256 amount,
        string calldata productId,
        string calldata orderId,
        uint256 index
    ) internal {
        items[_itemKey(orderId, index)] = Item({
            token: token,
            buyer: msg.sender,
            seller: seller,
            amount: amount,
            productId: productId,
            createdAt: block.timestamp,
            status: Status.Paid
        });
        emit PurchaseItem(orderId, index, msg.sender, seller, token, amount, productId, block.timestamp);
    }

    // ---------------------------------------------------------
    //  2) KONFIRMASI SATU ITEM -> dana item itu lepas ke penjualnya
    //     (item lain TIDAK terpengaruh)
    // ---------------------------------------------------------
    function confirmItem(string calldata orderId, uint256 index) external nonReentrant {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid, "Item tidak dalam status Paid");
        require(msg.sender == it.buyer, "Hanya pembeli yang boleh konfirmasi");

        it.status = Status.Completed;
        IERC20(it.token).safeTransfer(it.seller, it.amount);
        emit ItemCompleted(orderId, index, it.seller, it.amount);
    }

    // ---------------------------------------------------------
    //  3) REFUND SATU ITEM -> kembali ke pembeli (setelah timeout)
    // ---------------------------------------------------------
    function refundItem(string calldata orderId, uint256 index) external nonReentrant {
        Item storage it = items[_itemKey(orderId, index)];
        require(it.status == Status.Paid, "Item tidak dalam status Paid");
        require(msg.sender == it.buyer, "Hanya pembeli yang boleh refund");
        require(block.timestamp >= it.createdAt + REFUND_TIMEOUT, "Belum lewat batas waktu refund");

        it.status = Status.Refunded;
        IERC20(it.token).safeTransfer(it.buyer, it.amount);
        emit ItemRefunded(orderId, index, it.buyer, it.amount);
    }

    // ---------- Helper baca (dipakai frontend / audit) ----------
    function getItem(string calldata orderId, uint256 index) external view returns (Item memory) {
        return items[_itemKey(orderId, index)];
    }

    function getOrder(string calldata orderId) external view returns (Order memory) {
        return orders[orderId];
    }
}
