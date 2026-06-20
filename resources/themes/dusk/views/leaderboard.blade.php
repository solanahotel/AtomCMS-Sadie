<x-app-layout>
    @push('title', __('Leaderboard'))

    <div class="col-span-12">
        <x-page-header>
            <x-slot:icon>
                <img src="{{ asset('/assets/images/dusk/leaderboard_icon.png') }}" alt="">
            </x-slot:icon>

            {{ __('Leaderboards') }}
        </x-page-header>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-4">
            <x-leaderboard-card title="{{ __('Top credits') }}" icon="credits.png" :data="$credits" relationship="player" valueKey="credit_balance" valueType="Credits" />
            <x-leaderboard-card title="{{ __('Top duckets') }}" icon="duckets.png" :data="$duckets" relationship="player" valueKey="pixel_balance" valueType="Duckets" />
            <x-leaderboard-card title="{{ __('Top diamonds') }}" icon="diamond.png" :data="$diamonds" relationship="player" valueKey="seasonal_balance" valueType="Diamonds" />
            <x-leaderboard-card title="{{ __('Gamer of the Week') }}" icon="gotw.png" :data="$gotw" relationship="player" valueKey="gotw_points" valueType="GOTW Points" />
            <x-leaderboard-card title="{{ __('Respects received') }}" icon="heart.gif" :data="$respectsReceived" relationship="player" valueKey="respects_received" valueType="Respect received" />
            <x-leaderboard-card title="{{ __('Achievement score') }}" icon="star.gif" :data="$achievementScores" relationship="player" valueKey="achievement_score" valueType="Achievement points" />
        </div>
    </div>
</x-app-layout>
