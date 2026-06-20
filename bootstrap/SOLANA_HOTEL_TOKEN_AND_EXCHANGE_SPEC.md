# Solana Hotel — $HOTEL Token & Credit Exchange Spec

> **Purpose:** This document covers ONLY the on-chain crypto layer: the $HOTEL
> token integration, the Credit Exchange atomic swap (Marketplace Section B),
> and the automated treasury burn. It is deliberately separate from the main
> Economy & Marketplace spec. The two documents are companions but should not be
> merged.

---

## ⚠️ READ FIRST (instructions for Claude Code)

**Do NOT start building yet.** This layer is the highest-risk part of the project
because it crosses the boundary between on-chain (real tokens) and off-chain
(database Credits). Before writing code:

1. **Analyze** what wallet/Solana infrastructure already exists in the project
   (Phantom auth is already built; check for any existing Solana RPC config,
   web3.js / @solana/web3.js usage, SPL token handling, treasury config).
2. **Confirm the open decisions** in the "DECISIONS TO CONFIRM" section below
   with the owner before implementing.
3. **Propose an implementation plan** and wait for owner approval.

**Funds must never be lost.** Every design choice here prioritizes safety over
convenience. When in doubt, fail safe (no Credits released, no tokens moved).

---

## 0. The $HOTEL Token — owner-provided facts

**The owner is creating the $HOTEL token manually.** Claude Code does NOT create,
mint, or deploy the token. The owner will supply all required on-chain details.

Fields the owner will provide (fill in when ready):

| Field | Value (owner to provide) |
|---|---|
| Token name | $HOTEL |
| Mint / contract address | `__________________________` |
| Token decimals | `____` (SPL tokens are commonly 6 or 9) |
| Treasury wallet address (receives fee) | `__________________________` |
| Network | Solana **mainnet** (confirm) |
| Preferred RPC endpoint | `__________________________` (e.g. a paid RPC for reliability) |
| Access-gate minimum hold | **1,000 $HOTEL** (admin-configurable, default 1,000) |

> Until the mint address exists, the Credit Exchange (Section B) and access gate
> cannot go live. Everything in this doc is built against these values once provided.

---

## 1. DECISIONS (resolved by owner) + remaining items

**RESOLVED:**

