# Security and audit status

{% hint style="warning" %}
E-Trace is a **testnet prototype**. The smart contracts have had an internal review but **no external audit** yet. Do not send real funds to these contracts.
{% endhint %}

## What's already in place

**Contracts**

* `ReentrancyGuard` on every function that moves funds, and `SafeERC20` for token transfers.
* The payment token and the fee are **immutable** in the escrow contract, and the fee is capped at 10%.
* Escrow is kept separate per item, so one bad item can't affect another.
* The arbiter can only release to the item's seller or refund to its buyer. It can't send funds anywhere else.
* Paylater enforces an accounting invariant between liquidity, loans and shares.

**Application**

* The backend reads payments back from the chain and never trusts the browser.
* AI actions are limited by confidence (≥ 0.8) and order size (≤ 1,000 TLKM); everything else goes to a person.
* Insurance payouts have a per-claim cap and a daily circuit breaker.
* The keeper is idempotent: each order is settled at most once.
* Secrets (arbiter key, pool key, API keys) live only in environment variables, and the server checks at boot that they're present without printing them.
* Turnstile, rate limits, OTP and optional 2FA protect accounts.

## Known limitations

* **Custodial embedded wallets.** For PIN users, the server signs on the user's behalf. That's easier for newcomers but means trusting the platform with those keys.
* **A single arbiter.** One platform wallet settles disputes. We plan to move to multisig or community arbitration.
* **No price oracle in Paylater.** The collateral rate is a fixed demo parameter and there is no market liquidation.
* **Simulated tracking.** On testnet, shipment tracking events are simulated. They don't come from real courier APIs yet.

## Audit status

| Step | Status |
| --- | --- |
| Internal contract review with [Pashov's solidity-auditor skills](https://github.com/pashov/skills) (3 passes, multiple specialised agents) | ✅ Done: 18 findings, acknowledged |
| Web application security review (parallel agents per area) | ✅ Done |
| Fix critical and high app findings: file-upload handling, insurance premium verification, client-IP spoofing, wallet-registration nonce (SIWE) | ✅ Fixed |
| Fix contract findings (escrow refund timing, multisig stale approvals, Paylater accounting) | ⏳ Before mainnet, in the next contract version |
| External audit | ⏳ Before mainnet. See the [Roadmap](roadmap.md) |

Contract findings are on testnet only, where no real money is at stake. They are fixed in the next contract version rather than patched live, because deployed contracts can't be changed in place.

Found a vulnerability? Please open a private security advisory on the [GitHub repository](https://github.com/Gkhairy/E-Trace/security) instead of a public issue.
