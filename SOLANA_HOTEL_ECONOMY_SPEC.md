# Solana Hotel — Economy & Marketplace Specification

> **Purpose of this document:** This is the master design spec for Solana Hotel's
> Credit economy, daily wheel, anti-farming defenses, and three-section marketplace.
> Every decision here was deliberately made by the project owner. Do not redesign
> the economics — implement them as specified.

---

## ⚠️ READ FIRST — How to use this document (instructions for Claude Code)

**Do NOT start building yet.** Before writing any code, do the following and report back:

1. **Analyze the existing codebase** (AtomCMS-Sadie + Sadie emulator + shared `solanahotel` DB) and identify what *already exists* that maps to the mechanics below. Specifically check for:
   - Existing currency/balance columns (e.g. `player_data.credit_balance`, `website_balance`, ducket/diamond/points columns)
   - Existing catalog / shop system and how items are categorized by origin (Credit Shop vs Club/Rare/LTD/Event)
   - Existing inventory / item ownership tables
   - Existing in-game trade system (player ↔ player)
   - Existing messaging/inbox system (for notifications)
   - Existing daily reward / quest / achievement systems, if any
   - Existing wallet integration (already built: Phantom auth, `wallet_address` on `players`)
   - Any existing marketplace, auction, or shop-voucher tables

2. **Produce a breakdown** mapping each mechanic in this spec to one of:
   - ✅ **Already exists** — can be used as-is
   - 🔧 **Exists, needs extension** — describe what changes
   - 🆕 **Must be built from scratch** — describe the approach

3. **Propose an implementation plan** (ordered, with dependencies) and **wait for owner approval** before building. The owner wants room to review and adjust before construction, to avoid building things from scratch that already exist.

The goal: **reuse and extend the existing game wherever possible. Don't rebuild what's already there.**

---

## 1. Currency Model

Two in-game economies plus the crypto layer:

| Currency | Type | Role |
|---|---|---|
| **Credits** | In-game (database) | Primary grind currency. Earned by play, spent on catalog/rooms/cosmetics. Base item price = 5 credits (tunable). |
| **$HOTEL** | On-chain SPL token (to be created) | Premium/value layer. Access gate, marketplace settlement, premium shop (burned), event rewards. |
| **SOL** | On-chain | Real-money rails: HC membership, premium shop alternative. |

**Token creation:** $HOTEL does not exist yet and must be created on Solana mainnet. Supply design is a separate task to be done before any $HOTEL-dependent feature goes live.

**Access gate:** A wallet must hold a **minimum of 1,000 $HOTEL** to play
(admin-configurable, default 1,000; owner policy is to keep it at 1,000
permanently). Faucets pause if a wallet drops below this threshold.

---

## 2. The Credit Economy — Faucets (where Credits enter)

All faucets are **capped daily** and reset every 24h. All caps are multiplied by **1.5× for Membership users**.

| Faucet | Base earning | Base daily cap | Member cap (×1.5) |
|---|---|---|---|
| Daily login | 50 flat | 50 | 75 |
| Daily quests | 10 each (6 quests) | 60 | 90 |
| Active time | 10 per 30 min active | 30 | 45 |
| Room visitors | 4 per unique visitor | 20 | 30 |
| Visit other rooms | reward for visiting rooms | 20 | 30 |
| Streak bonus | 10/day, climbs to 5-day streak | 50 | 75 |
| **Base total** | | **230/day** | **345/day** |

**Streak detail:** +10/day each consecutive day, capping at +50/day on a 5-day streak. The +50 maintains daily as long as the streak is unbroken. Missing a day resets to +10 and climbs again (10→20→30→40→50).

**Active time detail:** Must require genuine activity (avatar movement / interaction), not just an open connection, to resist idle-farming.

**Multiplier logic (apply to BOTH earned amount and cap):**
```
multiplier = has_membership ? 1.5 : 1.0
earned_today = min(raw_earned * multiplier, faucet_cap * multiplier)
```

