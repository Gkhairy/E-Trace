// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

// =====================================================================
//  TlkmPaylater — kredit berjaminan on-chain DENGAN BUNGA (DEMO, UNAUDITED)
//  ---------------------------------------------------------------------
//  Agunan   : native tBNB (BNB Smart Chain Testnet).
//  Pinjaman : token TLKM (BEP-20 / ERC-20, 18 desimal).
//  Bunga    : FLAT, dikunci saat borrow (mis. 3% = 300 bps).
//  Platform (owner) MENGISI kontrak dengan likuiditas TLKM sebelum user borrow.
//
//  ALUR: depositCollateral(tBNB) -> creditLimit(TLKM) -> borrow(TLKM) (kewajiban =
//        pokok + bunga, dikunci ke dueAmount) -> repay(TLKM) mengurangi dueAmount ->
//        withdrawCollateral saat dueAmount==0. Bunga (dueAmount - principal) tertahan
//        di kontrak sebagai cadangan platform. Owner boleh seize() bila lewat tempo.
//
//  CATATAN PENTING: rasio agunan (`rate`) DAN bunga (`interestBps`) adalah PARAMETER
//  DEMO, bukan model risiko nyata. Tanpa oracle harga, tanpa likuidasi pasar. Hanya
//  untuk demo lomba di testnet. JANGAN dipakai dengan dana sungguhan.
//  Deploy: Remix, Solidity 0.8.20, BNB Smart Chain Testnet (chainId 97).
//    constructor(tlkm, rate, interestBps, duePeriod)
//    DEMO: rate=1_000_000e18, interestBps=300, duePeriod=604800 (7 hari)
// =====================================================================

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

