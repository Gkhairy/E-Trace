# Transparency Explorer

The Explorer at [e-trace.shop/explorer](https://e-trace.shop/explorer) is public. You don't need an account to see it.

### What it shows

* **Totals:** trading volume, number of transactions, and how much TLKM is held in escrow right now.
* **Activity chart:** volume and transactions over the last 14 days.
* **Escrow status:** a breakdown of items that are paid, completed, refunded or disputed.
* **Top stores** and **verified entities**.
* **TLKM transfers:** a live feed of token transfers read from the chain's `Transfer` events.

### Where the data comes from

Two indexers run every minute:

* one follows escrow events from PaymentGatewayV3 and tracks their confirmations,
* one reads TLKM `Transfer` logs through a public RPC node.

Every row links to the transaction on [BscScan Testnet](https://testnet.bscscan.com), so you never have to take E-Trace's numbers on faith. You can check them yourself.
