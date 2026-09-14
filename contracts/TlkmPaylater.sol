// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

// =====================================================================
//  TlkmPaylater — Protokol LENDING DUA-SISI dengan BAGI HASIL (nisbah)
//  & DEPOSIT BERJANGKA (DEMO, UNAUDITED)
//  ---------------------------------------------------------------------
//  PENYUPLAI (lender) memilih JANGKA saat menyetor TLKM:
//    term 0 = Fleksibel  : tarik kapan saja,  nisbah 60:40 (penyuplai:platform)
//    term 1 = Tetap 30h  : terkunci 30 hari,  nisbah 70:30
//    term 2 = Tetap 90h  : terkunci 90 hari,  nisbah 85:15
//  Bunga peminjam dibagi ke tiap bucket menurut bobot pokok, lalu di tiap
//  bucket dipecah: bagian penyuplai (nisbah) menaikkan nilai share bucket,
//  sisanya masuk platformReserve (pendapatan platform).
//  Tarik deposit TETAP sebelum jatuh tempo -> hanya POKOK (bagi hasil hangus,
//  otomatis terdistribusi ke penyuplai lain di bucket yang sama).
//
//  PEMINJAM (borrower): agunan tBNB -> limit TLKM -> pinjam DARI likuiditas
//  penyuplai -> bayar pokok + bunga FLAT (mis. 3%). Bila likuiditas kurang,
//  pinjaman ditolak (kecuali owner sudah menambah likuiditas / seed mint).
//
//  AKUNTANSI (invariant): liquidity + totalBorrows == ΣbucketAssets + platformReserve
//  Nilai 1 share bucket b (TLKM) = bucketAssets[b] / bucketShares[b] (naik saat bunga masuk).
//
//  Agunan = native tBNB. Pinjaman = TLKM (18 des). PARAMETER DEMO — tanpa oracle,
//  tanpa likuidasi pasar. Hanya untuk demo lomba di testnet. JANGAN pakai dana nyata.
//
//  DEPLOY (Remix, Solidity 0.8.20, BSC Testnet chainId 97):
//    1. constructor(tlkm, rate, interestBps, duePeriod)
//       DEMO: rate=1_000_000e18, interestBps=300, duePeriod=604800
//    2. (opsional, utk ownerSeedMint) TLKMToken.transferOwnership(<kontrak ini>).
//    3. Isi PAYLATER_ADDRESS di .env.
//    (Demo: pakai setTerm() utk memendekkan durasi supaya jatuh tempo cepat terlihat.)
// =====================================================================

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/extensions/IERC20Metadata.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

interface ITlkmMintable {
    function mint(address to, uint256 amountWholeTokens) external;
    function owner() external view returns (address);
    function transferOwnership(address newOwner) external;
}

