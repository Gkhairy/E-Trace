// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

// =====================================================================
//  TlkmPaylater — kredit berjaminan on-chain (DEMO testnet, UNAUDITED)
//  ---------------------------------------------------------------------
//  Agunan   : native tBNB (BNB Smart Chain Testnet).
//  Pinjaman : token TLKM (BEP-20 / ERC-20, 18 desimal).
//  Platform (owner) MENGISI kontrak dengan likuiditas TLKM (transfer TLKM ke
//  alamat kontrak) sebelum user bisa borrow.
//
//  ALUR: user depositCollateral(tBNB) -> dapat creditLimit(TLKM) -> borrow(TLKM)
//        -> belanja sekarang -> repay(TLKM) sebelum jatuh tempo -> withdrawCollateral.
//        Bila lewat tenggat & masih ada utang, owner boleh seize() agunan.
//
//  CATATAN PENTING: rasio agunan (`rate`) adalah PARAMETER DEMO, bukan model
//  risiko nyata. Tanpa bunga, tanpa oracle harga, tanpa likuidasi pasar.
//  Hanya untuk demo lomba di testnet. JANGAN dipakai dengan dana sungguhan.
//  Deploy: Remix, Solidity 0.8.20, BNB Smart Chain Testnet (chainId 97).
//    constructor(tlkm, rate, duePeriod) — DEMO: rate=1_000_000e18, duePeriod=604800 (7 hari)
// =====================================================================

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

contract TlkmPaylater is Ownable, ReentrancyGuard {
    using SafeERC20 for IERC20;

    IERC20  public immutable tlkm;   // alamat token TLKM
    uint256 public rate;             // TLKM (18 des) per 1 tBNB (1e18 wei). DEMO: 1_000_000e18
    uint256 public duePeriod;        // detik, mis. 604800 (7 hari)

    struct Position {
        uint256 collateral; // tBNB terkunci (wei)
        uint256 debt;       // TLKM terutang (wei)
        uint256 dueDate;    // timestamp jatuh tempo utang terakhir
    }
    mapping(address => Position) public positions;

    event Deposited(address indexed user, uint256 amount);
    event Borrowed(address indexed user, uint256 amount, uint256 dueDate);
    event Repaid(address indexed user, uint256 amount);
    event Withdrawn(address indexed user, uint256 amount);
    event Seized(address indexed user, uint256 collateral);
    event RateChanged(uint256 rate);
    event DuePeriodChanged(uint256 duePeriod);

    constructor(address _tlkm, uint256 _rate, uint256 _duePeriod) Ownable(msg.sender) {
        require(_tlkm != address(0), "TlkmPaylater: alamat TLKM nol");
        require(_rate > 0, "TlkmPaylater: rate harus > 0");
        tlkm      = IERC20(_tlkm);
        rate      = _rate;
        duePeriod = _duePeriod;
    }

    /// @notice Limit kredit user (TLKM wei) = collateral(tBNB wei) * rate / 1e18.
    function creditLimit(address u) public view returns (uint256) {
        return positions[u].collateral * rate / 1e18;
    }

    /// @notice Tambah agunan tBNB (native) ke posisi pemanggil.
    function depositCollateral() external payable nonReentrant {
        require(msg.value > 0, "TlkmPaylater: nilai 0");
        positions[msg.sender].collateral += msg.value;
        emit Deposited(msg.sender, msg.value);
    }

    /// @notice Pinjam TLKM dalam batas limit; kontrak transfer TLKM ke pemanggil.
    function borrow(uint256 amount) external nonReentrant {
        require(amount > 0, "TlkmPaylater: amount 0");
        Position storage p = positions[msg.sender];
        require(p.debt + amount <= creditLimit(msg.sender), "TlkmPaylater: melebihi limit kredit");
        require(tlkm.balanceOf(address(this)) >= amount, "TlkmPaylater: likuiditas TLKM kontrak kurang");
        // effects sebelum interaction (CEI)
        p.debt   += amount;
        p.dueDate = block.timestamp + duePeriod;
        // interaction
        tlkm.safeTransfer(msg.sender, amount);
        emit Borrowed(msg.sender, amount, p.dueDate);
    }

    /// @notice Lunasi sebagian/seluruh utang TLKM (butuh approve TLKM ke kontrak dulu).
    function repay(uint256 amount) external nonReentrant {
        require(amount > 0, "TlkmPaylater: amount 0");
        Position storage p = positions[msg.sender];
        require(p.debt > 0, "TlkmPaylater: tidak ada utang");
        uint256 pay = amount > p.debt ? p.debt : amount; // clamp ke sisa utang
        p.debt -= pay;                                    // effect
        tlkm.safeTransferFrom(msg.sender, address(this), pay); // interaction (pull)
        emit Repaid(msg.sender, pay);
    }

    /// @notice Tarik agunan tBNB — hanya jika utang sudah 0.
    function withdrawCollateral(uint256 amount) external nonReentrant {
        Position storage p = positions[msg.sender];
        require(p.debt == 0, "TlkmPaylater: lunasi utang dulu");
        require(amount > 0 && amount <= p.collateral, "TlkmPaylater: jumlah agunan tidak valid");
        p.collateral -= amount; // effect sebelum interaction
        (bool ok, ) = payable(msg.sender).call{value: amount}("");
        require(ok, "TlkmPaylater: transfer tBNB gagal");
        emit Withdrawn(msg.sender, amount);
    }

    /// @notice Owner menyita agunan bila utang lewat jatuh tempo (default/wanprestasi).
    function seize(address user) external onlyOwner nonReentrant {
        Position storage p = positions[user];
        require(p.debt > 0, "TlkmPaylater: user tak punya utang");
        require(block.timestamp > p.dueDate, "TlkmPaylater: belum jatuh tempo");
        uint256 col = p.collateral;
        p.debt = 0;         // effect
        p.collateral = 0;   // effect
        if (col > 0) {
            (bool ok, ) = payable(owner()).call{value: col}("");
            require(ok, "TlkmPaylater: transfer agunan gagal");
        }
        emit Seized(user, col);
    }

    /// @notice Ringkasan posisi user untuk verifikasi backend (chain sbg kebenaran).
    function positionOf(address u)
        external
        view
        returns (uint256 collateral, uint256 debt, uint256 dueDate, uint256 limit)
    {
        Position storage p = positions[u];
        return (p.collateral, p.debt, p.dueDate, creditLimit(u));
    }

    /// @notice Likuiditas TLKM yang tersedia di kontrak (untuk dipinjamkan).
    function ownerFundInfo() external view returns (uint256) {
        return tlkm.balanceOf(address(this));
    }

    // ===== Owner setters (parameter demo) =====
    function setRate(uint256 _rate) external onlyOwner {
        require(_rate > 0, "TlkmPaylater: rate harus > 0");
        rate = _rate;
        emit RateChanged(_rate);
    }

    function setDuePeriod(uint256 _duePeriod) external onlyOwner {
        duePeriod = _duePeriod;
        emit DuePeriodChanged(_duePeriod);
    }

    /// @notice Owner tarik kembali likuiditas TLKM yang belum terpinjam (opsional).
    function ownerWithdrawTlkm(uint256 amount) external onlyOwner {
        tlkm.safeTransfer(owner(), amount);
    }
}
