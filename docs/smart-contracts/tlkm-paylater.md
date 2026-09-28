# TlkmPaylater

**Address:** [`0x69D8f32F7e918061f32F26Ad7302906Cd857FCc1`](https://testnet.bscscan.com/address/0x69D8f32F7e918061f32F26Ad7302906Cd857FCc1) · **Source:** [`contracts/TlkmPaylater.sol`](https://github.com/Gkhairy/E-Trace/blob/master/contracts/TlkmPaylater.sol)

A two-sided lending pool: funders supply TLKM on fixed terms, and borrowers borrow TLKM against tBNB collateral. For the user view, see [Paylater](../user-flows/paylater.md).

## Parameters (testnet demo)

| Parameter | Value |
| --- | --- |
| `rate` | 1,000,000 TLKM per 1 tBNB of collateral |
| `interestBps` | 300 (flat 3% per loan; the contract caps it at 50%) |
| `duePeriod` | 604,800 seconds (7 days) |
| Terms | 0 = flexible (60% to funders), 1 = 30 days (70%), 2 = 90 days (85%) |

## Accounting

The contract keeps this invariant at all times:

```
liquidity + totalBorrows == Σ bucketAssets + platformReserve
```

Each term is a bucket with its own shares. The value of one share is `bucketAssets / bucketShares` and goes up whenever borrowers pay interest. The platform's part of the interest goes to `platformReserve`.

## Functions

**Funders**

| Function | Description |
| --- | --- |
| `supply(term, amount)` | Deposit TLKM into a term bucket and receive shares |
| `withdrawSupply(term, shares)` | Burn shares for TLKM. Early exit from a fixed term returns principal only. |
| `supplierInfo(user, term)`, `supplierValue(user, term)` | Read a position |

**Borrowers**

| Function | Description |
| --- | --- |
| `depositCollateral()` | Send tBNB as collateral (payable) |
| `creditLimit(user)` | `collateral × rate` |
| `borrow(amount)` | Borrow up to the limit if the pool has enough liquidity; resets the due date |
| `repay(amount)` | Repay in full or in part; interest is split between funders and the platform |
| `withdrawCollateral(amount)` | Only once the debt is fully repaid |
| `positionOf(user)` | Read a loan |

**Owner**

`seize(user)` for overdue loans, `ownerSeedMint` to seed liquidity, `withdrawReserve`, and setters for terms, rate, interest and due period.

## Events

`Supplied`, `SupplyWithdrawn`, `Deposited`, `Borrowed`, `Repaid`, `Withdrawn`, `Seized`, `OwnerSeeded`, `ReserveWithdrawn`.
