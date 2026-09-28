# Donate

Donations go through the [DonationPool](../smart-contracts/other-contracts.md#donationpool) contract. E-Trace charges **0% fee** on donations.

## Give to a campaign

1. Open **Donate** and pick a campaign. Some are opened by the team, some by the [Disaster Radar](../how-it-works/disaster-radar.md) when a real disaster is detected.
2. Enter an amount in TLKM and confirm with your PIN or wallet.
3. Your TLKM goes into the pool under that campaign. The contract keeps three numbers per campaign: total raised, balance waiting, and total disbursed.

## How money reaches the recipient

Only the campaign **validator** can move money out, and only by sending the whole campaign balance to a recipient wallet. Each disbursement is a `Disbursed` event with the campaign, the recipient, the amount and who sent it.

So anyone can check, for any campaign:

* how much came in and from which wallets,
* how much went out, to which wallet, and when,
* what is still waiting.

## Community wallets

Groups (a family, a club, an office) can also pool TLKM together:

* **Multisig wallet:** any member can propose a transfer, and it only runs once enough members approve.
* **Allowance wallet:** the owner gives each member a monthly spending limit.

See [Other contracts](../smart-contracts/other-contracts.md) for details.