**New-player starting grant:** 100 Credits, granted only after the wallet's first qualifying state (verified wallet meeting the access gate) — NOT on bare registration, to resist farming.

---

## 3. The Daily Wheel

One free spin per day. Separate from faucet caps. **Members get better odds.**

| Reward | Regular probability | Member probability |
|---|---|---|
| 10 credits | 35% | 25% |
| 25 credits | 30% | 28% |
| 50 credits | 20% | 25% |
| 100 credits | 12% | 17% |
| Rare Item Box | 3% | 5% |

- Regular average ≈ **33 credits/spin**; Member average ≈ **39 credits/spin**.
- **No CAPTCHA** on the wheel (owner decision).

**Rare Item Box** (when won, rolls its own internal table):

| Box contents | Probability |
|---|---|
| Uncommon rare item | 60% |
| Rare item | 30% |
| Very rare item | 9% |
| Legendary (seasonal exclusive) | 1% |

- The item from the box is **tradeable** on the marketplace.
- The box pulls from a **rotating pool** of items (rotated seasonally) so no single item floods the economy and older items become genuinely scarce when retired.

---

## 4. The Credit Economy — Sinks (where Credits leave / are burned)

Design principle: **sinks ≥ faucets**. Engaged players should burn ~85%+ of income. Track daily `net_change = minted − burned` and tune prices to keep it near zero.

| Sink category | Examples & prices | Effect |
|---|---|---|
| Catalog (primary) | Basic 5 / Standard 15 / Premium 40 / Deluxe 100 | Credits destroyed (leave economy) |
| Rooms | Extra slot 250 / size upgrade 500 / theme 750 / music 300 | Destroyed |
| Cosmetics | Name color 150 / glow 400 / walk effect 600 / clothing 200–800 / motto 100 | Destroyed |
| Marketplace tax | 5% of Credit trades | **Burned** |
| Crafting/recycling | 5 items + 50 credits → 1 better item; rare→legendary 500+materials | Burns items AND credits |

All prices are tunable starting points, not final.

---

## 4b. Shop Structure (three shops, distinct currencies)

The hotel has **three separate shops**, each with a clear role and currency. The
guiding principle: **players pay real money for status and convenience, but grind
for the rare items that define the economy.** Nothing power/economy-defining is
buyable with real money — only cosmetics and (later) convenience.

| Shop | Stock | Currency | Role |
|---|---|---|---|
| **Credit Shop** (Regular) | Basic everyday furniture | **Credits** | The primary sink. Items here are NOT listable on the marketplace (per marketplace rule). |
| **Rare Shop** | Rare items | **Higher Credit amounts** | The grind goal. Rares stay *earned*, keeping Credits valuable and the grind meaningful. Listable on the marketplace. |
| **Premium Shop** | **Cosmetic-only** items (effects, glows, animations, badges, name styles, exclusive seasonal cosmetics) | **Real SOL** | Where real money enters. Status, never power. SOL → treasury (revenue). |

**Design rules:**
- The **Rare Shop is Credits only** — never real money. This is deliberate: it
  keeps rares as an aspirational grind target and prevents pay-to-win, protecting
  Credit value and the fairness of the economy.
- The **Premium Shop is real SOL**, stocked **only with cosmetics** (status items)
  — exclusive effects, glows, animations, badges, name styles, limited seasonal
  drops. Nothing that affects gameplay balance or the rare-item economy.
- **HC Membership** fits in the Premium Shop tier (paid in SOL).
- **Future (owner, later):** the Premium Shop may also offer **convenience** items
  for SOL (e.g. extra room slots, extra marketplace listing slots, profile
  customization). To be designed later. Convenience = acceptable to monetize;
  power/rares = never.
- **Optional $HOTEL alternative:** the Premium Shop *may* later accept **$HOTEL as
  an alternative to SOL** — SOL payments → treasury (revenue), $HOTEL payments →
  burned (deflationary). Gives players choice and gives the project either revenue
  or token burn per purchase. (See token spec; optional, not required at launch.)

