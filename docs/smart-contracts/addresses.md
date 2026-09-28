# Contract addresses

All contracts are deployed on **BNB Smart Chain Testnet** (chain ID 97). Click an address to open it on BscScan.

| Contract | Address | What it does |
| --- | --- | --- |
| [PaymentGatewayV3](payment-gateway-v3.md) | [`0x76e783878E4d88e435c4296F87c56446be72FDc9`](https://testnet.bscscan.com/address/0x76e783878E4d88e435c4296F87c56446be72FDc9) | Multi-seller escrow: buyer confirm, refund and dispute; arbiter release and refund; 1% fee on release |
| [TLKM Token (BEP-20)](other-contracts.md#tlkm-token) | [`0x5D628943164d5D27eB102424045901e26720B4C1`](https://testnet.bscscan.com/address/0x5D628943164d5D27eB102424045901e26720B4C1) | The payment token used across the app |
| [DonationPool](other-contracts.md#donationpool) | [`0xdCCE11EADbE5426f946a61D7705C56761dF2f394`](https://testnet.bscscan.com/address/0xdCCE11EADbE5426f946a61D7705C56761dF2f394) | Donation campaigns and recorded disbursements |
| [TlkmPaylater](tlkm-paylater.md) | [`0x69D8f32F7e918061f32F26Ad7302906Cd857FCc1`](https://testnet.bscscan.com/address/0x69D8f32F7e918061f32F26Ad7302906Cd857FCc1) | Collateralised credit and a lending pool with fixed terms |
| [CommunityMultisigWallet](other-contracts.md#communitymultisigwallet) | one contract per group, for example [`0xa408e7991b7585ea3b94e38a5312d39169c8849f`](https://testnet.bscscan.com/address/0xa408e7991b7585ea3b94e38a5312d39169c8849f) | Shared wallet where every action needs signer approval |

## Add TLKM to your wallet

To see your TLKM balance in MetaMask, import a custom token:

* **Network:** BNB Smart Chain Testnet (chain ID 97)
* **Token address:** `0x5D628943164d5D27eB102424045901e26720B4C1`
* **Symbol:** TLKM
* **Decimals:** 18

## Source code

All contract sources are in the [`contracts/`](https://github.com/Gkhairy/E-Trace/tree/master/contracts) folder of the repository. They are written in Solidity 0.8.20 and use OpenZeppelin's `SafeERC20`, `ReentrancyGuard` and `Ownable`.
