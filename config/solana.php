<?php

return [
    // Network + RPC the verifier queries. Change the treasury/RPC here (or via
    // .env) at any time without touching the payment mechanic.
    'network' => env('SOLANA_NETWORK', 'mainnet-beta'),
    // Server-side verification RPC (private — full Helius key is safe here).
    'rpc_url'  => env('SOLANA_RPC_URL', 'https://api.mainnet-beta.solana.com'),
    // RPC the browser/client uses (blockhash only). This URL is sent to browsers,
    // so it must NOT carry a private key. Defaults to the public node so setting a
    // Helius key in SOLANA_RPC_URL never leaks to clients. Only set this once you
    // have a domain-restricted key.
    'client_rpc_url' => env('SOLANA_CLIENT_RPC_URL', 'https://api.mainnet-beta.solana.com'),

    // Destination of all club payments. Swap freely — the mechanic reads it here.
    'treasury_wallet' => env('SOLANA_TREASURY_WALLET', '3ssjyMcmzM7LM5PQkfBMJBDnngoZ1TLBuyYzcMqCzx5S'),

    // Shared secret for the emulator -> CMS server-to-server verify calls.
    'internal_secret' => env('SOLANA_INTERNAL_SECRET', ''),

    // Live SOL/USD price source (oracle).
    'price_url'         => env('SOLANA_PRICE_URL', 'https://api.coingecko.com/api/v3/simple/price?ids=solana&vs_currencies=usd'),
    'price_cache_secs'  => (int) env('SOLANA_PRICE_CACHE', 60),

    // A payment intent (and its locked SOL amount) is valid for this long.
    'intent_ttl_seconds' => (int) env('SOLANA_INTENT_TTL', 900),

    // Required on-chain confirmation level before granting.
    'min_confirmation' => env('SOLANA_MIN_CONFIRMATION', 'finalized'),

    // Allow the paid amount to be slightly under the quoted lamports to absorb
    // tiny price drift between intent and payment (0.5% default). Never below.
    'amount_tolerance_bps' => (int) env('SOLANA_AMOUNT_TOLERANCE_BPS', 50),

    // =====================================================================
    //  $HOTEL token (Phase 5). Fill these in via .env once the token exists.
    //  Until the mint + wallets are set, the access gate and the Credit
    //  Exchange on-chain settlement stay DISABLED — nothing breaks, and you
    //  flip the two `*_enabled` flags to turn it on. See Housekeeping ->
    //  Marketplace -> "$HOTEL / Crypto" for a live checklist of what's set.
    // =====================================================================
    'hotel' => [
        // The $HOTEL SPL token mint address (blank until the token is created).
        'mint' => env('HOTEL_MINT', ''),
        // SPL token decimals (commonly 6 or 9).
        'decimals' => (int) env('HOTEL_DECIMALS', 9),
        // Treasury wallet that receives the 5% fee (later burned in batches).
        'treasury_wallet' => env('HOTEL_TREASURY_WALLET', env('SOLANA_TREASURY_WALLET', '')),
        // Dedicated burn wallet — holds only fee tokens, burned on a cadence.
        'burn_wallet' => env('HOTEL_BURN_WALLET', ''),
        // Marketplace fee on $HOTEL trades (percent). 95/5 split by default.
        'fee_percent' => (float) env('HOTEL_FEE_PERCENT', 5),

        // ACCESS GATE — minimum $HOTEL a wallet must hold to play. DISABLED by
        // default (per owner: leave off until the token is live).
        'access_gate' => [
            'enabled' => filter_var(env('HOTEL_ACCESS_GATE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'amount'  => (int) env('HOTEL_ACCESS_GATE_AMOUNT', 1000),
        ],

        // CREDIT EXCHANGE on-chain settlement. DISABLED until mint + wallets are
        // set — the in-game "Buy ($HOTEL)" stays inert while this is off.
        'exchange_enabled' => filter_var(env('HOTEL_EXCHANGE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

        // Confirmation level required before releasing items, and how long a buy
        // intent / its locked price stays valid.
        'min_confirmation' => env('HOTEL_MIN_CONFIRMATION', 'finalized'),
        'intent_ttl_seconds' => (int) env('HOTEL_INTENT_TTL', 900),
    ],
];