**Why this structure:** free/grinding players can earn every gameplay-relevant
item (including all rares) through play, so the game never feels pay-to-win;
paying players get exclusive cosmetics, status, and convenience. This is the
healthy monetization model — **sell status and convenience, not power.**

---

## 5. Anti-Farming / Anti-Sybil Defenses

Priority order (owner-approved subset):

1. **Access gate** — minimum **1,000 $HOTEL** hold to play (admin-configurable,
   default 1,000). Primary Sybil defense (each alt requires locked capital). ✅ build
2. **Cash-out chokepoint** — Credits can ONLY become $HOTEL via the marketplace Credit Exchange, sold to *real buyers*. There is no direct "cash out" button. In-game player trades are **items only** (no Credit transfers), so Credits can never move untaxed/unlogged between accounts. ✅ build (this is mostly a design constraint)
3. **IP logging + trade logging** — capture IP on registration, login, and every marketplace action; log all trades. Surface suspicious patterns (alt clusters on one IP, circular A→B→C→A trades, funneling to one account, abnormal new-account volume) as a **flagged feed in admin for manual review** (not auto-ban). ✅ build
4. **Behavioral flags** — detect accounts with high login frequency but near-zero genuine interaction (no chat, no room edits, no friends); synchronized batch logins. Surface for review. ✅ build

**Skipped by owner:** on-chain funding-trail analysis, wheel CAPTCHA, reputation/age gating on cash-out, wash-trading conversion caps.

---

## 6. The Marketplace

**Listable items rule:** Any item **NOT** from the Regular Credit Shop may be listed. This means Club Shop, Rare Shop, Limited Edition, and Single/Multi Event items are listable; basic Credit Shop catalog items are not. Enforce this at the data level via item origin/category. Every listing shows an **origin/rarity badge** and the marketplace supports **filtering by origin/rarity**.

**One item = one active listing.** An item can be listed in the Item Market OR the Auction House, never both, never twice. Enforce by moving the item into escrow when listed.

**Max 20 active listings per account.**

**Consistent fee: 5%** on all marketplace transactions (none on in-game trades).

### Section A — Item Market (items → Credits)
- Fixed price, seller-set in Credits.
- Seller may cancel **anytime until purchased** → item returns to inventory.
- On purchase: listing deleted, seller Credits += price, **inbox notification** to seller, item → buyer.
- 5% Credit fee **burned**.
- Listings expire after seller-set / default duration → item auto-returns.
- DB escrow (item held while listed). Fully atomic, safe.

### Section B — Credit Exchange (Credits → $HOTEL)
- Fixed rate, seller-set in $HOTEL.
- Seller may cancel **anytime until purchased**.
- **This is the cash-out chokepoint and the only on-chain-crossing trade.**
- **Atomic swap sequence (critical — must prevent any fund loss):**
  1. Buyer clicks buy → backend **locks listing** (status `pending`, ~2 min timeout) to prevent double-purchase.
  2. Buyer's Phantom builds ONE transaction: 95% $HOTEL → seller wallet, 5% $HOTEL → treasury. Buyer signs.
  3. Tx submitted to Solana.
  4. Backend **waits for on-chain finalization** (polls signature).
  5. **Confirmed →** release Credits to buyer (DB), mark sold, notify seller, log with tx signature.
  6. **Failed/timeout →** release lock, no Credits move, listing reactivates. Nothing lost.
- **Non-custodial:** $HOTEL goes buyer→seller directly; the platform never holds it. Credits release is gated on on-chain confirmation.
- 5% $HOTEL → treasury (burnable).

### Section C — Auction House (items → Credits ONLY)
- **Credits only** (owner decision — avoids on-chain escrow entirely; anchors Credit value).
- Seller sets **fixed starting price** (Credits) and **duration: 6h / 12h / 24h / 48h**.
- **Minimum bid increment: +5 Credits** over current highest bid (prevents 1-credit spam).
- **Anti-snipe:** bids in the final minutes extend the timer (e.g. +5 min).
- **Bid escrow in Credits:** bidder's Credits held; outbid = **instant refund**.
- **Seller cancel ONLY before the first bid** → item returns to inventory. After any bid → locked, no cancel.
- **No bids at expiry →** item auto-returns to seller (scheduled job checks ended auctions every minute).
- **Auction won →** item → winner, seller Credits += final bid, **inbox notification** to both parties, losing bidders refunded.
- Fully DB escrow. Safe, atomic.

