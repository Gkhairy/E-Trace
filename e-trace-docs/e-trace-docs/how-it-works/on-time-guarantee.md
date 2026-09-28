# On-Time Guarantee

The On-Time Guarantee is **parametric shipping insurance**. The buyer doesn't file a claim. If the parcel is late and it's the seller's or courier's fault, the payout happens automatically.

### How it works

1. At checkout the buyer can add the guarantee for a small premium (**2 TLKM** by default).
2. E-Trace calculates an ETA from the **distance** between the seller's city and the buyer's city, then sets a promised date: `ETA + 3 days buffer`.
3. The [keeper](ai-settlement.md) checks insured orders every day. An order counts as late only once it is past `promised date + 2 days grace`.
4. If DeliveryAI finds the delay was caused by the seller or the courier, the insurance pool refunds the **shipping cost** to the buyer.

### Limits

| Setting               | Default                                        |
| --------------------- | ---------------------------------------------- |
| Premium               | 2 TLKM                                         |
| Payout                | the shipping cost, capped at 30 TLKM per claim |
| Daily circuit breaker | 500 TLKM paid out per day in total             |
| Payouts per order     | at most one                                    |

Premiums and payouts go through a platform-held pool wallet, and every payout is a normal TLKM transfer that anyone can see on BscScan.

{% hint style="info" %}
The guarantee is off by default and only turns on once a pool wallet is configured.
{% endhint %}
