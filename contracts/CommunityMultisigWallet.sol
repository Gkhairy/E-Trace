// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

import "@openzeppelin/contracts/token/ERC20/IERC20.sol";
import "@openzeppelin/contracts/token/ERC20/utils/SafeERC20.sol";
import "@openzeppelin/contracts/utils/ReentrancyGuard.sol";

/// @title CommunityMultisigWallet — Multisig Perusahaan M-dari-N (Mode B)
/// @notice Mengirim dana TLKM butuh persetujuan M dari N anggota. Perubahan aturan
///         (anggota/ambang) juga lewat persetujuan — tak bisa diubah sepihak.
/// @dev    SafeERC20 + ReentrancyGuard. Semua aksi emit event untuk audit. Didesain
///         untuk jumlah anggota kecil-menengah (sampai belasan) demi hemat gas.
contract CommunityMultisigWallet is ReentrancyGuard {
    using SafeERC20 for IERC20;

    IERC20  public immutable token;   // TLKM
    address[] public members;
    mapping(address => bool) public isMember;
    uint256 public threshold;         // M

    enum Kind { Transfer, Config }
    struct Proposal {
        Kind      kind;
        address   to;                 // untuk Transfer
        uint256   amount;             // untuk Transfer
        address[] newMembers;         // untuk Config
        uint256   newThreshold;       // untuk Config
        uint256   approvals;
        bool      executed;
        address   proposer;
    }
    Proposal[] private _proposals;
    mapping(uint256 => mapping(address => bool)) public approvedBy;

    event Deposited(address indexed from, uint256 amount);
    event Proposed(uint256 indexed id, Kind kind, address indexed proposer);
    event Approved(uint256 indexed id, address indexed member, uint256 approvals);
    event Executed(uint256 indexed id, Kind kind);
    event ConfigChanged(address[] members, uint256 threshold);

    modifier onlyMember() {
        require(isMember[msg.sender], "Bukan anggota");
        _;
    }

    constructor(address tlkm, address[] memory _members, uint256 _threshold) {
        require(tlkm != address(0), "Alamat nol");
        require(_members.length > 0 && _threshold > 0 && _threshold <= _members.length, "Ambang tidak valid");
        token = IERC20(tlkm);
        _setMembers(_members, _threshold);
    }

    function _setMembers(address[] memory _members, uint256 _threshold) internal {
        // reset keanggotaan lama
        for (uint256 i = 0; i < members.length; i++) {
            isMember[members[i]] = false;
        }
        delete members;
        for (uint256 i = 0; i < _members.length; i++) {
            require(_members[i] != address(0) && !isMember[_members[i]], "Anggota tidak valid/duplikat");
            isMember[_members[i]] = true;
            members.push(_members[i]);
        }
        threshold = _threshold;
        emit ConfigChanged(_members, _threshold);
    }

    /// @notice Setor TLKM ke kas bersama. Penyetor harus approve dulu.
    function deposit(uint256 amount) external nonReentrant {
        require(amount > 0, "Nominal 0");
        token.safeTransferFrom(msg.sender, address(this), amount);
        emit Deposited(msg.sender, amount);
    }

    /// @notice Ajukan pengiriman dana. Pengusul otomatis ikut menyetujui.
    function proposeTransfer(address to, uint256 amount) external onlyMember returns (uint256) {
        require(to != address(0) && amount > 0, "Parameter tidak valid");
        Proposal storage p = _proposals.push();
        p.kind = Kind.Transfer;
        p.to = to;
        p.amount = amount;
        p.proposer = msg.sender;
        uint256 id = _proposals.length - 1;
        emit Proposed(id, Kind.Transfer, msg.sender);
        _approve(id);
        return id;
    }

    /// @notice Ajukan perubahan aturan (anggota + ambang). Butuh persetujuan juga.
    function proposeConfig(address[] calldata newMembers, uint256 newThreshold) external onlyMember returns (uint256) {
        require(newMembers.length > 0 && newThreshold > 0 && newThreshold <= newMembers.length, "Ambang tidak valid");
        Proposal storage p = _proposals.push();
        p.kind = Kind.Config;
        p.newMembers = newMembers;
        p.newThreshold = newThreshold;
        p.proposer = msg.sender;
        uint256 id = _proposals.length - 1;
        emit Proposed(id, Kind.Config, msg.sender);
        _approve(id);
        return id;
    }

    /// @notice Setujui sebuah usulan.
    function approve(uint256 id) external onlyMember {
        _approve(id);
    }

    function _approve(uint256 id) internal {
        Proposal storage p = _proposals[id];
        require(!p.executed, "Sudah dieksekusi");
        require(!approvedBy[id][msg.sender], "Sudah menyetujui");
        approvedBy[id][msg.sender] = true;
        p.approvals += 1;
        emit Approved(id, msg.sender, p.approvals);
    }

    /// @notice Eksekusi usulan setelah ambang M tercapai. Bisa dipanggil anggota mana pun.
    function execute(uint256 id) external nonReentrant onlyMember {
        Proposal storage p = _proposals[id];
        require(!p.executed, "Sudah dieksekusi");
        require(p.approvals >= threshold, "Persetujuan belum cukup");

        p.executed = true; // efek sebelum interaksi (CEI)
        if (p.kind == Kind.Transfer) {
            require(token.balanceOf(address(this)) >= p.amount, "Saldo kurang");
            token.safeTransfer(p.to, p.amount);
        } else {
            _setMembers(p.newMembers, p.newThreshold);
        }
        emit Executed(id, p.kind);
    }

    // ===== View =====
    function balance() external view returns (uint256) { return token.balanceOf(address(this)); }
    function memberCount() external view returns (uint256) { return members.length; }
    function proposalCount() external view returns (uint256) { return _proposals.length; }

    /// @notice Ringkasan sebuah usulan (untuk ditampilkan di frontend).
    function getProposal(uint256 id) external view returns (
        Kind kind, address to, uint256 amount, uint256 newThreshold,
        uint256 approvals, bool executed, address proposer
    ) {
        Proposal storage p = _proposals[id];
        return (p.kind, p.to, p.amount, p.newThreshold, p.approvals, p.executed, p.proposer);
    }
}
