<x-filament-panels::page>
    @php
        $t = $this->token();
        $configured = $t->isConfigured();
        $exchangeLive = $t->isExchangeLive();
        $gateActive = $t->isAccessGateActive();
        $swaps = $this->swaps();
        $badge = fn($on, $onText, $offText) =>
            '<span style="padding:2px 9px;border-radius:9px;font-size:12px;font-weight:600;color:#fff;background:'.($on ? '#16a34a' : '#6b7280').'">'.($on ? $onText : $offText).'</span>';
        $th = 'text-align:left;padding:6px 10px;font-size:12px;color:#6b7280;border-bottom:1px solid #e5e7eb';
        $td = 'padding:6px 10px;font-size:13px;border-bottom:1px solid #f3f4f6';
    @endphp

    <x-filament::section icon="heroicon-o-signal">
        <x-slot name="heading">Status</x-slot>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <div>Token configured: {!! $badge($configured, 'Yes', 'Not yet') !!}</div>
            <div>Credit Exchange (on-chain): {!! $badge($exchangeLive, 'LIVE', 'Disabled') !!}</div>
            <div>Access gate: {!! $badge($gateActive, 'ACTIVE ('.$t->accessGateAmount().' $HOTEL)', 'Disabled') !!}</div>
        </div>
        @unless($configured)
            <div style="margin-top:10px;font-size:13px;color:#b45309">
                Fill in the mint + treasury + burn wallet (below) in your <code>.env</code>, then set
                <code>HOTEL_EXCHANGE_ENABLED=true</code> to enable buying. Leave the access gate off until you're ready.
            </div>
        @endunless
    </x-filament::section>

    <x-filament::section icon="heroicon-o-clipboard-document-check">
        <x-slot name="heading">Configuration checklist</x-slot>
        <x-slot name="description">Set these in <code>.env</code> (config/solana.php reads them). Run <code>php artisan config:clear</code> after editing.</x-slot>
        <table style="width:100%;border-collapse:collapse">
            <tr><th style="{{ $th }}">Setting</th><th style="{{ $th }}">.env key</th><th style="{{ $th }}">Current value</th><th style="{{ $th }}"></th></tr>
            @foreach ($t->checklist() as $row)
                @php $set = $row['value'] !== '' && $row['value'] !== null; @endphp
                <tr>
                    <td style="{{ $td }}">{{ $row['label'] }}</td>
                    <td style="{{ $td }}"><code>{{ $row['env'] }}</code></td>
                    <td style="{{ $td }};word-break:break-all">{{ $set ? $row['value'] : '—' }}</td>
                    <td style="{{ $td }}">{!! $set ? $badge(true,'set','') : $badge(false,'','missing') !!}</td>
                </tr>
            @endforeach
        </table>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-code-bracket" collapsible collapsed>
        <x-slot name="heading">.env template</x-slot>
        <pre style="font-size:12px;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto">HOTEL_MINT=
HOTEL_DECIMALS=9
HOTEL_TREASURY_WALLET=
HOTEL_BURN_WALLET=
HOTEL_FEE_PERCENT=5
SOLANA_RPC_URL=
SOLANA_CLIENT_RPC_URL=

# Flip these on when ready:
HOTEL_EXCHANGE_ENABLED=false
HOTEL_ACCESS_GATE_ENABLED=false
HOTEL_ACCESS_GATE_AMOUNT=1000</pre>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-arrows-right-left">
        <x-slot name="heading">Recent swaps ({{ count($swaps) }})</x-slot>
        @if (count($swaps))
            <table style="width:100%;border-collapse:collapse">
                <tr><th style="{{ $th }}">#</th><th style="{{ $th }}">Buyer</th><th style="{{ $th }}">Seller</th><th style="{{ $th }}">$HOTEL</th><th style="{{ $th }}">Fee</th><th style="{{ $th }}">Status</th><th style="{{ $th }}">Tx</th></tr>
                @foreach ($swaps as $s)
                    <tr><td style="{{ $td }}">{{ $s->id }}</td><td style="{{ $td }}">{{ $s->buyer }}</td><td style="{{ $td }}">{{ $s->seller }}</td>
                        <td style="{{ $td }}">{{ $s->amount_hotel }}</td><td style="{{ $td }}">{{ $s->fee_hotel }}</td><td style="{{ $td }}">{{ $s->status }}</td>
                        <td style="{{ $td }};word-break:break-all">{{ $s->tx_signature ? \Illuminate\Support\Str::limit($s->tx_signature, 16) : '—' }}</td></tr>
                @endforeach
            </table>
        @else <div style="color:#6b7280;font-size:13px">No swaps yet — they appear here once the exchange is live and buyers settle on-chain.</div> @endif
    </x-filament::section>
</x-filament-panels::page>
