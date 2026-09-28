# PaymentGatewayV3

**Address:** [`0x76e783878E4d88e435c4296F87c56446be72FDc9`](https://testnet.bscscan.com/address/0x76e783878E4d88e435c4296F87c56446be72FDc9) · **Source:** [`contracts/PaymentGatewayV3.sol`](https://github.com/Gkhairy/E-Trace/blob/master/contracts/PaymentGatewayV3.sol)

Multi-seller escrow for TLKM. For the idea behind it, see [Escrow](../how-it-works/escrow.md).

## Constructor

```solidity
constructor(address tlkm, address feeRecipient, uint256 feeBps, address arbiter)
```

| Parameter | Meaning |
| --- | --- |
| `tlkm` | The only token the contract accepts (immutable) |
| `feeRecipient` | The platform wallet that receives fees (immutable) |
| `feeBps` | Fee in basis points: 100 = 1%, maximum 1000 = 10% (immutable) |
| `arbiter` | The supervisor wallet that can settle items; the owner can rotate it |

## Functions

| Function | Caller | Description |
| --- | --- | --- |
| `payCart(sellers[], amounts[], productIds[], orderId)` | buyer | Pulls the total once and opens one escrow per item. Needs a TLKM approval first. Each `orderId` can be used only once. |
| `confirmItem(orderId, index)` | buyer | Releases the item to the seller, minus the fee |
| `refundItem(orderId, index)` | buyer | Full refund, allowed 3 days (`REFUND_TIMEOUT`) after payment |
| `disputeItem(orderId, index)` | buyer | Freezes the item for the arbiter |
| `arbiterRelease(orderId, index)` | arbiter | Releases a paid or disputed item to the seller, minus the fee |
| `arbiterRefund(orderId, index)` | arbiter | Refunds a paid or disputed item in full |
| `setArbiter(newArbiter)` | owner | Rotates the arbiter wallet |
| `getItem(orderId, index)` | anyone | Reads one escrow item |

## Events

| Event | Emitted when |
| --- | --- |
| `PurchaseItem(orderId, index, buyer, seller, amount, productId, timestamp)` | an item is paid into escrow |
| `ItemCompleted(orderId, index, seller, sellerAmount, fee, resolvedByArbiter)` | money is released to the seller |
| `ItemRefunded(orderId, index, buyer, amount, resolvedByArbiter)` | money is refunded to the buyer |
| `ItemDisputed(orderId, index, buyer, timestamp)` | the buyer opens a dispute |
| `ArbiterChanged(oldArbiter, newArbiter)` | the arbiter is rotated |
