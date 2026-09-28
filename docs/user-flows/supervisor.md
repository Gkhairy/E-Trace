# Supervisor flow

Supervisors are the platform's moderators. On-chain, the supervisor wallet is the **arbiter** of the escrow contract and the **validator** of the donation pool.

## Overview page

The supervisor dashboard starts with three queues: open **disputes**, orders the AI put **on hold**, and active **insurance claims**. Each queue shows how many items are waiting, how much TLKM is at stake, and the three oldest. Below that are 30-day volume, escrow status, keeper outcomes (auto-release, auto-refund, manual, held) and how confident the AI's decisions were.

## Resolve disputes and held orders

When a buyer opens a dispute, or the [AI keeper](../how-it-works/ai-settlement.md) is not confident enough to act, the item lands in the supervisor queue. The supervisor reviews the tracking history and the AI's reasoning, then either:

* **Release** to the seller (`arbiterRelease`, minus the 1% fee), or
* **Refund** the buyer (`arbiterRefund`, full amount).

Both calls emit an event with `resolvedByArbiter = true`, so the public can tell a supervisor decision apart from a buyer confirmation.

## Review disaster campaigns

The [Disaster Radar](../how-it-works/disaster-radar.md) sends medium-severity events to a review queue. The supervisor can open a donation campaign for one with a single click, or reject it. Supervisors can also paste a news link and have the AI assess it on the spot.

## Manage banner ads

Supervisors manage the banner carousel on the home page: upload an image or paste an image URL, set a link and an order, and turn each banner on or off. See [Business model](../project/business-model.md).
