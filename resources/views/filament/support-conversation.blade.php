<div style="display:flex;flex-direction:column;gap:8px;max-height:440px;overflow:auto;padding:4px">
    @forelse($ticket->messages as $m)
        <div style="align-self:{{ $m->is_staff ? 'flex-end' : 'flex-start' }};max-width:80%;
                    background:{{ $m->is_staff ? '#dbeafe' : '#f3f4f6' }};border-radius:10px;padding:8px 11px">
            <div style="font-weight:600;font-size:12px;color:{{ $m->is_staff ? '#1d4ed8' : '#374151' }}">
                {{ $m->sender_name }}{{ $m->is_staff ? ' (Staff)' : '' }}
            </div>
            <div style="white-space:pre-wrap;color:#111827">{{ $m->body }}</div>
            <div style="font-size:11px;color:#6b7280;text-align:right">{{ $m->created_at }}</div>
        </div>
    @empty
        <div style="color:#6b7280">No messages.</div>
    @endforelse
</div>
