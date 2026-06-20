# Solana Hotel — Implementation Analysis & Plan (pre-build)

> Response to the "READ FIRST" instructions in `SOLANA_HOTEL_ECONOMY_SPEC.md` and
> `SOLANA_HOTEL_TOKEN_AND_EXCHANGE_SPEC.md`. **No code has been written.** This maps every
> mechanic to *already-exists / needs-extension / build-from-scratch*, defines the **standalone**
> marketplace (own nav icon, not the catalog), and proposes an ordered plan. Awaiting approval.

---

## A. What already exists (verified in code + DB)

| Area | Status | Where |
|---|---|---|
| **Currencies** credits/duckets/diamonds/gotw/achievement-score | ✅ | `player_data.{credit_balance,pixel_balance,seasonal_balance,gotw_points,achievement_score}` |
| **Currency client writers** | ✅ | `PlayerCreditsBalanceWriter`, `PlayerActivityPointsBalanceWriter` (NetworkPacketEventHelpers) |
| **Currency add/subtract logic** | ✅ but scattered | CatalogPurchase (subtract), `:credits` admin cmd (add), RoomRedeemItem (add). No central service. |
| **Periodic currency reward task** (faucet primitive) | 🔧 partial | `Sadie.Server/Tasks/.../PlayerCurrencyRewardsTask.cs` + `server_periodic_currency_reward` (config empty) |
| **Furniture inventory + ownership** | ✅ | `player_furniture_items` (player_id = owner), placement_data hides placed items |
| **In-game trade (items only, 0 fee)** | ✅ | `RoomUserTrade.SwapItemsAsync` reassigns `player_id`. Confirmed no credit transfer. |
| **Gift/present system (durable, offline-safe)** | ✅ | `player_gifts` + present wrapper. *This is the delivery/notification precedent.* |
| **Membership / HC** | ✅ | `player_subscriptions` (drives the 1.5× multiplier + wheel odds) |
| **Solana wallet auth (Phantom)** | ✅ | `WalletAuthController` + `SolanaVerificationService` (Ed25519); `players.wallet_address`, `wallet_verified_at` |
| **On-chain payment verify (SOL)** | ✅ | `SolanaPaymentService::verifyPayment` (RPC `getTransaction`, finalized, fail-closed) |
| **Club payment flow (intent→verify→grant, anti-replay)** | ✅ | `ClubPaymentController` + `club_payments`. **This is the template for the Credit Exchange swap.** |
| **Solana config** (RPC, treasury, internal secret, network) | ✅ | `config/solana.php` (Helius RPC, treasury wallet, `X-Internal-Secret`) |
| **RCON CMS→emulator** | ✅ | `RconService` (givecredits, givepoints, sendgift, setrank, alertuser, …) on :3001 |
| **Grant actions (CMS)** | ✅ | `app/Actions/SendCurrency`, `SendFurniture` (RCON primary, DB fallback) |
| **Admin settings** | ✅ | `server_settings` (emulator), `website_settings` + CMS-Settings Filament screen |
| **Live alerts** | ✅ | `PlayerAlertWriter` (online only) |
| **Client nav/toolbar + view system** | ✅ | `ToolbarView.tsx`, `MainView.tsx`, link-event system (`CreateLinkEvent`/`ILinkEventTracker`) |

## What does NOT exist (build-from-scratch)

