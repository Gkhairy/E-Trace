# Contract addresses

All contracts are deployed on **BNB Smart Chain Testnet** (chain ID 97). Click an address to open it on BscScan.

| Contract                                                              | Address                                                                                                                                                            | What it does                                                                                          |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------- |
| [PaymentGatewayV3](payment-gateway-v3.md)                             | [`0x76e783878E4d88e435c4296F87c56446be72FDc9`](https://testnet.bscscan.com/address/0x76e783878E4d88e435c4296F87c56446be72FDc9)                                     | Multi-seller escrow: buyer confirm, refund and dispute; arbiter release and refund; 1% fee on release |
| [TLKM Token (BEP-20)](other-contracts.md#tlkm-token)                  | [`0x5D628943164d5D27eB102424045901e26720B4C1`](https://testnet.bscscan.com/address/0x5D628943164d5D27eB102424045901e26720B4C1)                                     | The payment token used across the app                                                                 |
| [DonationPool](other-contracts.md#donationpool)                       | [`0xdCCE11EADbE5426f946a61D7705C56761dF2f394`](https://testnet.bscscan.com/address/0xdCCE11EADbE5426f946a61D7705C56761dF2f394)                                     | Donation campaigns and recorded disbursements                                                         |
| [TlkmPaylater](tlkm-paylater.md)                                      | [`0x69D8f32F7e918061f32F26Ad7302906Cd857FCc1`](https://testnet.bscscan.com/address/0x69D8f32F7e918061f32F26Ad7302906Cd857FCc1)                                     | Collateralised credit and a lending pool with fixed terms                                             |
| [CommunityMultisigWallet](other-contracts.md#communitymultisigwallet) | _not deployed yet_ (source only) | On-chain community wallet where every action needs signer approval; planned for the next contract version |

{% hint style="info" %}
Community wallets in the prototype **don't use a smart contract yet**. Each group gets its own wallet address, managed by the app (its key is stored encrypted on the server). The app enforces the rules (a monthly allowance per member, or approval from every signer, each confirmed with a PIN), records every approval, and then sends a normal TLKM transfer. So on BscScan a community wallet such as `0xa408…849f` shows up as a regular address with TLKM transfers, not as a contract. `CommunityMultisigWallet` and `CommunityAllowanceWallet` are written and internally reviewed, and will replace the app-managed wallets in the next contract version.
{% endhint %}

### Add TLKM to your wallet

To see your TLKM balance in MetaMask, import a custom token:

* **Network:** BNB Smart Chain Testnet (chain ID 97)
* **Token address:** `0x5D628943164d5D27eB102424045901e26720B4C1`
* **Symbol:** TLKM
* **Decimals:** 18

### Source code

All contract sources are in the [`contracts/`](../../../contracts) folder of the repository. They are written in Solidity 0.8.20 and use OpenZeppelin's `SafeERC20`, `ReentrancyGuard` and `Ownable`.
