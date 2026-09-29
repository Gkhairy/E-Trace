# E-Trace

**Every payment held in escrow, every transaction on-chain. Don't trust. Trace.**

E-Trace is a marketplace on BNB Smart Chain where the buyer's money never sits in the platform's bank account. It sits in a smart contract, and it only moves to the seller once the buyer confirms the goods arrived. Anyone can check any payment on the block explorer.

* **App:** [e-trace.shop](https://e-trace.shop)
* **Source code:** [github.com/Gkhairy/E-Trace](https://github.com/Gkhairy/E-Trace)
* **Demo video:** [E-Trace | The Transparent Blockchain Marketplace](https://youtu.be/QE7p25uFDSw)
* **Network:** BNB Smart Chain Testnet (chain ID 97)

## The problem

On a normal marketplace the platform holds the buyer's money until the order is done. Buyers have to trust the platform, sellers wait for payouts, and nobody outside the company can see where the money is. When something goes wrong, the platform decides, and there is no public record of why.

## What E-Trace does differently

| On a normal marketplace | On E-Trace |
| --- | --- |
| The platform holds the money | A smart contract holds the money, per item |
| The balance is a number in a database | The balance is a token on a public chain |
| Disputes are decided behind closed doors | Every release, refund and dispute is an on-chain event |
| Fees are whatever the platform says | The 1% fee is fixed in the contract and shown in every release event |

On top of escrow, E-Trace adds a small on-chain financial system:

* [**Paylater**](user-flows/paylater.md): borrow against tBNB collateral, or earn by funding the lending pool.
* [**AI auto-settlement**](how-it-works/ai-settlement.md): a keeper reads shipment tracking and releases or refunds stuck orders.
* [**On-Time Guarantee**](how-it-works/on-time-guarantee.md): parametric shipping insurance that pays out automatically when a parcel is late.
* [**Disaster Radar**](how-it-works/disaster-radar.md): AI watches real disaster feeds and opens donation campaigns with on-chain disbursements.
* [**Transparency Explorer**](how-it-works/explorer.md): a public dashboard of volume, escrow and token transfers.

## Where to start

* Want to try it? Go to [Try E-Trace in 5 minutes](getting-started/try-e-trace.md).
* Want to see the contracts? Go to [Contract addresses](smart-contracts/addresses.md).
* Want the business case? Go to [Business model](project/business-model.md) and [Roadmap](project/roadmap.md).

{% hint style="warning" %}
E-Trace runs on **testnet** with test tokens. No real money is involved, and the contracts have not been audited yet.
{% endhint %}
