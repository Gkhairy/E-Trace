# Smart-contract audit report

{% hint style="warning" %}
This is an **internal** review, not an external audit. All contracts run on **BNB Smart Chain Testnet** and hold no real money. Every finding below is **acknowledged** and will be fixed in the next contract version, which will go through an external audit before mainnet.
{% endhint %}

## Summary

| | |
| --- | --- |
| **Scope** | `PaymentGatewayV3`, `TlkmPaylater`, `DonationPool`, `TLKMToken`, `CommunityMultisigWallet`, `CommunityAllowanceWallet` |
| **Out of scope** | `PaymentGateway.sol` (the old version, replaced by V3 and no longer used by the app) and OpenZeppelin libraries |
| **Compiler** | Solidity 0.8.20, OpenZeppelin 5 |
| **Method** | Two passes. The first used [Pashov's solidity-auditor skills](https://github.com/pashov/skills) with several specialised agents. The second was a line-by-line manual review of each contract against its accounting invariants and its role model. This page merges both passes. |
| **Date** | September 2026 |

| Severity | Count |
| --- | --- |
| High | 5 |
| Medium | 10 |
| Low | 9 |
| Informational | 4 |
| **Total** | **28** |

**Severity guide**

* **High:** users can lose funds, or funds can get stuck, without anyone acting maliciously or with only a cheap action by an attacker.
* **Medium:** funds are at risk only in particular conditions, or a privileged role holds more power than users would expect.
* **Low:** limited impact, edge cases, or missing safety nets.
* **Informational:** code quality or design notes.

## Why the fixes aren't deployed yet

A deployed contract can't be edited. A fix means deploying a new contract, moving the app to the new address, and leaving the old one to run out its existing orders and loans. Doing that during the competition would risk the running demo more than the findings do, because no real money is involved. So we are fixing everything in the next contract version (**PaymentGatewayV4**, **TlkmPaylaterV2**, **CommunityMultisigWalletV2**), which will be externally audited before mainnet. See the [Roadmap](roadmap.md).

Some findings are already reduced by the app itself. Those are noted under **Mitigation today**.

## Findings overview

| ID | Title | Severity | Status |
| --- | --- | --- | --- |
| [PG-01](#pg-01) | Buyer can refund an item that was already delivered | High | Acknowledged, mitigated off-chain |
| [PG-02](#pg-02) | A seller has no way to claim funds when the buyer stays silent | Medium | Acknowledged, mitigated off-chain |
| [PG-03](#pg-03) | Order IDs can be front-run or squatted | Medium | Acknowledged |
| [PG-04](#pg-04) | Arbiter and owner are single keys with broad power | Medium | Acknowledged |
| [PG-05](#pg-05) | Disputed items have no timeout | Low | Acknowledged |
| [PG-06](#pg-06) | Owner can't be transferred | Low | Acknowledged |
| [PG-07](#pg-07) | Unbounded strings and cart length | Informational | Acknowledged |
| [PL-01](#pl-01) | Bad-debt write-off can break the pool's accounting invariant | High | Acknowledged |
| [PL-02](#pl-02) | Seized collateral goes to the owner while suppliers take the loss | High | Acknowledged |
| [PL-03](#pl-03) | Early withdrawal ignores losses and can revert | High | Acknowledged |
| [PL-04](#pl-04) | A bucket with zero assets but live shares dilutes new suppliers | Medium | Acknowledged |
| [PL-05](#pl-05) | Borrowing again pushes back the due date of the whole debt | Medium | Acknowledged |
| [PL-06](#pl-06) | Seize takes all collateral, even for a tiny remaining debt | Medium | Acknowledged |
| [PL-07](#pl-07) | Owner can mint into the pool and change terms of existing deposits | Medium | Acknowledged |
| [PL-08](#pl-08) | Share price can be skewed by rounding on small buckets | Low | Acknowledged |
| [PL-09](#pl-09) | Just-in-time deposits can capture interest in the flexible bucket | Low | Acknowledged |
| [PL-10](#pl-10) | Interest weighting uses principal that was never written down | Low | Acknowledged |
| [PL-11](#pl-11) | No price oracle for collateral | Low | Acknowledged, known limitation |
| [PL-12](#pl-12) | Adding to a fixed-term deposit re-locks the whole position | Informational | Acknowledged |
| [MS-01](#ms-01) | Approvals from removed members still count | High | Acknowledged, not used by the live app |
| [MS-02](#ms-02) | Old configuration proposals can undo newer ones | Medium | Acknowledged, not used by the live app |
| [MS-03](#ms-03) | Approvals can't be revoked and proposals never expire | Low | Acknowledged |
| [DP-01](#dp-01) | Validator can send any campaign's balance anywhere | Medium | Acknowledged, mitigated off-chain |
| [DP-02](#dp-02) | Donations are accepted for unknown or closed campaigns | Low | Acknowledged |
| [DP-03](#dp-03) | Raw `transfer` calls instead of `SafeERC20` | Informational | Acknowledged |
| [TK-01](#tk-01) | Token owner can mint without limit | Medium | Acknowledged, testnet by design |
| [AW-01](#aw-01) | Re-adding a member duplicates the list and keeps old spending | Low | Acknowledged, contract not deployed |
| [AW-02](#aw-02) | Owner controls every limit, including their own | Informational | Acknowledged |

***

## PaymentGatewayV3

### PG-01

**Buyer can refund an item that was already delivered · High**

`refundItem` only checks that three days have passed since payment. It doesn't know whether the item shipped or arrived. A buyer who receives the goods on day four can still call `refundItem` and take the full amount back, and the seller has no on-chain defence.

**Mitigation today:** the settlement keeper watches tracking events. It releases delivered items to the seller through `arbiterRelease` after the auto-complete window, and refunds items the seller never shipped. The refund policy in the app says a refund is for an item not received after three days, and a problem with a delivered item goes to a dispute. A buyer who calls the contract directly can still exploit the gap between day three and the keeper's release.

**Fix in V4:** add `markShipped` for the seller (or arbiter). After an item ships, the buyer can no longer refund on their own. They can confirm or dispute. The buyer can refund only if the item hasn't shipped by a shipping deadline.

### PG-02

**A seller has no way to claim funds when the buyer stays silent · Medium**

Only the buyer (`confirmItem`) or the arbiter (`arbiterRelease`) can release funds to a seller. If the buyer never confirms and the arbiter key is offline or lost, the seller's funds stay locked.

**Mitigation today:** the keeper auto-completes delivered items.

**Fix in V4:** a seller can call `claimAfterTimeout` once an item has been shipped and the confirmation window has passed with no dispute.

### PG-03

**Order IDs can be front-run or squatted · Medium**

`itemCount[orderId]` is global and first-come-first-served, and the app builds order IDs as `CART-<milliseconds>`. Someone watching the mempool can copy a pending `payCart`'s order ID, pay a 1-wei cart first, and make the buyer's transaction revert. They can also pre-register future IDs. No funds are lost, but checkout can be blocked.

**Fix in V4:** key items by `(buyer, orderId)` so one buyer's ID can't collide with another's. The app will also add a random suffix to order IDs.

### PG-04

**Arbiter and owner are single keys with broad power · Medium**

The arbiter can release or refund **any** item that is `Paid` or `Disputed`, not only disputed ones. The owner can replace the arbiter instantly. If either key is compromised, every open escrow is at risk (funds can still only go to that item's buyer or seller, never to a third address).

**Fix before mainnet:** put the arbiter behind a multisig, limit direct arbiter action on non-disputed items to after a timeout, and add a delay to arbiter changes.

### PG-05

**Disputed items have no timeout · Low**

Once an item is `Disputed`, only the arbiter can resolve it. If the arbiter never acts, the funds stay locked.

**Fix in V4:** after a long dispute timeout, fall back to a refund to the buyer.

### PG-06

**Owner can't be transferred · Low**

`owner` is set in the constructor and has no transfer function. If the deployer key is lost, the arbiter can never be rotated.

**Fix in V4:** use OpenZeppelin `Ownable2Step`.

### PG-07

**Unbounded strings and cart length · Informational**

`orderId` and `productId` have no length limit, and `payCart` has no maximum number of items. Very long inputs only cost the caller more gas, but a cap makes gas predictable and keeps indexer output clean.

***

## TlkmPaylater

### PL-01

**Bad-debt write-off can break the pool's accounting invariant · High**

The pool must keep `liquidity + totalBorrows == Σ bucketAssets + platformReserve`. `seize` lowers `totalBorrows` by the full bad debt but lowers bucket assets by a rounded-down share per bucket, and clamps a bucket at zero if its loss is bigger than its assets. Either way the right-hand side falls by less than the left. The pool then owes suppliers more TLKM than it holds, and the last supplier to withdraw can't get out.

**Fix in V2:** absorb bad debt from `platformReserve` first, give the rounding remainder to the last bucket, and never clamp silently. Revert or carry the shortfall explicitly instead.

### PL-02

**Seized collateral goes to the owner while suppliers take the loss · High**

When a loan defaults, `seize` writes the unpaid principal off against suppliers' assets and sends the borrower's tBNB collateral to the owner. The platform keeps the collateral and the suppliers absorb the loss, which is the wrong way round.

**Fix in V2:** the platform reserve becomes first-loss capital. Seized collateral compensates the buckets that took the loss, or the owner can take it only after covering the loss from the reserve.

### PL-03

**Early withdrawal ignores losses and can revert · High**

An early withdrawal from a fixed-term bucket pays back full principal (`principalPortion`) even after a default has reduced the bucket's assets. That shifts the leaver's share of the loss onto everyone who stays. If assets fall below the principal being withdrawn, `bucketAssets -= payout` underflows and the withdrawal reverts, locking the position.

**Fix in V2:** pay out `min(principalPortion, shareValue)`.

### PL-04

**A bucket with zero assets but live shares dilutes new suppliers · Medium**

If a default drives a bucket's assets to zero while shares still exist, `supply` treats the bucket as empty and mints shares 1:1. The new supplier's deposit is then split with worthless old shares, so they lose value on deposit.

**Fix in V2:** use virtual shares and assets (the OpenZeppelin ERC-4626 offset), and don't accept deposits into a bucket whose shares have no backing.

### PL-05

**Borrowing again pushes back the due date of the whole debt · Medium**

Every `borrow` call sets `dueDate = now + duePeriod` for the whole position. A borrower can borrow 1 wei just before the due date and push back the deadline on the entire debt, so `seize` never becomes possible.

**Fix in V2:** set the due date only when the position has no outstanding debt, or track each loan's due date separately.

### PL-06

**Seize takes all collateral, even for a tiny remaining debt · Medium**

`seize` only checks `dueAmount > 0`. A borrower who repaid all principal but left 1 wei of interest loses all their collateral.

**Fix in V2:** seize only the collateral that covers the outstanding debt at the protocol's `rate`, and leave the rest withdrawable.

### PL-07

**Owner can mint into the pool and change terms of existing deposits · Medium**

The owner can mint unlimited TLKM into the pool as a supplier (`ownerSeedMint`). The owner can also change the profit share of a bucket through `setTerm`, which applies to deposits that are already locked, and can change interest and credit rate at any time.

**Fix before mainnet:** remove `ownerSeedMint` on mainnet, freeze terms per deposit when it's made, and put parameter changes behind a timelock.

### PL-08

**Share price can be skewed by rounding on small buckets · Low**

Shares are minted with integer division from internal accounting. On a bucket with very few shares and a lot of accrued interest, a new deposit can round down and lose part of its value to existing holders. This is hard to trigger on purpose, because interest reaches a bucket only in proportion to its principal.

**Fix in V2:** same virtual-offset change as PL-04.

### PL-09

**Just-in-time deposits can capture interest in the flexible bucket · Low**

The flexible bucket has no lock. Someone can deposit right before a large `repay` and withdraw right after, taking part of interest they didn't fund.

**Fix in V2:** a short minimum holding time, or interest that is streamed rather than added in one go.

### PL-10

**Interest weighting uses principal that was never written down · Low**

After a default, `bucketPrincipal` isn't reduced, so later interest is still split as if the lost principal were there.

**Fix in V2:** write down principal together with assets.

### PL-11

**No price oracle for collateral · Low (known limitation)**

The credit limit uses a fixed `rate` of TLKM per tBNB, set by the owner. There is no market price and no liquidation before the due date. This is a deliberate testnet simplification.

**Fix before mainnet:** a price oracle plus health-factor liquidation (on the [Roadmap](roadmap.md), phase 2).

### PL-12

**Adding to a fixed-term deposit re-locks the whole position · Informational**

A new `supply` into a fixed-term bucket resets `maturity` for the whole position, including earlier deposits. The app should warn users about this before they top up a locked deposit.

***

## CommunityMultisigWallet

The live app does **not** use this contract. Community wallets in the app are custodial and collect signer approvals off-chain, and the server enforces the rules. The contract exists as a reference deployment for groups that want a fully on-chain wallet.

### MS-01

**Approvals from removed members still count · High**

`approvals` is a plain counter, and `execute` compares it with the current threshold. When a configuration change removes a member, the approvals they already gave stay counted on every pending proposal. Old approvals can then meet a new, lower threshold. A proposal can be executed by people who are no longer members.

**Fix in V2:** count approvals from **current** members at execution time, and store a configuration nonce on each proposal so a membership change cancels all pending proposals.

### MS-02

**Old configuration proposals can undo newer ones · Medium**

A configuration proposal that reached its threshold but wasn't executed can be executed after a newer configuration, for example to add back a member who was just removed.

**Fix in V2:** the same configuration nonce as MS-01.

### MS-03

**Approvals can't be revoked and proposals never expire · Low**

A member can't take back an approval, and a proposal stays open forever.

**Fix in V2:** add `revoke(id)` and an expiry time.

***

## DonationPool

### DP-01

**Validator can send any campaign's balance anywhere · Medium**

`disburse(campaignId, to)` sends a campaign's whole balance to any address the validator chooses. A compromised validator key can empty every campaign.

**Mitigation today:** every disbursement is a public on-chain event linked from the campaign page and the Transparency Explorer. In the app, supervisors disburse only to the recipient recorded for the campaign.

**Fix before mainnet:** register each campaign's recipient on-chain when it's created, and put the validator behind a multisig.

### DP-02

**Donations are accepted for unknown or closed campaigns · Low**

`donate` accepts any `campaignId`, including one that was never created or is already closed. Funds sent there wait until a validator disburses them, and the donor has no refund path.

**Fix in V2:** register campaigns on-chain with an open or closed state.

### DP-03

**Raw `transfer` calls instead of `SafeERC20` · Informational**

`DonationPool` checks the returned bool, which is correct for TLKM. It would break with tokens that don't return a value. Switch to `SafeERC20` for consistency with the other contracts.

***

## TLKMToken

### TK-01

**Token owner can mint without limit · Medium (testnet by design)**

`mint` lets the owner create any amount of TLKM. The testnet uses this for faucets and demo liquidity. A mainnet token needs a fixed or capped supply, with minting (if any) behind a multisig and a timelock.

***

## CommunityAllowanceWallet

This contract isn't deployed and the app doesn't use it.

### AW-01

**Re-adding a member duplicates the list and keeps old spending · Low**

`removeMember` followed by `setMember` pushes the same address into `memberList` again, and the member's old `spent` value carries into a fresh period.

**Fix:** reset `spent` and skip the push when the address is already listed.

### AW-02

**Owner controls every limit, including their own · Informational**

The owner can add themselves with an unlimited allowance. That's the intended trust model for a group treasurer, but it should be stated clearly in the app.

***

## What's already done well

* `ReentrancyGuard` on every function that moves funds, and `SafeERC20` in the escrow, Paylater and multisig contracts.
* Escrow is stored per item, so one bad item never touches another item's funds.
* The escrow token and fee are immutable, and the fee is capped at 10%.
* Arbiter actions can send funds only to the item's buyer or seller.
* State is updated before external calls (checks-effects-interactions).
* Every value transfer emits an event that the Transparency Explorer indexes.

Found something we missed? Please open a private security advisory on the [GitHub repository](https://github.com/Gkhairy/E-Trace/security).
