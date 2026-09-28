# Disaster Radar

When a disaster hits, donation drives usually start days later, and donors can't see where the money ends up. The Disaster Radar finds real disasters in Indonesia early and opens a donation campaign whose every payment is on-chain.

### Sources

Once a day the radar collects events from:

* **BMKG**, Indonesia's earthquake feed (only magnitude 5.0 and above),
* **GDACS**, the UN's global disaster alerts (Green-level alerts are skipped),
* **Google News** in Indonesian from the last day, searching for fires, floods, landslides, earthquakes, eruptions, whirlwinds and tsunamis.

Events older than 7 days, duplicates, and news that is obviously not a disaster (drills, seminars, early warnings) are filtered out before any AI is used.

### AI assessment

An LLM reads each remaining event and decides whether it is a real disaster, with a severity score from 0 to 100 and a reason.

| Score                       | What happens                                                                                       |
| --------------------------- | -------------------------------------------------------------------------------------------------- |
| 70 or more                  | A 30-day campaign opens automatically, paying out to the E-Trace donation wallet (at most 3 a day) |
| 45 to 69                    | Goes to the supervisor queue; opening it takes one click                                           |
| Below 45, or not a disaster | Rejected, and the reason is kept                                                                   |

Supervisors can also paste a news link and have the AI assess it on the spot.

### Why it's on-chain

Donations go to the [DonationPool](../smart-contracts/other-contracts.md#donationpool) contract under that campaign's ID. The public can see every donation and every disbursement, including which wallet received the money. See [Donate](../user-flows/donate.md).
