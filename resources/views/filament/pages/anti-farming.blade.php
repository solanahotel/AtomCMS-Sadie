<x-filament-panels::page>
    @php
        $sharedIps = $this->sharedIps();
        $wash = $this->washTrades();
        $funnel = $this->funnelAccounts();
        $velocity = $this->newVelocity();
        $lowEng = $this->lowEngagement();
        $thStyle = 'text-align:left;padding:6px 10px;font-size:12px;color:#6b7280;border-bottom:1px solid #e5e7eb;white-space:nowrap';
        $tdStyle = 'padding:6px 10px;font-size:13px;border-bottom:1px solid #f3f4f6;vertical-align:top';
    @endphp

    <div style="font-size:13px;color:#6b7280;margin-bottom:4px">
        Patterns are surfaced here for <strong>manual review</strong> only — nothing is auto-actioned. Reload to refresh.
    </div>

    {{-- Shared-IP clusters --}}
    <x-filament::section icon="heroicon-o-link" icon-color="warning">
        <x-slot name="heading">Shared-IP clusters ({{ count($sharedIps) }})</x-slot>
        <x-slot name="description">Accounts that logged in from the same IP — possible alt clusters.</x-slot>
        @if (count($sharedIps))
            <table style="width:100%;border-collapse:collapse">
                <tr><th style="{{ $thStyle }}">IP</th><th style="{{ $thStyle }}">Accounts</th><th style="{{ $thStyle }}">Usernames</th><th style="{{ $thStyle }}">Last seen</th></tr>
                @foreach ($sharedIps as $r)
                    <tr><td style="{{ $tdStyle }}"><code>{{ $r->ip_address }}</code></td>
                        <td style="{{ $tdStyle }}"><strong style="color:#b45309">{{ $r->accounts }}</strong></td>
                        <td style="{{ $tdStyle }}">{{ $r->usernames }}</td>
                        <td style="{{ $tdStyle }};color:#6b7280">{{ $r->last_seen }}</td></tr>
                @endforeach
            </table>
        @else <div style="color:#6b7280;font-size:13px">No shared-IP clusters.</div> @endif
    </x-filament::section>

    {{-- Wash / circular trades --}}
    <x-filament::section icon="heroicon-o-arrow-path" icon-color="danger">
        <x-slot name="heading">Wash / circular trades ({{ count($wash) }})</x-slot>
        <x-slot name="description">Account pairs that traded with each other in BOTH directions — possible collusion / wash trading.</x-slot>
        @if (count($wash))
            <table style="width:100%;border-collapse:collapse">
                <tr><th style="{{ $thStyle }}">Account A</th><th style="{{ $thStyle }}">Account B</th><th style="{{ $thStyle }}">A→B</th><th style="{{ $thStyle }}">B→A</th><th style="{{ $thStyle }}">Total</th></tr>
                @foreach ($wash as $r)
                    <tr><td style="{{ $tdStyle }}">{{ $r->user_a }}</td><td style="{{ $tdStyle }}">{{ $r->user_b }}</td>
                        <td style="{{ $tdStyle }}">{{ $r->ab }}</td><td style="{{ $tdStyle }}">{{ $r->ba }}</td>
                        <td style="{{ $tdStyle }}"><strong style="color:#dc2626">{{ $r->total }}</strong></td></tr>
                @endforeach
            </table>
        @else <div style="color:#6b7280;font-size:13px">No mutual / circular trade pairs.</div> @endif
    </x-filament::section>

    {{-- Funnel hubs --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="danger">
        <x-slot name="heading">Funnel hubs ({{ count($funnel) }})</x-slot>
        <x-slot name="description">Accounts trading with many distinct counterparties (≥ 5) — possible value funnelling from alts.</x-slot>
        @if (count($funnel))
            <table style="width:100%;border-collapse:collapse">
                <tr><th style="{{ $thStyle }}">User</th><th style="{{ $thStyle }}">Distinct partners</th><th style="{{ $thStyle }}">Trades</th></tr>
                @foreach ($funnel as $r)
                    <tr><td style="{{ $tdStyle }}">{{ $r->username }}</td>
                        <td style="{{ $tdStyle }}"><strong style="color:#dc2626">{{ $r->partners }}</strong></td>
                        <td style="{{ $tdStyle }}">{{ $r->trades }}</td></tr>
                @endforeach
            </table>
        @else <div style="color:#6b7280;font-size:13px">No funnel hubs flagged.</div> @endif
    </x-filament::section>

    {{-- New high-velocity accounts --}}
    <x-filament::section icon="heroicon-o-bolt" icon-color="warning">
        <x-slot name="heading">New accounts, high volume ({{ count($velocity) }})</x-slot>
        <x-slot name="description">Accounts created in the last 7 days already trading heavily (≥ 5 marketplace transactions).</x-slot>
        @if (count($velocity))
            <table style="width:100%;border-collapse:collapse">
                <tr><th style="{{ $thStyle }}">User</th><th style="{{ $thStyle }}">Created</th><th style="{{ $thStyle }}">Trades</th></tr>
                @foreach ($velocity as $r)
                    <tr><td style="{{ $tdStyle }}">{{ $r->username }}</td><td style="{{ $tdStyle }};color:#6b7280">{{ $r->created_at }}</td>
                        <td style="{{ $tdStyle }}"><strong style="color:#b45309">{{ $r->trades }}</strong></td></tr>
                @endforeach
            </table>
        @else <div style="color:#6b7280;font-size:13px">No new high-volume accounts.</div> @endif
    </x-filament::section>

    {{-- Behavioral: low engagement --}}
    <x-filament::section icon="heroicon-o-user-minus" icon-color="gray">
        <x-slot name="heading">Low-engagement, high-login ({{ count($lowEng) }})</x-slot>
        <x-slot name="description">Logs in repeatedly (≥ 5) but no friends, no rooms, and almost no chat — possible idle / bot farms.</x-slot>
        @if (count($lowEng))
            <table style="width:100%;border-collapse:collapse">
                <tr><th style="{{ $thStyle }}">User</th><th style="{{ $thStyle }}">Logins</th><th style="{{ $thStyle }}">Friends</th><th style="{{ $thStyle }}">Rooms</th><th style="{{ $thStyle }}">Chats</th></tr>
                @foreach ($lowEng as $r)
                    <tr><td style="{{ $tdStyle }}">{{ $r->username }}</td>
                        <td style="{{ $tdStyle }}"><strong>{{ $r->logins }}</strong></td>
                        <td style="{{ $tdStyle }}">{{ $r->friends }}</td><td style="{{ $tdStyle }}">{{ $r->rooms }}</td><td style="{{ $tdStyle }}">{{ $r->chats }}</td></tr>
                @endforeach
            </table>
        @else <div style="color:#6b7280;font-size:13px">No low-engagement accounts flagged.</div> @endif
    </x-filament::section>
</x-filament-panels::page>