### In-game trades (separate from marketplace)
- **Items only**, **0% fee**. No Credit transfers between players (chokepoint integrity).

---

## 7. Notifications & Balance Updates

| Event | Behavior |
|---|---|
| Item Market sale | Listing deleted; seller Credits += price; **inbox notify** seller; item → buyer |
| Credit Exchange sale | Listing deleted; $HOTEL → seller wallet on-chain; buyer Credits += amount **after tx confirmed**; **inbox notify** seller |
| Auction won | Item → winner; seller Credits += final bid; **inbox notify** both; losers refunded |
| Auction expired, no bid | Item auto-returns to seller |
| Outbid | Held Credits instantly refunded; optional "outbid" notification |

Reuse the existing AtomCMS messaging/inbox system for notifications if present.

---

## 8. Logging (organized for full traceability)

### `marketplace_logs`
```
id, event_type (list/buy/cancel/expire/bid/outbid/auction_win),
section (item_market/credit_exchange/auction),
seller_id, buyer_id, item_id, credit_amount, hotel_amount,
fee_amount, fee_currency, seller_ip, buyer_ip,
tx_signature, status (active/pending/completed/cancelled/expired/failed),
created_at, completed_at
```

### `credit_exchange_swaps` (on-chain audit trail — proves no funds lost)
```
id, listing_id, buyer_wallet, seller_wallet,
hotel_to_seller, hotel_to_treasury, tx_signature,
tx_status (pending/confirmed/failed), credits_released (bool),
confirmed_at, created_at
```

Every on-chain swap has a row showing the tx signature (verifiable on Solana explorer), both sides of the money, and whether Credits were released — the guarantee/proof that no funds were lost. IP fields feed the anti-farming suspicious-pattern detection.

### Suggested economy monitoring
```
credit_economy_stats (one row/day):
id, date, total_supply, minted_today, burned_today,
net_change, active_players
```
Watch `net_change` week-over-week; nudge sink prices to keep it near zero (the central-bank lever).

---

## 9. Token / Membership Roles (summary)

| Paid in | Used for |
|---|---|
| **Credits** | Credit Shop (basic), Rare Shop (rares, higher amounts), rooms, in-game cosmetics, crafting, Item Market, Auction House |
| **$HOTEL** | Access gate (hold 1,000), Credit Exchange settlement, optional Premium Shop alternative (burned), event rewards |
| **SOL** | Premium Shop (cosmetic-only items), HC membership, future convenience items |

Membership grants **1.5× faucet caps** and **better wheel odds**. Membership may later be purchasable in SOL and/or Credits (decision pending).

---

## 10. Build Order (proposed — for Claude Code to refine after analysis)

1. **Analyze existing codebase**, produce the reuse/extend/build breakdown, get owner approval.
2. Credits economy foundation: faucets, caps, daily reset, starting grant, transaction logging.
3. Daily wheel (regular + member odds, Rare Item Box, rotating pool).
4. Sinks: wire catalog/rooms/cosmetics/crafting to burn Credits; economy stats tracking.
5. Access gate (1,000 $HOTEL hold check on login).
6. Marketplace Section A (Item Market) + escrow + logging + notifications.
7. Marketplace Section C (Auction House) — Credits-only, bids, anti-snipe, escrow.
8. Marketplace Section B (Credit Exchange) — atomic $HOTEL swap, audit trail. (Requires $HOTEL token to exist.)
9. Anti-farming: IP/behavioral logging + admin suspicious-activity feed.
10. $HOTEL token creation + supply design (separate track, gates #5 and #8).

---

*End of spec. Remember: analyze and propose before building. Reuse the existing game wherever possible.*
