# Business model

E-Trace earns money where it adds value: when a trade closes safely, when credit is repaid, when a delivery is insured, and when brands want to be seen by shoppers. Donations stay free.

### Revenue streams

#### 1. Escrow fee: 1% on every completed sale

* Taken **only when money is released** to the seller, and paid by the seller out of the payout. Buyers pay the price they see.
* Refunds are free: if the sale doesn't happen, nobody pays a fee.
* The rate is hard-coded in [PaymentGatewayV3](../smart-contracts/payment-gateway-v3.md) and can never go above 10%. Every fee shows up in a public `ItemCompleted` event.
* VAT (11%) is applied to the platform fee, not the item price, and appears in the seller's report.

#### 2. Paylater interest share

Borrowers pay a flat interest on each loan. The contract splits it between funders and the platform according to the term the funds are locked for:

| Funder's term | Platform keeps  |
| ------------- | --------------- |
| Flexible      | 40% of interest |
| Fixed 30 days | 30%             |
| Fixed 90 days | 15%             |

The platform's share builds up in `platformReserve`, visible on-chain.

#### 3. Sponsored banners on the home page

The top of the E-Trace home page is a banner carousel (the jumbotron), and it's the first thing every shopper sees. It is already live: supervisors can upload a banner, link it to any page, set its position and switch it on or off.

We sell these slots to:

* **Sellers** who want to promote their store or a product launch,
* **Brands and partners**, such as couriers, wallets and Web3 projects, who want to reach crypto-ready shoppers,
* **Campaigns**, for example featuring a disaster relief drive for free as a public service.

Planned pricing: a flat fee per slot per week, paid in TLKM. Because payment is on-chain, advertisers get a public receipt, and we can publish exactly how much ad revenue the platform earns. Later on, once we measure impressions and clicks, we'll move to performance pricing (per 1,000 impressions or per click). See the [Roadmap](roadmap.md).

#### 4. On-Time Guarantee premiums

Buyers can pay a small premium (2 TLKM by default) to insure delivery time. The difference between premiums collected and payouts made is margin for the insurance pool. Payouts are capped per claim and per day, so the pool can't be drained.

#### 5. Future: premium seller tools

Seller reports, the AI chatbot and analytics are free today. Advanced versions can become a monthly subscription for high-volume stores.

### What stays free

* **Donations and community wallets:** 0% fee, always.
* **Buyers:** no fees on top of the listed price.
* **Refunds:** never charged.

### Market and projections

#### Market size

|         | Market                            | Size                                                         | Source                                      |
| ------- | --------------------------------- | ------------------------------------------------------------ | ------------------------------------------- |
| **TAM** | E-commerce users in Indonesia     | 73 million (2025)                                            | BKPerdag / Ministry of Trade, 2025          |
| **SAM** | Online SMEs, donors and donations | 4.4 million SMEs, 11.7 million donors, 1 million donations   | BKPerdag / Ministry of Trade 2024, Kitabisa |
| **SOM** | First-market target               | 500 SMEs, 100 communities/organizations, 10,000 users/donors | Internal target                             |

#### Revenue projection (IDR)

Based on transaction volume (GMV) growing 25% per year. The escrow fee alone is 1% of GMV; the other streams are added on top.

|                          | Year 1    | Year 2    | Year 3    |
| ------------------------ | --------- | --------- | --------- |
| Transaction volume (GMV) | 10.0 B    | 12.5 B    | 15.6 B    |
| Escrow fee (1%)          | 100 M     | 125 M     | 156 M     |
| Sponsored banners        | 60 M      | 90 M      | 130 M     |
| Paylater interest share  | 32 M      | 45 M      | 63 M      |
| On-Time Guarantee margin | 10 M      | 15 M      | 20 M      |
| **Total revenue**        | **202 M** | **275 M** | **369 M** |

B = billion, M = million. Figures are projections, not results.

#### How Paylater earns

Paylater makes money on every loan that is repaid, on top of the escrow fee from the purchase itself:

* Borrowers pay a **flat 3% interest** per loan.
* On average the platform keeps about **30%** of that interest (40% / 30% / 15% depending on the funders' term), so platform revenue is roughly **0.9% of loan volume**.
* We assume **35% of GMV** is paid with Paylater in Year 1, rising to 40% and 45% as more shoppers get a credit limit.

|                                 | Year 1   | Year 2   | Year 3   |
| ------------------------------- | -------- | -------- | -------- |
| Share of GMV paid with Paylater | 35%      | 40%      | 45%      |
| Loan volume                     | 3.5 B    | 5.0 B    | 7.0 B    |
| Interest paid by borrowers (3%) | 105 M    | 150 M    | 210 M    |
| → to funders (\~70%)            | 73 M     | 105 M    | 147 M    |
| → **to E-Trace (\~30%)**        | **32 M** | **45 M** | **63 M** |

Funders earn around **2.1% per loan cycle** on the money they lend, which is what keeps the pool funded. Paylater also lifts GMV (shoppers buy more when they can pay later), which grows the escrow fee as well.

#### Business impact

* **Trust through transparency.** Every payment, fee and donation can be checked on-chain.
* **Retention.** The e-wallet, donations and community savings keep users coming back beyond a single purchase.
* **Easier discovery.** EVA, the AI assistant, helps shoppers find products.
* **One connected ecosystem** linking consumers, merchants, communities and donation institutions, with revenue from transaction fees, merchant partnerships and institutional partnerships.

### Why this model fits

The trust layer isn't a cost center. Because every fee is on-chain, E-Trace can prove what it earns, which is a selling point for both users and investors.
