// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

/// @title CommunityAllowanceWallet — Dompet Bersama + Jatah Bulanan (Mode A)
/// @notice Satu dompet menampung TLKM. Penanggung jawab (owner) menunjuk anggota dan
///         mengatur BATAS penarikan per bulan tiap anggota. Anggota bisa menarik sampai
///         batasnya; jatah reset otomatis tiap 30 hari (berbasis waktu on-chain).
/// @dev    Pakai SafeERC20 + ReentrancyGuard. Semua aksi emit event untuk audit di BscScan.
contract CommunityAllowanceWallet is ReentrancyGuard {
    using SafeERC20 for IERC20;

    IERC20  public immutable token;   // TLKM
    address public owner;             // penanggung jawab
    uint256 public constant PERIOD = 30 days;

    struct Member {
        bool    active;
        uint256 monthlyLimit;   // batas tarik per periode (wei)
        uint256 spent;          // sudah ditarik pada periode berjalan
        uint256 periodStart;    // awal periode berjalan
    }
    mapping(address => Member) public members;
    address[] public memberList;      // untuk enumerasi (jumlah kecil-menengah)

    event Deposited(address indexed from, uint256 amount);
    event MemberSet(address indexed member, uint256 monthlyLimit);
    event MemberRemoved(address indexed member);
    event Withdrawn(address indexed member, uint256 amount, uint256 remainingThisPeriod);
    event OwnerChanged(address indexed oldOwner, address indexed newOwner);

    modifier onlyOwner() {
        require(msg.sender == owner, "Hanya penanggung jawab");
        _;
    }

    constructor(address tlkm, address _owner) {
        require(tlkm != address(0) && _owner != address(0), "Alamat nol");
        token = IERC20(tlkm);
        owner = _owner;
    }

    /// @notice Setor TLKM ke dompet bersama. Penyetor harus approve dulu.
    function deposit(uint256 amount) external nonReentrant {
        require(amount > 0, "Nominal 0");
        token.safeTransferFrom(msg.sender, address(this), amount);
        emit Deposited(msg.sender, amount);
    }

    /// @notice Tambah/ubah anggota beserta batas bulanannya. Hanya owner.
    function setMember(address member, uint256 monthlyLimit) external onlyOwner {
        require(member != address(0), "Alamat nol");
        Member storage m = members[member];
        if (!m.active) {
            m.active = true;
            m.periodStart = block.timestamp;
            memberList.push(member);
        }
        m.monthlyLimit = monthlyLimit;
        emit MemberSet(member, monthlyLimit);
    }

    /// @notice Nonaktifkan anggota. Hanya owner.
    function removeMember(address member) external onlyOwner {
        require(members[member].active, "Bukan anggota");
        members[member].active = false;
        members[member].monthlyLimit = 0;
        emit MemberRemoved(member);
    }

    /// @notice Tarik dana sampai batas bulanan. Jatah reset otomatis tiap 30 hari.
    function withdraw(uint256 amount) external nonReentrant {
        Member storage m = members[msg.sender];
        require(m.active, "Bukan anggota aktif");
        require(amount > 0, "Nominal 0");

        // Reset periode bila sudah lewat 30 hari.
        if (block.timestamp >= m.periodStart + PERIOD) {
            m.periodStart = block.timestamp;
            m.spent = 0;
        }
        require(m.spent + amount <= m.monthlyLimit, "Melebihi jatah bulan ini");
        require(token.balanceOf(address(this)) >= amount, "Saldo dompet kurang");

        m.spent += amount;                       // efek sebelum interaksi (CEI)
        token.safeTransfer(msg.sender, amount);
        emit Withdrawn(msg.sender, amount, m.monthlyLimit - m.spent);
    }

    /// @notice Ganti penanggung jawab. Hanya owner.
    function transferOwnership(address newOwner) external onlyOwner {
        require(newOwner != address(0), "Alamat nol");
        emit OwnerChanged(owner, newOwner);
        owner = newOwner;
    }

    // ===== View =====
    function balance() external view returns (uint256) {
        return token.balanceOf(address(this));
    }

    function memberCount() external view returns (uint256) {
        return memberList.length;
    }

    /// @notice Sisa jatah anggota pada periode berjalan (menghitung reset).
    function remaining(address member) external view returns (uint256) {
        Member storage m = members[member];
        if (!m.active) return 0;
        uint256 spent = block.timestamp >= m.periodStart + PERIOD ? 0 : m.spent;
        return m.monthlyLimit > spent ? m.monthlyLimit - spent : 0;
    }
}