contract TlkmPaylater is Ownable, ReentrancyGuard {
    using SafeERC20 for IERC20;

    uint8   public constant TERMS = 3; // 0=fleksibel, 1=30h, 2=90h

    IERC20  public immutable tlkm;
    uint256 public immutable UNIT;     // 10**decimals (1e18)
    uint256 public rate;               // TLKM per 1 tBNB. DEMO: 1_000_000e18
    uint256 public interestBps;        // bunga flat peminjam. DEMO: 300
    uint256 public duePeriod;          // tenor utang peminjam (detik)

    // ---- Sisi POOL (penyuplai), per bucket jangka ----
    uint256[TERMS] public bucketShares;    // total share bucket
    uint256[TERMS] public bucketAssets;    // total TLKM milik penyuplai bucket (pokok + yield)
    uint256[TERMS] public bucketPrincipal; // total pokok setoran bucket (bobot alokasi bunga)
    uint256[TERMS] public nisbahBps;       // bagian penyuplai (bps). [6000,7000,8500]
    uint256[TERMS] public termSeconds;     // lama kunci. [0, 30d, 90d]

    struct SupplyPos { uint256 shares; uint256 principal; uint256 maturity; }
    mapping(address => mapping(uint8 => SupplyPos)) public supplyPos;

    uint256 public liquidity;        // TLKM menganggur siap dipinjamkan
    uint256 public totalBorrows;     // total pokok berjalan (dipinjam keluar)
    uint256 public platformReserve;  // bagian platform dari bunga (TLKM), bisa ditarik owner

    // ---- Sisi PEMINJAM ----
    struct Position { uint256 collateral; uint256 principal; uint256 dueAmount; uint256 dueDate; }
    mapping(address => Position) public positions;

    event Supplied(address indexed user, uint8 term, uint256 amount, uint256 sharesMinted, uint256 maturity);
    event SupplyWithdrawn(address indexed user, uint8 term, uint256 payout, uint256 sharesBurned, bool early);
    event Deposited(address indexed user, uint256 amount);
    event Borrowed(address indexed user, uint256 amount, uint256 dueAmount, uint256 dueDate);
    event Repaid(address indexed user, uint256 amount, uint256 interestToSuppliers, uint256 interestToPlatform, uint256 remainingDue);
    event Withdrawn(address indexed user, uint256 amount);
    event Seized(address indexed user, uint256 collateral, uint256 badDebt);
    event OwnerSeeded(uint256 minted, uint256 sharesMinted);
    event ReserveWithdrawn(address indexed to, uint256 amount);

    constructor(address _tlkm, uint256 _rate, uint256 _interestBps, uint256 _duePeriod) Ownable(msg.sender) {
        require(_tlkm != address(0), "TlkmPaylater: alamat TLKM nol");
        require(_rate > 0, "TlkmPaylater: rate harus > 0");
        require(_interestBps <= 5000, "TlkmPaylater: bunga maks 50%");
        tlkm        = IERC20(_tlkm);
        UNIT        = 10 ** uint256(IERC20Metadata(_tlkm).decimals());
        rate        = _rate;
        interestBps = _interestBps;
        duePeriod   = _duePeriod;
        // Default bucket: nisbah & durasi.
        nisbahBps[0]   = 6000; termSeconds[0] = 0;         // Fleksibel 60:40
        nisbahBps[1]   = 7000; termSeconds[1] = 30 days;   // Tetap 30h 70:30
        nisbahBps[2]   = 8500; termSeconds[2] = 90 days;   // Tetap 90h 85:15
    }

    // =================================================================
    //  PENYUPLAI (LENDER / EARN) — dengan jangka & nisbah
    // =================================================================

    /// @notice Setor TLKM ke pool pada jangka `term` (0..2). Butuh approve TLKM dulu.
    function supply(uint8 term, uint256 amount) external nonReentrant {
        require(term < TERMS, "TlkmPaylater: jangka tak valid");
        require(amount > 0, "TlkmPaylater: amount 0");
        uint256 sh = (bucketShares[term] == 0 || bucketAssets[term] == 0)
            ? amount
            : amount * bucketShares[term] / bucketAssets[term];
        require(sh > 0, "TlkmPaylater: setoran terlalu kecil");
        SupplyPos storage p = supplyPos[msg.sender][term];
        p.shares    += sh;
        p.principal += amount;
        if (term > 0) p.maturity = block.timestamp + termSeconds[term]; // kunci/perpanjang
        bucketShares[term]    += sh;
        bucketAssets[term]    += amount;
        bucketPrincipal[term] += amount;
        liquidity             += amount;
        tlkm.safeTransferFrom(msg.sender, address(this), amount);
        emit Supplied(msg.sender, term, amount, sh, p.maturity);
    }

    /// @notice Tarik sebagian/semua share dari jangka `term`. Untuk jangka tetap sebelum
    ///         jatuh tempo: hanya POKOK yang kembali (yield hangus utk penyuplai lain).
    function withdrawSupply(uint8 term, uint256 shareAmount) external nonReentrant {
        require(term < TERMS, "TlkmPaylater: jangka tak valid");
        SupplyPos storage p = supplyPos[msg.sender][term];
        require(shareAmount > 0 && shareAmount <= p.shares, "TlkmPaylater: share tidak cukup");

        uint256 assets          = shareAmount * bucketAssets[term] / bucketShares[term]; // pokok+yield
        uint256 principalPortion = p.principal * shareAmount / p.shares;
        bool early = (term > 0 && block.timestamp < p.maturity);
        uint256 payout = early ? principalPortion : assets;
        require(liquidity >= payout, "TlkmPaylater: likuiditas dipinjam, coba lagi nanti");

        // bucket (effects). Saat early, hanya `payout`(pokok) yang keluar dari assets;
        // selisih yield tetap di bucket -> menaikkan nilai share penyuplai lain.
        bucketShares[term]    -= shareAmount;
        bucketPrincipal[term] -= principalPortion;
        bucketAssets[term]    -= payout;
        liquidity             -= payout;
        // user pos
        p.shares    -= shareAmount;
        p.principal -= principalPortion;
        if (p.shares == 0) p.maturity = 0;
        // Jaga: bila bucket kosong tapi masih ada sisa assets (yield hangus tak bertuan),
        // sapu ke platformReserve agar tak ada aset nyangkut.
        if (bucketShares[term] == 0 && bucketAssets[term] > 0) {
            platformReserve += bucketAssets[term];
            bucketAssets[term] = 0;
            bucketPrincipal[term] = 0;
        }
        tlkm.safeTransfer(msg.sender, payout);
        emit SupplyWithdrawn(msg.sender, term, payout, shareAmount, early);
    }

    /// @notice Nilai klaim penyuplai (pokok + yield) untuk satu jangka.
    function supplierValue(address u, uint8 term) public view returns (uint256) {
        if (term >= TERMS || bucketShares[term] == 0) return 0;
        return supplyPos[u][term].shares * bucketAssets[term] / bucketShares[term];
    }

    /// @notice Info posisi penyuplai satu jangka: shares, pokok, nilai kini, jatuh tempo.
    function supplierInfo(address u, uint8 term)
        external view returns (uint256 shares, uint256 principal, uint256 value, uint256 maturity)
    {
        SupplyPos storage p = supplyPos[u][term];
        return (p.shares, p.principal, supplierValue(u, term), p.maturity);
    }

    /// @notice Statistik satu bucket: pokok, aset, share, nisbah, lama kunci.
    function bucketInfo(uint8 term)
        external view returns (uint256 principal, uint256 assets, uint256 shares, uint256 nisbah, uint256 lockSeconds)
    {
        return (bucketPrincipal[term], bucketAssets[term], bucketShares[term], nisbahBps[term], termSeconds[term]);
    }

    /// @notice Statistik pool global.
    function poolStats()
        external view returns (uint256 liq, uint256 borrows, uint256 reserve, uint256 totalAssets, uint256 utilBps)
    {
        uint256 a = bucketAssets[0] + bucketAssets[1] + bucketAssets[2];
        uint256 util = a == 0 ? 0 : totalBorrows * 10000 / a;
        return (liquidity, totalBorrows, platformReserve, a, util);
    }

    // =================================================================
    //  PEMINJAM
    // =================================================================

    function creditLimit(address u) public view returns (uint256) {
        return positions[u].collateral * rate / 1e18;
    }

    function canMint() public view returns (bool) {
        return ITlkmMintable(address(tlkm)).owner() == address(this);
    }

    function depositCollateral() external payable nonReentrant {
        require(msg.value > 0, "TlkmPaylater: nilai 0");
        positions[msg.sender].collateral += msg.value;
        emit Deposited(msg.sender, msg.value);
    }

    /// @notice Pinjam TLKM dari likuiditas penyuplai; kewajiban (pokok + bunga) dikunci.
    function borrow(uint256 amount) external nonReentrant {
        require(amount > 0, "TlkmPaylater: amount 0");
        Position storage p = positions[msg.sender];
        require(p.principal + amount <= creditLimit(msg.sender), "TlkmPaylater: melebihi limit kredit");
        require(liquidity >= amount, "TlkmPaylater: likuiditas pool kurang");
        uint256 dueAdd = amount * (10000 + interestBps) / 10000;
        p.principal += amount;
        p.dueAmount += dueAdd;
        p.dueDate    = block.timestamp + duePeriod;
        liquidity    -= amount;
        totalBorrows += amount;
        tlkm.safeTransfer(msg.sender, amount);
        emit Borrowed(msg.sender, amount, p.dueAmount, p.dueDate);
    }

    /// @notice Lunasi kewajiban (approve dulu). Bunga dibagi ke bucket (bobot pokok),
    ///         lalu dipecah nisbah: bagian penyuplai -> bucketAssets, sisanya -> platformReserve.
    function repay(uint256 amount) external nonReentrant {
        require(amount > 0, "TlkmPaylater: amount 0");
        Position storage p = positions[msg.sender];
        require(p.dueAmount > 0, "TlkmPaylater: tidak ada kewajiban");
        uint256 pay = amount > p.dueAmount ? p.dueAmount : amount;
        uint256 principalPart = pay > p.principal ? p.principal : pay;
        uint256 interestPart  = pay - principalPart;

        p.dueAmount  -= pay;
        p.principal  -= principalPart;
        totalBorrows -= principalPart;
        liquidity    += pay;
        if (p.dueAmount == 0) p.principal = 0;

        (uint256 toSup, uint256 toPlat) = _distributeInterest(interestPart);
        tlkm.safeTransferFrom(msg.sender, address(this), pay);
        emit Repaid(msg.sender, pay, toSup, toPlat, p.dueAmount);
    }

    /// @dev Bagi bunga ke 3 bucket menurut bobot pokok, pecah nisbah. Return (kePenyuplai, kePlatform).
    function _distributeInterest(uint256 interest) internal returns (uint256 toSup, uint256 toPlat) {
        if (interest == 0) return (0, 0);
        uint256 totalPrin = bucketPrincipal[0] + bucketPrincipal[1] + bucketPrincipal[2];
        if (totalPrin == 0) { platformReserve += interest; return (0, interest); }
        uint256 used;
        for (uint8 b = 0; b < TERMS; b++) {
            if (bucketPrincipal[b] == 0) continue;
            uint256 iB = interest * bucketPrincipal[b] / totalPrin;
            uint256 sup = iB * nisbahBps[b] / 10000;
            bucketAssets[b] += sup;
            platformReserve += (iB - sup);
            toSup  += sup;
            toPlat += (iB - sup);
            used   += iB;
        }
        // sisa pembulatan -> platform
        if (interest > used) { platformReserve += (interest - used); toPlat += (interest - used); }
    }

    function withdrawCollateral(uint256 amount) external nonReentrant {
        Position storage p = positions[msg.sender];
        require(p.dueAmount == 0, "TlkmPaylater: lunasi kewajiban dulu");
        require(amount > 0 && amount <= p.collateral, "TlkmPaylater: jumlah agunan tidak valid");
        p.collateral -= amount;
        (bool ok, ) = payable(msg.sender).call{value: amount}("");
        require(ok, "TlkmPaylater: transfer tBNB gagal");
        emit Withdrawn(msg.sender, amount);
    }

    /// @notice Sita agunan bila lewat tempo. Pokok tak kembali = kerugian penyuplai
    ///         (dipotong dari bucketAssets menurut bobot pokok); agunan tBNB -> owner.
    function seize(address user) external onlyOwner nonReentrant {
        Position storage p = positions[user];
        require(p.dueAmount > 0, "TlkmPaylater: user tak punya kewajiban");
        require(block.timestamp > p.dueDate, "TlkmPaylater: belum jatuh tempo");
        uint256 col     = p.collateral;
        uint256 badDebt = p.principal;
        if (badDebt > 0) {
            totalBorrows -= badDebt;
            uint256 totalPrin = bucketPrincipal[0] + bucketPrincipal[1] + bucketPrincipal[2];
            if (totalPrin > 0) {
                for (uint8 b = 0; b < TERMS; b++) {
                    uint256 loss = badDebt * bucketPrincipal[b] / totalPrin;
                    bucketAssets[b] = bucketAssets[b] > loss ? bucketAssets[b] - loss : 0;
                }
            }
        }
        p.collateral = 0; p.principal = 0; p.dueAmount = 0; p.dueDate = 0;
        if (col > 0) {
            (bool ok, ) = payable(owner()).call{value: col}("");
            require(ok, "TlkmPaylater: transfer agunan gagal");
        }
        emit Seized(user, col, badDebt);
    }

    function positionOf(address u)
        external view returns (uint256 collateral, uint256 principal, uint256 dueAmount, uint256 dueDate, uint256 limit)
    {
        Position storage p = positions[u];
        return (p.collateral, p.principal, p.dueAmount, p.dueDate, creditLimit(u));
    }

    /// @notice Likuiditas siap dipinjam (nyata, dari penyuplai).
    function availableLiquidity() external view returns (uint256) {
        return liquidity;
    }

    // =================================================================
    //  OWNER
    // =================================================================

    /// @notice Bootstrap likuiditas dengan mencetak TLKM (owner jadi penyuplai FLEKSIBEL).
    function ownerSeedMint(uint256 wholeTokens) external onlyOwner nonReentrant {
        require(wholeTokens > 0, "TlkmPaylater: 0");
        require(canMint(), "TlkmPaylater: mint mati (belum jadi owner TLKM)");
        uint256 minted = wholeTokens * UNIT;
        ITlkmMintable(address(tlkm)).mint(address(this), wholeTokens);
        uint8 term = 0;
        uint256 sh = (bucketShares[term] == 0 || bucketAssets[term] == 0)
            ? minted
            : minted * bucketShares[term] / bucketAssets[term];
        SupplyPos storage p = supplyPos[owner()][term];
        p.shares    += sh;
        p.principal += minted;
        bucketShares[term]    += sh;
        bucketAssets[term]    += minted;
        bucketPrincipal[term] += minted;
        liquidity             += minted;
        emit OwnerSeeded(minted, sh);
    }

    /// @notice Tarik pendapatan platform (bagi hasil bagian platform) ke alamat mana pun.
    function withdrawReserve(address to, uint256 amount) external onlyOwner nonReentrant {
        require(to != address(0), "TlkmPaylater: tujuan nol");
        require(amount <= platformReserve, "TlkmPaylater: melebihi reserve");
        require(liquidity >= amount, "TlkmPaylater: likuiditas dipinjam");
        platformReserve -= amount;
        liquidity       -= amount;
        tlkm.safeTransfer(to, amount);
        emit ReserveWithdrawn(to, amount);
    }

    function setTerm(uint8 term, uint256 lockSeconds, uint256 nisbah) external onlyOwner {
        require(term < TERMS, "TlkmPaylater: jangka tak valid");
        require(nisbah <= 10000, "TlkmPaylater: nisbah maks 100%");
        termSeconds[term] = lockSeconds;
        nisbahBps[term]   = nisbah;
    }

    function setRate(uint256 _rate) external onlyOwner {
        require(_rate > 0, "TlkmPaylater: rate harus > 0");
        rate = _rate;
    }

    function setInterestBps(uint256 _interestBps) external onlyOwner {
        require(_interestBps <= 5000, "TlkmPaylater: bunga maks 50%");
        interestBps = _interestBps;
    }

    function setDuePeriod(uint256 _duePeriod) external onlyOwner {
        duePeriod = _duePeriod;
    }

    function returnTokenOwnership(address to) external onlyOwner {
        require(to != address(0), "TlkmPaylater: tujuan nol");
        ITlkmMintable(address(tlkm)).transferOwnership(to);
    }
}
