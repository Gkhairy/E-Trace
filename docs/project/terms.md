# Terms of Service

_Last updated: 1 October 2026_

These terms explain how E-Trace works, what you agree to when you use it, and where its limits are. By creating an account or using any feature of E-Trace, you accept them.

{% hint style="warning" %}
**E-Trace is a prototype running on BNB Chain Testnet.** TLKM and tBNB on testnet have **no real-money value**. The rupiah amounts shown in the app (1 TLKM = Rp1,000) are for illustration only. Do not send real assets to any address shown in the app.
{% endhint %}

## 1. Definitions

| Term | Meaning |
| --- | --- |
| **E-Trace**, **we** | The E-Trace marketplace at [e-trace.shop](https://e-trace.shop) and its smart contracts |
| **Buyer** | A user who buys an item |
| **Seller** | A user who runs a store and sells items |
| **Supervisor / Arbiter** | The E-Trace team role that reviews disputes, held orders and donation campaigns. On-chain it acts through the arbiter key |
| **TLKM** | The BEP-20 token used for every payment in the prototype |
| **Escrow** | The [PaymentGatewayV3](../smart-contracts/payment-gateway-v3.md) contract that holds a buyer's payment until the item is settled |

## 2. Accounts

* You sign up with an email address and confirm it with a one-time code (OTP). You may also log in with MetaMask or another browser wallet.
* Each email account gets an embedded wallet protected by a **6-digit PIN**. Optional two-factor authentication (2FA) is available.
* New accounts receive **1,000 TLKM** on testnet so you can try the app.
* Keep your password and PIN secret. Anyone who has your PIN can move funds from your embedded wallet, and we cannot reverse a transaction signed with it.
* One person, one account. Bots and automated sign-ups are blocked (Cloudflare Turnstile and rate limits).

## 3. E-Wallet and transfers

* When you confirm a transaction with your PIN, the server signs it for you from your embedded wallet.
* **Gas is topped up automatically only on testnet.** Before a PIN transaction, the platform sends a little tBNB to your wallet so you don't have to buy gas. This is a testnet convenience and may not continue on mainnet.
* You can send TLKM to a **phone number** or a **wallet address**. For a phone number, the app first shows the recipient's display name (never their email) so you can check it before you confirm with your PIN.
* **On-chain transfers are final.** If you send to the wrong person or address, E-Trace cannot pull the funds back.

## 4. Buying and escrow

* You can pay for a cart with items from several sellers in **one** payment. The escrow still keeps a separate record for every item, so each seller's money stays apart.
* Your payment is held by the contract, not by E-Trace. It goes to the seller only when you confirm receipt, or when the order is settled under section 6.
* Nobody, the platform included, can send escrowed funds anywhere other than to the buyer or the seller of that item.
* An order counts as paid only after the server has read it back from the contract.

## 5. Refunds and disputes

* **Seller didn't ship:** if the seller hasn't shipped within **3 days**, the order is refunded to you in full.
* **Problem after shipping:** file a **dispute** with evidence (tracking number, photos). A supervisor reviews it and decides whether the money goes to you or to the seller. Disputes are **not** refunded automatically.
* Refunds are always **100%** of the item amount, with no fee.

## 6. Automatic settlement (AI keeper)

Once a day, a settlement keeper closes orders that would otherwise stay in escrow forever:

| Situation | Default | Result |
| --- | --- | --- |
| Seller hasn't shipped | after 3 days | refund to buyer |
| Delivered but buyer hasn't confirmed | after 3 days | release to seller |
| Far destination (ETA ≥ 10 days) | — | no auto-release; buyer gets more time |

For other cases, an AI reads the tracking history and suggests release, refund or hold. It acts on its own **only** when its confidence is at least **0.8** and the order is worth at most **1,000 TLKM**. Everything else goes to a supervisor. Every AI decision and its reason is stored for audit. See [AI auto-settlement](../how-it-works/ai-settlement.md).

## 7. Fees and tax

* **Buyers pay no fee and no tax** on top of the listed price.
* **Sellers pay a 1% escrow fee**, deducted only when money is released to them. The rate is fixed in the contract and can never exceed 10%.
* **Refunds, donations and community wallets have no fee.**
* **VAT (PPN 11%) is borne by the seller and is calculated on the 1% platform fee, not on the item price.** Example: a 100 TLKM sale has a 1 TLKM fee, so the VAT is 0.11 TLKM. It is shown in the seller dashboard for tax reporting and is not deducted separately on-chain.

## 8. On-Time Guarantee

* An optional delivery-time insurance you can add at checkout for a premium of **2 TLKM**.
* The promised date is the distance-based ETA plus a 3-day buffer. An order counts as late only after a further **2-day grace period**.
* If the delay was caused by the seller or the courier, the pool refunds your **shipping cost**, capped at **30 TLKM per claim** and once per order. Total payouts are capped at 500 TLKM per day.
* The premium is not refunded if the parcel arrives on time. See [On-Time Guarantee](../how-it-works/on-time-guarantee.md).

## 9. Paylater

**Borrowing**

* Lock tBNB as collateral. Your credit limit is `collateral × rate` (1,000,000 TLKM per tBNB on testnet), as long as the pool has free liquidity.
* **For now, interest is a flat 3% per loan**, no matter how long you keep it within the loan period (7 days on testnet). In a later version, **interest will depend on the loan duration**, and the rate in force will be shown before you borrow.
* You can repay in one go or in parts. Once the debt is zero you can withdraw your collateral.
* If a loan is overdue, the platform may seize your collateral to cover it. This is recorded on-chain.

**Funding the pool**

* Choose Flexible, Fixed 30 days or Fixed 90 days. Funders receive 60%, 70% or 85% of the interest respectively; the platform keeps the rest.
* If you withdraw a fixed-term deposit early, you get your principal back but give up the earnings.
* Returns are not guaranteed. If borrowers default and the collateral does not cover the debt, the pool can make a loss.

{% hint style="info" %}
Paylater is a testnet demo. There is no price oracle or market liquidation yet. Nothing in E-Trace is financial or investment advice.
{% endhint %}

## 10. Community wallets and friends

* A community wallet holds TLKM for a group, in one of two modes:
  * **Monthly allowance:** each member can spend up to a set limit per month.
  * **Multisig:** sending funds, inviting or removing members, and transferring ownership need approval from **all** signers.
* A member can pay a checkout from community funds; the order is paid only after the required approvals.
* Members are responsible for whom they invite and what they approve.

## 11. Donations and Disaster Radar

* The Disaster Radar collects events from BMKG, GDACS and Indonesian news, and an AI scores them. High-scoring events open a 30-day campaign automatically; mid-scoring ones are reviewed by a supervisor.
* Every donation and disbursement goes through the [DonationPool](../smart-contracts/other-contracts.md#donationpool) contract and is public.
* Donations are **voluntary and final**. Donating has no fee. See [Disaster Radar](../how-it-works/disaster-radar.md).

## 12. Sellers

By opening a store you agree to:

* describe items honestly, with real photos and correct prices,
* ship within the stated time and enter a valid tracking number,
* not sell anything illegal in Indonesia, counterfeit goods, weapons, drugs, or items that infringe someone else's rights.

Stores that break these rules may be hidden or suspended, and their open orders refunded.

## 13. Privacy and transparency

* Personal data (name, address, phone number) is stored **encrypted** in the application database and is **never written on-chain**, in line with Indonesia's Personal Data Protection Law (UU PDP).
* On-chain records, and the public [Transparency Explorer](../how-it-works/explorer.md), show only wallet addresses, amounts, order IDs and product IDs. Blockchain data is public and permanent.

## 14. AI features

The EVA chatbot, DeliveryAI and the Disaster Radar use AI models that can be wrong. Large or uncertain decisions are always passed to a human supervisor. Don't rely on the chatbot for legal, tax or financial advice.

## 15. Sponsored banners

The banners at the top of the home page may be paid placements. A sponsored banner is not an endorsement by E-Trace.

## 16. Limits of liability

* Smart contracts and blockchains carry risks: bugs, network outages and congestion. Known contract findings are listed in the [Audit report](audit-report.md) and will be fixed before mainnet.
* E-Trace is not responsible for losses caused by a leaked PIN or password, transfers to the wrong recipient, or problems on BNB Chain itself.
* The prototype is provided **as is**, for testing and demonstration.

## 17. Suspension

We may suspend an account that commits fraud, abuses the platform, tries to attack the system, or breaks these terms. Funds held in escrow are still settled under sections 5 and 6.

## 18. Changes and governing law

* We may update these terms. The date at the top will change, and material changes will be announced in the app.
* These terms are governed by the laws of the Republic of Indonesia.
* Questions or a security issue? Open an issue or a private [security advisory](https://github.com/Gkhairy/E-Trace/security) on GitHub.