- ❌ Any marketplace / auction / credit-exchange / escrow / swap / treasury-burn tables or handlers.
- ❌ Item **origin/rarity** flag — `furniture_items` has only `type` + `interaction_type`. The "listable = not Credit Shop" rule and rarity badges have no data source yet.
- ❌ Daily **faucet system** (login/quests/active-time/visitors/streak) with daily caps + 1.5× member multiplier. (Only the periodic-reward primitive exists.)
- ❌ **Daily wheel** (the CMS "draw badge" is unrelated).
- ❌ Persistent **inbox** — only live alerts + durable item/credit delivery exist.
- ❌ **$HOTEL** SPL token balance reads / access gate / atomic swap / treasury burn (token doesn't exist yet — owner creates it).
- ❌ **IP / trade logging + anti-farming** feed.

---

## B. Mechanic-by-mechanic mapping

### Currency & economy
| Mechanic | Verdict | Notes |
|---|---|---|
| Credits balance storage | ✅ exists | `credit_balance` |
| Central give/take Credits + client refresh | 🔧 extend | Build a small `CurrencyService` (add/remove + writer) — pieces exist, just not centralized. Used by faucets, sinks, marketplace. |
| Faucet: daily login (50) | 🆕 build | Reuse achievements' daily-once pattern |
| Faucet: daily quests (6×10) | 🆕 build | No quest system exists |
| Faucet: active time (10/30min, genuine activity) | 🔧 extend | `PlayerCurrencyRewardsTask` is the seed; add activity gating + caps |
| Faucet: room visitors / visiting rooms | 🆕 build | Hook room-entry events |
| Faucet: streak bonus | 🆕 build | New per-player streak state |
| Daily caps + 24h reset + 1.5× membership | 🆕 build | New `player_faucet_state` table; membership from `player_subscriptions` |
| New-player 100-credit grant (gated on verified wallet) | 🔧 extend | Hook wallet-verify (exists) not registration |
| Daily wheel (odds, Rare Box, rotating pool) | 🆕 build | Location TBD (decision) |
| Sinks: catalog/rooms/cosmetics burn | 🔧 extend | Catalog purchase already subtracts credits; "burn" = it already leaves the economy. Add cosmetics/room sinks. |
| Crafting/recycling | 🆕 build | |
| `credit_economy_stats` daily mint/burn tracking | 🆕 build | New table + daily job |

### Shops (3-shop structure)
| Mechanic | Verdict | Notes |
|---|---|---|
| Credit Shop (credits) | ✅ exists | In-game catalog (Credit Shop page) |
| Rare Shop (higher credits) | ✅ exists | Catalog Rare Shop page (id 27) |
| Premium Shop (SOL, cosmetic-only) | 🔧 extend | SOL rails exist (ClubPayment pattern); needs a cosmetic-only SOL shop surface |
| "Credit Shop items NOT listable" rule | 🆕 build | Needs the origin flag (below) |

### Marketplace (the standalone feature — see Section C)
| Mechanic | Verdict | Notes |
|---|---|---|
| Item origin/rarity tagging + filter/badge | 🆕 build | Add `furniture_items.origin`/`rarity`; backfill from catalog-page membership |
| Listing escrow (item held while listed) | 🆕 build | New `marketplace_listings` + move item out of usable inventory (gift/trade reassign pattern) |
| Section A — Item Market (item→credits, fixed price) | 🆕 build | Native emulator handlers + DB |
| Section C — Auction House (credits-only, bids, anti-snipe, escrow) | 🆕 build | + 1-min scheduled job for expiry/anti-snipe |
| Section B — Credit Exchange (credits→$HOTEL atomic swap) | 🆕 build, 🔒 gated | Heavily reuses ClubPayment's intent→verify→grant pattern; **needs $HOTEL mint** |
| Max 20 listings / 5% fee / one-active-listing | 🆕 build | Rules in the listing service |
| Inbox notifications on sale | 🔧 extend / decision | No inbox; use durable delivery (credits/items land in DB) + live alert if online, or build a small inbox |
| `marketplace_logs`, `credit_exchange_swaps`, `treasury_burns`, `wallet_balance_checks` | 🆕 build | Per spec |

### Crypto layer ($HOTEL)
| Mechanic | Verdict | Notes |
|---|---|---|
| Solana RPC + on-chain verify | ✅ exists | `SolanaPaymentService` (extend to SPL token reads/burn) |
| SPL token **balance read** (access gate) | 🔧 extend | Add `getTokenAccountsByOwner`/`getTokenAccountBalance` to the Solana service |
| Access gate (≥1,000 $HOTEL, admin-configurable) | 🆕 build, 🔒 gated | Threshold = a `website_settings` key; enforced at game-entry/SSO (Solana code is PHP-side) |
| Atomic swap (lock→build tx→sign→confirm→release) | 🆕 build, 🔒 gated | ClubPayment is the proven template (intent, anti-replay, finalized poll, fail-safe) |
| Reconciliation job (confirmed-but-unreleased) | 🆕 build | Per token spec — important, not skipped |
| Treasury burn (batched SPL burn) + custody | 🆕 build, 🔒 gated | Needs custody decision (#4 in token spec) |

### Anti-farming
| Mechanic | Verdict | Notes |
|---|---|---|
| Access gate (Sybil defense) | see crypto | |
| Cash-out chokepoint (credits only convert via Exchange; trades = items only) | ✅ mostly | In-game trade is already items-only; just enforce "no credit transfer" stays true |
| IP logging (register/login/marketplace) + trade logging | 🆕 build | Capture IP (login handler has it), persist, flag patterns |
| Behavioral flags + admin suspicious-activity feed | 🆕 build | New admin (Filament) feed |

---

## C. The Marketplace as a STANDALONE feature (own nav icon) — design + how the icon is added

**Decision honored:** the marketplace is **not** part of the Catalog. The Catalog stays exactly as-is
(Credit Shop / Rare Shop). The Marketplace is its own top-level client window with its own toolbar
icon and three internal tabs: **Item Market · Credit Exchange · Auction House**.

### How the nav icon + view is added (verified against the client architecture)
The Nitro toolbar uses a **link-event** system (no global store). Adding a standalone feature = 7 edits,
all following existing conventions:

1. **Icon asset** — add `src/assets/images/toolbar/icons/marketplace.png`.
2. **Icon CSS** — add `&.icon-marketplace { background-image: url(...marketplace.png); width/height }` in `src/assets/styles/icons.scss`.
3. **Toolbar button** — in `src/components/toolbar/ToolbarView.tsx`, next to the catalog/inventory icons:
   `<Base pointer className="navigation-item icon icon-marketplace" onClick={() => CreateLinkEvent('marketplace/toggle')} />`
4. **New view** — `src/components/marketplace/MarketplaceView.tsx` (its own `NitroCardView` window) with an `ILinkEventTracker` on `eventUrlPrefix:'marketplace/'` handling show/hide/toggle, and three internal tabs.
5. **Register view** — import + render `<MarketplaceView />` in `src/components/main/MainView.tsx` (where Catalog/Inventory are rendered).
6. **State hook** (optional) — `src/hooks/marketplace/useMarketplace.ts` via `useBetween` (mirrors `useCatalog`).
7. **Messages** — add marketplace message composers/events (see protocol decision #1) under `src/api`/message layer.

This keeps it 100% decoupled from `CatalogView`. I'll show you the actual icon + a render mock before wiring.

### Server side (standalone, native to the emulator)
- New packet header range for marketplace ops (browse/list/buy/cancel + bids + exchange steps) — **not** the Habbo catalog-marketplace packets.
- New emulator handlers + a `MarketplaceService` (listing/escrow/atomicity), new DB tables.
- Section B (Credit Exchange) coordinates with the **CMS** for the on-chain swap (Solana code is PHP-side), via internal endpoints like the ClubPayment flow.

---

## D. Decisions to confirm before building

**From the token spec (still open):**
- (T1) Burn key custody — recommend **(A) dedicated burn wallet** holding only fees.
- (T2) RPC provider — you already have a Helius RPC in `config/solana.php`; confirm it's the one to use (and rate limits).
- (T3) Token facts — mint address, decimals, treasury wallet (owner provides; gates Section B + gate).

**New decisions surfaced by the analysis:**
- (D1) **Marketplace protocol:** define **custom packets** for all three sections (recommended — clean, fully ours, supports auction + exchange which Habbo's protocol doesn't) vs. reuse the half-built standard Habbo marketplace composers (catalog-coupled, item-market only). Recommend custom.
- (D2) **Item origin/rarity source:** add a `furniture_items.origin`/`rarity` column and **backfill from catalog-page membership** (Credit Shop → not listable; Rare/Club/LTD/Event → listable). Confirm this approach.
- (D3) **Notifications:** no inbox exists. Recommend **durable delivery** (credits/items always land in DB, seen on next login) **+ live alert when online**, and defer a full inbox unless you want persistent message history.
- (D4) **Wheel + faucets location:** faucets are gameplay → **emulator**. The wheel can be an in-client view (emulator-backed) or a CMS web page. Recommend **in-client** for cohesion. Confirm.
- (D5) **Access-gate enforcement point:** enforce at **CMS game-entry/SSO issuance** (CMS holds all Solana code) rather than the emulator doing RPC. Confirm.
- (D6) **Credit Exchange orchestration:** run the swap from the **CMS** (reusing `SolanaPaymentService` + internal-secret endpoints), with the client/emulator coordinating. Confirm.

---

## E. Proposed build order (revised, dependency-ordered)

**Phase 0 — foundations (no crypto):**
1. `CurrencyService` (central give/take + client refresh).
2. Item **origin/rarity** column + backfill + filter/badge plumbing (D2).
3. Economy logging tables (`marketplace_logs`, `credit_economy_stats`).

**Phase 1 — standalone Marketplace shell + Section A:**
4. Client: marketplace toolbar icon + `MarketplaceView` + tabs (Section C above).
5. Emulator: `MarketplaceService` + escrow + listing table + custom packets (D1).
6. **Section A — Item Market** (list/buy/cancel/expire, 5% burn, durable delivery + alert).

**Phase 2 — Section C (Auction House):** bids, +5 increment, anti-snipe, credit-bid escrow, 1-min expiry job.

**Phase 3 — Faucets + Wheel + Sinks:** daily caps + reset + 1.5× membership; daily wheel + Rare Box; cosmetics/room sinks; economy-stats job.

**Phase 4 — Anti-farming:** IP + trade logging, behavioral flags, admin suspicious-activity feed.

**Phase 5 — Crypto (gated on $HOTEL mint + decisions T1–T3):**
7. SPL balance read → **access gate** (D5).
8. **Section B — Credit Exchange** atomic swap (D6) + `credit_exchange_swaps` + reconciliation job.
9. **Treasury burn** (batched) + custody (T1) + `treasury_burns`.

Phases 0–4 have **no crypto dependency** and deliver a fully working Credits marketplace + economy.
Phase 5 turns on when the token exists.

---

*Analysis only — nothing built. Awaiting your review of the decisions (D1–D6, T1–T3) and the plan.*

---

## F. Round 2 — owner decisions & the credit-item exchange redesign

**Locked in:**
- **Nav icon = bottom-LEFT toolbar cluster** (alongside catalog/inventory/navigator), not the right side.
- **D2 = approved** — add `furniture_items.origin`/`rarity`, backfill from catalog-page membership.
- **D3 = build a persistent inbox** (in-game `player_inbox` + a client inbox view/indicator; durable, offline-safe).
- **D5/D6 = approved** — access gate + Credit Exchange run from the **CMS**; the gate is ALSO re-verified
  **on client load / game entry** (server-authoritative; players can't bypass it client-side).

**Credit-item Exchange redesign (replaces "list raw Credits → $HOTEL"):**
- Players never list a raw Credit *balance*. Instead they list the existing in-game **credit ITEMS**
  (the `CF_`-prefixed redeemable furniture, e.g. a "Gold Bar" worth 50 credits via the existing
  `RoomRedeemItemEventHandler`).
- **Credit items become NON-TRADABLE** in normal player↔player trade (blocked in the trade offer
  handler). They can move between accounts ONLY via the marketplace (priced in $HOTEL, taxed 5%,
  logged) — which *enforces the cash-out chokepoint at the item level* (no untaxed/unlogged credit
  movement is even possible).
- A Credit-Exchange listing = **a quantity of one credit-item type** at a **seller-set fixed $HOTEL
  price for the bundle**. The listing shows the **total credit value = qty × per-item credit value**,
  visible to all, updating live as the seller sets the quantity (e.g. 5 × Gold Bar(50) → "250 credits").
- Buyer pays $HOTEL (95% → seller wallet, 5% → treasury, single atomic tx) → receives the N credit
  items; redeems them for credits in-game via the existing redeem mechanic.
- **Item Market** (rare items → Credits) and **Auction House** (items → Credits) are unchanged
  (priced in Credits). Only the Credit Exchange uses credit-items → $HOTEL.

**ALL DECISIONS NOW RESOLVED:**
- **D1 = custom packets.** The marketplace uses its own message classes in the Nitro client (our fork)
  + matching emulator handlers, over the game websocket — fully in-client, real-time. Fits auctions +
  $HOTEL + credit-item bundles + inbox.
- **$HOTEL settlement:** the **signing happens in the client (Phantom)** — buyer builds/signs/submits the
  tx and gets a signature. The **verify-and-release is server-authoritative and runs in the CMS**
  (reusing `SolanaPaymentService` + treasury config + anti-replay). Client can never authorize its own
  release. Flow: client signs → hands signature to CMS → CMS confirms finalized on-chain + amounts +
  not-replayed → only then emulator releases the credit-items. Fail/timeout → nothing released.
- **D4 = in-client wheel** (emulator-backed). Faucets are emulator-side by necessity.
- **Credit-item acquisition = BOTH** (buyable with credits in the catalog AND obtainable as wheel/event
  rewards).

**Owner to provide for Phase 5 (crypto only — gates nothing in Phases 0–4):** $HOTEL mint address,
decimals, treasury wallet, a dedicated burn wallet + how its key reaches the backend signer (or pick
manual-burn), and confirmed RPC endpoint + mainnet.
