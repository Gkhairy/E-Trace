# Other contracts

## TLKM Token

**Address:** [`0x5D628943164d5D27eB102424045901e26720B4C1`](https://testnet.bscscan.com/address/0x5D628943164d5D27eB102424045901e26720B4C1) · **Source:** [`contracts/TLKMToken.sol`](https://github.com/Gkhairy/E-Trace/blob/master/contracts/TLKMToken.sol)

A standard BEP-20 (ERC-20) token built on OpenZeppelin.

| Property | Value |
| --- | --- |
| Name / symbol | Telkom Token / TLKM |
| Decimals | 18 |
| Display rate in the app | 1 TLKM = Rp1,000 |
| Minting | The owner can mint more (`mint(to, wholeTokens)`), which the testnet uses for faucets and demos |

## DonationPool

**Address:** [`0xdCCE11EADbE5426f946a61D7705C56761dF2f394`](https://testnet.bscscan.com/address/0xdCCE11EADbE5426f946a61D7705C56761dF2f394) · **Source:** [`contracts/DonationPool.sol`](https://github.com/Gkhairy/E-Trace/blob/master/contracts/DonationPool.sol)

Campaign-based donations. A campaign's on-chain ID is `keccak256(slug)`; its title and story live off-chain.

| Function | Caller | Description |
| --- | --- | --- |
| `donate(campaignId, amount)` | anyone | Donate TLKM to a campaign (approve first) |
| `disburse(campaignId, to)` | validator | Send the **whole** campaign balance to a recipient wallet |
| `setValidator(newValidator)` | owner | Rotate the validator |
| `raised`, `balance`, `disbursed` | anyone | Per-campaign totals |

Events: `Donated(campaignId, donor, amount, timestamp)`, `Disbursed(campaignId, to, amount, by, timestamp)`, `ValidatorChanged`.

## CommunityMultisigWallet

**Example deployment:** [`0xa408e7991b7585ea3b94e38a5312d39169c8849f`](https://testnet.bscscan.com/address/0xa408e7991b7585ea3b94e38a5312d39169c8849f) · **Source:** [`contracts/CommunityMultisigWallet.sol`](https://github.com/Gkhairy/E-Trace/blob/master/contracts/CommunityMultisigWallet.sol)

Each community group gets its own contract. Members hold TLKM together, and nothing moves without enough approvals.

| Function | Description |
| --- | --- |
| `deposit(amount)` | Anyone adds TLKM to the group wallet |
| `proposeTransfer(to, amount)` | A member proposes a payment (the proposer's approval counts) |
| `proposeConfig(members[], threshold)` | A member proposes changing the member list or the threshold |
| `approve(id)` | Another member approves a proposal |
| `execute(id)` | Runs the proposal once it has reached the threshold |

Events: `Deposited`, `Proposed`, `Approved`, `Executed`, `ConfigChanged`.

## CommunityAllowanceWallet

**Source:** [`contracts/CommunityAllowanceWallet.sol`](https://github.com/Gkhairy/E-Trace/blob/master/contracts/CommunityAllowanceWallet.sol)

A group wallet where the owner gives each member a monthly spending limit, like pocket money. Members withdraw up to their limit, and the limit resets each period.

| Function | Description |
| --- | --- |
| `deposit(amount)` | Add TLKM |
| `setMember(member, monthlyLimit)` / `removeMember(member)` | Owner manages members and limits |
| `withdraw(amount)` | A member withdraws within this period's remaining limit |
| `remaining(member)` | How much a member can still withdraw this period |
