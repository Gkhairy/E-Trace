# AI auto-settlement

Buyers forget to press "Confirm received". Sellers sometimes never ship. Without help, that money would sit in escrow forever. The **settlement keeper** is a scheduled job that closes these orders fairly, and every decision it makes is written down.

## Step 1: fixed rules first, no AI

| Rule | Default | Action |
| --- | --- | --- |
| Seller hasn't shipped within N days | 3 days | refund the buyer |
| Parcel delivered but buyer hasn't confirmed within M days | 3 days | release to the seller |
| Destination is far (ETA ≥ 10 days) | — | skip auto-release and give the buyer more time |

## Step 2: the AI reads the tracking

For orders the rules don't settle, the keeper sends the shipment's tracking history to **DeliveryAI** (an LLM, `gpt-4o-mini`). It answers with one of `release`, `refund` or `hold`, a confidence score and a short reason.

The keeper only acts on its own when **both** of these are true:

* confidence is at least **0.8**, and
* the order is worth at most **1,000 TLKM**.

Anything else is marked **held** and goes to the [supervisor queue](../user-flows/supervisor.md) with the AI's reasoning attached.

## Step 3: execute on-chain as the arbiter

The keeper signs `arbiterRelease` or `arbiterRefund` with the platform's arbiter key. The resulting event has `resolvedByArbiter = true`, and the AI's decision and reason are stored with the order for auditing.

## Safety

* **Idempotent:** an order is only settled while its status is `pending`, so running the keeper twice cannot pay twice.
* **Safe when off:** if the arbiter key isn't configured, the AI still records its decision but no money moves.
* The keeper runs once a day.