contract TlkmPaylater is Ownable, ReentrancyGuard {
    using SafeERC20 for IERC20;

    IERC20  public immutable tlkm;   // alamat token TLKM
    uint256 public rate;             // TLKM (18 des) per 1 tBNB (1e18 wei). DEMO: 1_000_000e18
    uint256 public interestBps;      // bunga FLAT per pinjaman (basis poin). DEMO: 300 = 3%
    uint256 public duePeriod;        // detik, mis. 604800 (7 hari)

    struct Position {
        uint256 collateral; // tBNB terkunci (wei)
        uint256 principal;  // total pokok terpinjam yang masih berjalan (wei TLKM)
        uint256 dueAmount;  // kewajiban tersisa = pokok + bunga (wei TLKM)
        uint256 dueDate;    // timestamp jatuh tempo utang terakhir
    }
    mapping(address => Position) public positions;

    event Deposited(address indexed user, uint256 amount);
    event Borrowed(address indexed user, uint256 amount, uint256 dueAmount, uint256 dueDate);
    event Repaid(address indexed user, uint256 amount, uint256 remainingDue);
    event Withdrawn(address indexed user, uint256 amount);
    event Seized(address indexed user, uint256 collateral);
    event RateChanged(uint256 rate);
    event InterestChanged(uint256 interestBps);
    event DuePeriodChanged(uint256 duePeriod);

    constructor(address _tlkm, uint256 _rate, uint256 _interestBps, uint256 _duePeriod) Ownable(msg.sender) {
        require(_tlkm != address(0), "TlkmPaylater: alamat TLKM nol");
        require(_rate > 0, "TlkmPaylater: rate harus > 0");
        require(_interestBps <= 5000, "TlkmPaylater: bunga maks 50%");
        tlkm        = IERC20(_tlkm);
        rate        = _rate;
        interestBps = _interestBps;
        duePeriod   = _duePeriod;
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

    /// @notice Pinjam TLKM; kewajiban (pokok + bunga flat) dikunci ke dueAmount.
    function borrow(uint256 amount) external nonReentrant {
        require(amount > 0, "TlkmPaylater: amount 0");
        Position storage p = positions[msg.sender];
        require(p.principal + amount <= creditLimit(msg.sender), "TlkmPaylater: melebihi limit kredit");
        require(tlkm.balanceOf(address(this)) >= amount, "TlkmPaylater: likuiditas TLKM kontrak kurang");
        // Bunga FLAT dikunci saat pinjam: kewajiban = pokok * (1 + bunga).
        uint256 dueAdd = amount * (10000 + interestBps) / 10000;
        // effects sebelum interaction (CEI)
        p.principal += amount;
        p.dueAmount += dueAdd;
        p.dueDate    = block.timestamp + duePeriod;
        // interaction
        tlkm.safeTransfer(msg.sender, amount);
        emit Borrowed(msg.sender, amount, p.dueAmount, p.dueDate);
    }

    /// @notice Lunasi kewajiban TLKM (butuh approve TLKM ke kontrak dulu).
    ///         Dikurangkan dari dueAmount (kewajiban total termasuk bunga), bukan proporsi
    ///         principal. Saat kewajiban lunas (dueAmount==0), principal di-reset agar limit
    ///         pulih; bunga yang terkumpul tetap di saldo kontrak sebagai cadangan platform.
    function repay(uint256 amount) external nonReentrant {
        require(amount > 0, "TlkmPaylater: amount 0");
        Position storage p = positions[msg.sender];
        require(p.dueAmount > 0, "TlkmPaylater: tidak ada kewajiban");
        uint256 pay = amount > p.dueAmount ? p.dueAmount : amount; // clamp ke sisa kewajiban
        p.dueAmount -= pay;                 // effect
        if (p.dueAmount == 0) {
            p.principal = 0;                // posisi lunas -> pulihkan limit
        }
        tlkm.safeTransferFrom(msg.sender, address(this), pay); // interaction (pull)
        emit Repaid(msg.sender, pay, p.dueAmount);
    }

    /// @notice Tarik agunan tBNB — hanya jika kewajiban sudah 0.
    function withdrawCollateral(uint256 amount) external nonReentrant {
        Position storage p = positions[msg.sender];
        require(p.dueAmount == 0, "TlkmPaylater: lunasi kewajiban dulu");
        require(amount > 0 && amount <= p.collateral, "TlkmPaylater: jumlah agunan tidak valid");
        p.collateral -= amount; // effect sebelum interaction
        (bool ok, ) = payable(msg.sender).call{value: amount}("");
        require(ok, "TlkmPaylater: transfer tBNB gagal");
        emit Withdrawn(msg.sender, amount);
    }

    /// @notice Owner menyita agunan bila kewajiban lewat jatuh tempo (default/wanprestasi).
    function seize(address user) external onlyOwner nonReentrant {
        Position storage p = positions[user];
        require(p.dueAmount > 0, "TlkmPaylater: user tak punya kewajiban");
        require(block.timestamp > p.dueDate, "TlkmPaylater: belum jatuh tempo");
        uint256 col = p.collateral;
        // reset posisi (effects)
        p.collateral = 0;
        p.principal  = 0;
        p.dueAmount  = 0;
        p.dueDate    = 0;
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
        returns (uint256 collateral, uint256 principal, uint256 dueAmount, uint256 dueDate, uint256 limit)
    {
        Position storage p = positions[u];
        return (p.collateral, p.principal, p.dueAmount, p.dueDate, creditLimit(u));
    }

    /// @notice Likuiditas TLKM yang tersedia di kontrak (untuk dipinjamkan).
    function availableLiquidity() external view returns (uint256) {
        return tlkm.balanceOf(address(this));
    }

    // ===== Owner setters (parameter demo) =====
    function setRate(uint256 _rate) external onlyOwner {
        require(_rate > 0, "TlkmPaylater: rate harus > 0");
        rate = _rate;
        emit RateChanged(_rate);
    }

    function setInterestBps(uint256 _interestBps) external onlyOwner {
        require(_interestBps <= 5000, "TlkmPaylater: bunga maks 50%");
        interestBps = _interestBps;
        emit InterestChanged(_interestBps);
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