1. **Fee percentage = 5%.** Across the marketplace, buyer pays 100%, seller
   receives 95%, treasury receives 5%. (Matches Kintara's 95/5 split.) This
   applies to the Credit Exchange ($HOTEL fee → treasury → burned) and to the
   Credits-only sections (5% Credits burned), per the main spec. Both documents
   now agree on 5%.

2. **Access gate = 1,000 $HOTEL, admin-configurable (default 1,000).** Owner
   policy is to keep it at 1,000 permanently (early-adopter advantage / entry
   urgency). Implement the threshold as an **admin setting that defaults to 1,000**
   so it *can* be changed from the admin panel if ever needed, without a code
   change. Policy = 1,000 always; mechanism = flexible.

3. **Burn cadence = batched.** Treasury accumulates fee tokens and burns on a
   threshold or schedule (see Section 4), rather than per-transaction. Keeps swaps
   fast and minimizes tx-fee waste.

**STILL TO CONFIRM (when reaching the crypto build):**

4. **Burn key custody** — automated burns need the treasury key available to a
   backend signer. Owner to choose the custody approach in Section 4
   (recommendation: dedicated burn wallet holding only fees).

5. **RPC provider** — a reliable (ideally paid) Solana RPC endpoint for
   confirmation polling and burns. Public endpoints rate-limit and can stall swap
   confirmations. Owner to provide.

---

## 2. Marketplace Section B — Credit Exchange (Credits → $HOTEL)

A seller lists Credits for sale at a fixed $HOTEL rate. A buyer holding $HOTEL
buys them. This is the **only** place Credits convert to crypto (the cash-out
chokepoint) and the **only** trade that crosses on-chain.

### Non-custodial principle
$HOTEL moves **directly buyer → seller wallet**. The platform NEVER holds the
seller's proceeds. The platform only controls **Credits** (database), which it
releases *after* confirming the on-chain payment. The only tokens the platform's
treasury ever receives are the **fee portion**, which is later burned (Section 4).

### Atomic swap sequence (must prevent any fund loss)

```
1. Buyer clicks "Buy {X} Credits for {Y} $HOTEL".

2. Backend LOCKS the listing:
   - status → pending
   - lock timeout ~2 minutes
   - prevents two buyers purchasing the same listing simultaneously.

3. Backend builds ONE Solana transaction containing both transfers:
   - (Y − fee) $HOTEL  → seller wallet
   -      fee  $HOTEL  → treasury wallet
   Both transfers in a single atomic tx (both land or both fail).

4. Buyer signs the transaction in Phantom.

5. Transaction submitted to Solana.

6. Backend POLLS the tx signature until FINALIZED
   (not merely "submitted" — wait for on-chain finalization).

7a. CONFIRMED:
    - release {X} Credits to buyer (DB transaction)
    - mark listing sold, delete it
    - notify seller (inbox)
    - write marketplace_logs + credit_exchange_swaps rows (with tx signature)

7b. FAILED or TIMEOUT:
    - release the lock
    - NO Credits move
    - listing returns to active
    - log the failed attempt
    - nothing is lost
```

### Safety invariants (must hold at all times)
- **Credits release ONLY after on-chain finalization.** Never optimistically.
- **The listing is locked during the swap** so the same Credits can't be sold twice.
- **The fee + payment are in one signed tx** → they cannot partially execute.
- **Any failure path moves zero Credits** and simply reactivates the listing.
- **The platform never custodies seller proceeds** — only the fee reaches treasury.

### Edge cases to handle explicitly
- Buyer wallet has insufficient $HOTEL → tx fails at sign/submit → fail-safe path.
- Buyer abandons after lock → lock timeout expires → listing reactivates.
- RPC stalls / can't confirm within timeout → treat as timeout → fail-safe;
  if the tx *later* confirms, reconcile via the `credit_exchange_swaps` audit row
  (a reconciliation job should detect a confirmed-but-unreleased swap and release
  the Credits, or flag for manual review). **This reconciliation path is important
  — design it, don't skip it.**
- Buyer also must meet the **access gate** (hold ≥ 1,000 $HOTEL) to participate.

---

## 3. Access Gate (admin-configurable, default 1,000 $HOTEL)

- The threshold is an **admin setting**, default **1,000 $HOTEL**. Owner policy is
  to keep it at 1,000 permanently; the setting exists only so it can be adjusted
  from the admin panel without a code change if ever required.
- On login (and periodically / before gated actions), query the wallet's $HOTEL
  balance via RPC.
- If balance ≥ threshold → access + faucets active.
- If balance < threshold → access denied / faucets paused (per main spec).
- Cache balance briefly to avoid hammering RPC on every request, but re-check
  before sensitive actions (entering game, cashing out).

---

## 4. Automated Treasury Burn

The treasury wallet receives the fee portion of every Credit Exchange swap. These
$HOTEL tokens are **burned automatically** (sent to the SPL burn / removed from
supply), making $HOTEL deflationary.

### Burn mechanism
SPL tokens are burned using the standard SPL `burn` instruction (reduces the
mint's total supply), signed by the treasury wallet that holds the tokens. (This
is a true supply reduction — preferable to sending to an inert address.)

### Recommended cadence — batched
Per-transaction burns are simple but waste SOL on tx fees and add latency to each
swap. **Recommended:** accumulate fee tokens in the treasury and burn on a
**threshold or timer**, e.g.:
- Burn when treasury fee balance ≥ N $HOTEL, **or**
- Burn on a fixed schedule (e.g. hourly/daily cron),
- whichever the owner prefers.

This keeps the swap path fast (the swap just delivers fee to treasury; burning is
a separate async job) and minimizes transaction costs.

### Key custody — owner must choose (security-critical)
Automated burns require signing with the treasury key from a backend process.
Options, safest first:

- **(A) Dedicated burn wallet, minimal balance.** Fees flow to a wallet that holds
  *only* fee tokens (never large reserves). Its key signs only burns. If
  compromised, exposure is limited to un-burned fees. **Recommended.**
- **(B) Separate signer service / KMS.** Key held in a secrets manager / HSM /
  cloud KMS; backend requests signatures without the raw key on the app server.
  More robust, more setup.
- **(C) Manual burn approval.** Backend prepares the burn; owner approves/signs
  periodically. Safest (no automated key) but not "automatic." A reasonable
  interim.

> **Do NOT place a treasury private key holding significant funds directly in
> application code or .env.** Use (A) at minimum. The burn wallet should never
> hold more than recently-accrued, soon-to-be-burned fees.

### Burn logging
Every burn writes an auditable record:
```
treasury_burns
--------------
id, amount_burned, tx_signature, triggered_by (threshold/schedule/manual),
treasury_balance_before, treasury_balance_after, created_at
```
Combined with `credit_exchange_swaps`, this proves: fees in (per swap) = fees
burned (over time), fully traceable on-chain via signatures.

---

## 5. Logging (crypto-layer specific)

Reuses / complements the main spec's `marketplace_logs`. Crypto-specific tables:

### `credit_exchange_swaps` (on-chain audit trail for every swap)
```
id, listing_id, buyer_wallet, seller_wallet,
hotel_to_seller, hotel_to_treasury, tx_signature,
tx_status (pending/confirmed/failed), credits_released (bool),
confirmed_at, created_at
```

### `treasury_burns` (see Section 4)

### `wallet_balance_checks` (optional, for access-gate auditing / anti-farming)
```
id, wallet_address, hotel_balance, passed_gate (bool), checked_at
```

Every on-chain action is traceable to a Solana tx signature. The audit tables let
the owner prove no funds were lost and reconcile any stalled swap.

---

## 6. Build Order (crypto layer — refine after analysis)

1. Analyze existing Solana/wallet infra; confirm the DECISIONS TO CONFIRM. **Wait for approval.**
2. Owner provides token facts (mint address, decimals, treasury, RPC).
3. Solana RPC integration + balance reads (powers access gate).
4. Access gate (1,000 $HOTEL) wired into login / gated actions.
5. Credit Exchange atomic swap (lock → build tx → sign → confirm → release) + audit tables.
6. Reconciliation job for stalled/confirmed-but-unreleased swaps.
7. Automated treasury burn (batched) + key-custody approach + burn logging.

> Gates: nothing here goes live until the $HOTEL mint exists. Items 4–7 depend on
> the owner-provided token facts in Section 0.

---

## 7. Dependencies on the main spec

- The Credit Exchange is **Marketplace Section B** in the main spec; this document
  is the detailed/safety design for it. The Item Market (A) and Auction House (C)
  are Credits-only and fully covered by the main spec (no crypto, no escrow risk).
- The access gate is referenced in the main spec's anti-farming section; the
  balance-check mechanism lives here.
- Confirm the fee % (Decision #1) so both documents agree. **RESOLVED: 5%** in
  both documents.

---

*End of $HOTEL token & Credit Exchange spec. Analyze and confirm decisions before
building. Fail safe: if anything is uncertain at runtime, move zero funds.*
