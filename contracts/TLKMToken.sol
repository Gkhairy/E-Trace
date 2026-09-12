// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

// =============================================================
//  TLKM Token (BEP-20 — kompatibel ERC-20, dipakai di BNB Smart Chain)
//  -------------------------------------------------------------
//  Ini token buatan kamu sendiri, dipakai sebagai alat bayar
//  di toko crypto. Dideploy di jaringan BNB Smart Chain Testnet (testnet).
//
//  Cara pakai di Remix:
//   1. Buka https://remix.ethereum.org
//   2. Buat file baru: TLKMToken.sol, tempel isi file ini.
//   3. Di tab "Solidity Compiler" pilih versi 0.8.20+ lalu Compile.
//   4. Di tab "Deploy & Run", Environment = "Injected Provider - MetaMask"
//      (pastikan MetaMask lagi di jaringan BNB Smart Chain Testnet).
//   5. Deploy. Simpan alamat kontraknya -> ini "TLKM_ADDRESS".
//
//  Import OpenZeppelin lewat URL (Remix otomatis mengunduhnya).
// =============================================================

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

contract TLKMToken is ERC20, Ownable {
    // Desimal token. USDC pakai 6, tapi untuk token sendiri
    // 18 itu standar dan paling gampang. Kita pakai 18.
    // (Frontend harus pakai angka desimal yang SAMA -> 18.)

    constructor(uint256 initialSupplyWholeTokens)
        ERC20("Telkom Token", "TLKM")
        Ownable(msg.sender)
    {
        // Mint pasokan awal ke wallet yang men-deploy (kamu).
        // initialSupplyWholeTokens = jumlah token utuh, misal 1000000 = 1 juta TLKM.
        _mint(msg.sender, initialSupplyWholeTokens * 10 ** decimals());
    }

    // Owner (kamu) bisa mencetak token tambahan kapan saja.
    // Berguna untuk testing: bagikan TLKM ke wallet uji coba pembeli.
    function mint(address to, uint256 amountWholeTokens) external onlyOwner {
        _mint(to, amountWholeTokens * 10 ** decimals());
    }
}
