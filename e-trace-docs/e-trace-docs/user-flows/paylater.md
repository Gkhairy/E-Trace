# Paylater: borrow and fund

Paylater is a two-sided lending pool in the [TlkmPaylater](../smart-contracts/tlkm-paylater.md) contract. **Funders** put TLKM into the pool and earn a share of the interest. **Borrowers** lock tBNB as collateral and borrow TLKM from that pool to shop now and pay later.

### Borrow

1. Open **Paylater → Borrow** and deposit tBNB as collateral.
2. Your credit limit appears right away: `limit = collateral × rate`. On testnet the rate is 1,000,000 TLKM per tBNB.
3. Borrow any amount up to your limit, as long as the pool has enough free liquidity.
4. You owe the amount plus a **flat 3% interest**, due after the loan period (7 days on testnet).
5. Repay in one go or in parts. When the debt is zero you can withdraw your collateral.

If a loan is overdue, the platform can seize the collateral to cover it. That is recorded on-chain as a `Seized` event, along with any bad debt.

### Fund the pool

Open **Paylater → Fund**, choose a term, and deposit TLKM:

| Term          | Lock                    | Funder's share of interest | Platform's share |
| ------------- | ----------------------- | -------------------------- | ---------------- |
| Flexible      | none, withdraw any time | 60%                        | 40%              |
| Fixed 30 days | 30 days                 | 70%                        | 30%              |
| Fixed 90 days | 90 days                 | 85%                        | 15%              |

Interest from borrowers is split between the three terms by how much principal each one holds. Inside each term, the funders' share raises the value of their pool shares, so your balance grows without you doing anything.

If you pull a fixed-term deposit out early, you get your principal back but give up the earnings. Those earnings stay in the pool for the other funders in the same term.

{% hint style="warning" %}
These are demo parameters for testnet. There is no price oracle and no market liquidation yet. See [Security](../project/security.md).
{% endhint %}
