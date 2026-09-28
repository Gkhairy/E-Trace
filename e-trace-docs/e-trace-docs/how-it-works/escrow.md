# Escrow

Escrow is the core of E-Trace. It is handled by the [PaymentGatewayV3](../smart-contracts/payment-gateway-v3.md) contract.

### One escrow per item

A cart can hold items from several sellers. When the buyer pays, the contract pulls the cart total in **one** TLKM transfer, then records each item separately under the key `keccak256(orderId, index)`:

```
Item { buyer, seller, amount, productId, createdAt, status }
status: Paid → Completed | Refunded | Disputed
```

That means a buyer can confirm one seller's item and dispute another's in the same order. Each seller's money is kept apart.

### The rules are in the contract

| Action           | Who can call it | When                                  | Result                                           |
| ---------------- | --------------- | ------------------------------------- | ------------------------------------------------ |
| `confirmItem`    | buyer           | item is `Paid`                        | seller gets amount − fee; status `Completed`     |
| `refundItem`     | buyer           | item is `Paid` and 3 days have passed | buyer gets 100%; status `Refunded`               |
| `disputeItem`    | buyer           | item is `Paid`                        | status `Disputed`; only the arbiter can close it |
| `arbiterRelease` | arbiter         | item is `Paid` or `Disputed`          | seller gets amount − fee                         |
| `arbiterRefund`  | arbiter         | item is `Paid` or `Disputed`          | buyer gets 100%                                  |

Nobody else can move the money, the platform included. The arbiter can only choose between the buyer and the seller of that item; it cannot send funds anywhere else.

### The fee is fixed and visible

* The fee (`feeBps`, 100 = 1%) and its recipient are **immutable**. They are set when the contract is deployed and cannot be changed later. The contract refuses anything above 10%.
* The fee is only taken on release. Refunds are always in full.
* Every `ItemCompleted` event records the seller amount, the fee, and whether a supervisor decided it.

### The token is locked too

The contract only accepts TLKM. The token address is set at deploy time, and callers cannot pass a different token, so nobody can "pay" with a worthless look-alike.

### The server does not trust the browser

After a payment, the backend reads the order back **from the contract** (buyer, seller, amounts, status) before it marks anything as paid. A tampered request from the browser cannot fake a payment. An indexer follows confirmations: 1 confirmation counts as paid, 3 as final on testnet.
