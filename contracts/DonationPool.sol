// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

interface IERC20 {
    function transfer(address to, uint256 amount) external returns (bool);
    function transferFrom(address from, address to, uint256 amount) external returns (bool);
    function balanceOf(address account) external view returns (uint256);
}

/// @title DonationPool — donasi TLKM berbasis campaign yang transparan.
/// @notice Admin/validator membuat campaign (metadata off-chain; identitas on-chain =
///         `campaignId` = keccak256(slug)). Donatur menyetor TLKM ke sebuah campaign;
///         dana ditampung PER-CAMPAIGN. Validator menyalurkan saldo campaign ke wallet
///         penerima. Setiap donasi & penyaluran = event on-chain, bisa diaudit publik.
contract DonationPool {
    IERC20  public immutable token;   // TLKM
    address public immutable owner;   // deployer
    address public validator;         // pengawas yang boleh menyalurkan

    mapping(bytes32 => uint256) public raised;    // total masuk per campaign (seumur hidup)
    mapping(bytes32 => uint256) public balance;   // saldo belum disalurkan per campaign
    mapping(bytes32 => uint256) public disbursed; // total keluar per campaign

    event Donated(bytes32 indexed campaignId, address indexed donor, uint256 amount, uint256 timestamp);
    event Disbursed(bytes32 indexed campaignId, address indexed to, uint256 amount, address by, uint256 timestamp);
    event ValidatorChanged(address indexed oldValidator, address indexed newValidator);

    modifier onlyValidator() {
        require(msg.sender == validator, "Hanya validator");
        _;
    }

    constructor(address tlkm, address _validator) {
        require(tlkm != address(0) && _validator != address(0), "Alamat nol");
        token     = IERC20(tlkm);
        validator = _validator;
        owner     = msg.sender;
    }

    /// @notice Donasi TLKM ke sebuah campaign. Donatur WAJIB approve kontrak ini dulu.
    /// @param campaignId keccak256(slug) dari campaign (dihitung frontend/backend).
    function donate(bytes32 campaignId, uint256 amount) external {
        require(amount > 0, "Nominal harus > 0");
        raised[campaignId]  += amount;
        balance[campaignId] += amount;
        require(token.transferFrom(msg.sender, address(this), amount), "transferFrom gagal");
        emit Donated(campaignId, msg.sender, amount, block.timestamp);
    }

    /// @notice Salurkan SELURUH saldo campaign ke `to` (wallet penerima). Hanya validator.
    function disburse(bytes32 campaignId, address to) external onlyValidator {
        require(to != address(0), "Alamat tujuan nol");
        uint256 amt = balance[campaignId];
        require(amt > 0, "Saldo campaign kosong");
        balance[campaignId]    = 0;
        disbursed[campaignId] += amt;
        require(token.transfer(to, amt), "transfer gagal");
        emit Disbursed(campaignId, to, amt, msg.sender, block.timestamp);
    }

    /// @notice Rotasi wallet validator (owner only).
    function setValidator(address newValidator) external {
        require(msg.sender == owner, "Hanya owner");
        require(newValidator != address(0), "Alamat nol");
        emit ValidatorChanged(validator, newValidator);
        validator = newValidator;
    }
}
