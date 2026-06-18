<?php

namespace App\Models;

use App\Models\Articles\WebsiteArticle;
use App\Models\Articles\WebsiteArticleComment;
use App\Models\Community\Staff\WebsiteStaffApplications;
use App\Models\Community\Staff\WebsiteTeam;
use App\Models\Game\Furniture\Item;
use App\Models\Game\Permission;
use App\Models\Game\Player\UserBadge;
use App\Models\Game\Player\UserSubscription;
use App\Models\Game\Room;
use App\Models\Help\WebsiteHelpCenterTicket;
use App\Models\Miscellaneous\CameraWeb;
use App\Models\Miscellaneous\WebsiteBetaCode;
use App\Models\Shop\WebsitePaypalTransaction;
use App\Models\Shop\WebsiteUsedShopVoucher;
use App\Models\User\ClaimedReferralLog;
use App\Models\User\PlayerAvatarData;
use App\Models\User\PlayerData;
use App\Models\User\PlayerFriendship;
use App\Models\User\PlayerRole;
use App\Models\User\PlayerSsoToken;
use App\Models\User\PlayerWebsiteData;
use App\Models\User\Referral;
use App\Models\User\Role;
use App\Models\User\Role as UserRole;
use App\Models\User\UserReferral;
use App\Models\User\WebsiteUserGuestbook;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = ['id', 'password', 'remember_token', 'wallet_address'];

    protected $table = 'players';

    protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hidden_staff' => 'boolean',
        'online' => 'boolean',
        'wallet_verified_at' => 'datetime',
    ];
}

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function ssoTokens(): HasMany
    {
        return $this->hasMany(PlayerSsoToken::class, 'player_id');
    }

    public function currency(string $currency)
    {
        $data = $this->relationLoaded('data') ? $this->data : $this->data()->first();

        if (! $data) {
            return 0;
        }

        return match ($currency) {
            // map your CMS strings to player_data columns
            'credits' => $data->credit_balance,
            'duckets' => $data->pixel_balance,
            'diamonds' => $data->seasonal_balance,
            'points' => $data->gotw_points,
            default => 0,
        };
    }

    public function permission(): HasOne
    {
        return $this->hasOne(Permission::class, 'id', 'rank');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(WebsiteArticle::class);
    }

    public function referrals(): HasOne
    {
        return $this->hasOne(UserReferral::class);
    }

    public function userReferrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function claimedReferralLog(): HasMany
    {
        return $this->hasMany(ClaimedReferralLog::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'player_id', 'id')
            ->with('badge');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'owner_id');
    }

    public function friends(): HasMany
    {
        return $this->hasMany(PlayerFriendship::class, 'origin_player_id');
    }

    public function referralsNeeded()
    {
        $referrals = 0;

        if (! is_null($this->referrals)) {
            $referrals = $this->referrals->referrals_total;
        }

        return setting('referrals_needed') - $referrals;
    }

    public function ban()
    {
        return $this->hasOne(\App\Models\User\Ban::class, 'player_id')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function ssoTicket(): string
    {
        $prefix = Str::replace(' ', '', setting('hotel_name') ?? 'Atom');

        $token = sprintf('%s-%s', $prefix, Str::uuid());

        if (PlayerSsoToken::where('token', $token)->exists()) {
            return $this->ssoTicket();
        }

        $now = now();
        $expiresAt = $now->copy()->addMinutes(10);

        $this->ssoTokens()->update([
            'expires_at' => $now,
            'used_at' => $now,
        ]);

        $this->ssoTokens()->create([
            'token' => $token,
            'created_at' => $now,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        return $token;
    }

    public function betaCode(): HasOne
    {
        return $this->hasOne(WebsiteBetaCode::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(WebsiteTeam::class, 'team_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(WebsiteStaffApplications::class, 'user_id');
    }

    public function hcSubscription(): HasOne
    {
        return $this->hasOne(UserSubscription::class);
    }

    public function articleComments(): HasMany
    {
        return $this->hasMany(WebsiteArticleComment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WebsitePaypalTransaction::class);
    }

    public function usedShopVouchers(): HasMany
    {
        return $this->hasMany(WebsiteUsedShopVoucher::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'user_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(WebsiteHelpCenterTicket::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CameraWeb::class);
    }

    public function profileGuestbook(): HasMany
    {
        return $this->hasMany(WebsiteUserGuestbook::class, 'profile_id');
    }

    public function guestbook(): HasMany
    {
        return $this->hasMany(WebsiteUserGuestbook::class, 'user_id');
    }

    public function chatLogs()
    {
        return $this->hasMany(ChatlogRoom::class, 'user_from_id');
    }

    public function chatLogsPrivate()
    {
        return $this->hasMany(ChatlogPrivate::class, 'user_from_id');
    }

    public function getOnlineFriends(int $total = 10)
    {
        return PlayerFriendship::query()
            ->select([
                'player_friendships.target_player_id as friend_id',
                'players.id',
                'players.username',
                'player_avatar_data.figure_code as look',
                'player_avatar_data.motto',
                'player_data.last_online',
            ])
            ->join('players', 'players.id', '=', 'player_friendships.target_player_id')
            ->join('player_avatar_data', 'player_avatar_data.player_id', '=', 'players.id')
            ->join('player_data', 'player_data.player_id', '=', 'players.id')
            ->where('player_friendships.origin_player_id', $this->id)
            ->where('player_friendships.status', 1)
            ->where('player_data.is_online', 1)
            ->inRandomOrder()
            ->limit($total)
            ->get();
    }

    public function confirmTwoFactorAuthentication($code)
    {
        $codeIsValid = app(TwoFactorAuthenticationProvider::class)
            ->verify(decrypt($this->two_factor_secret), $code);

        if (! $codeIsValid) {
            return false;
        }

        $this->update([
            'two_factor_confirmed' => true,
        ]);

        return true;
    }

    public function hasAppliedForPosition(int $rankId)
    {
        return $this->applications()->where('rank_id', '=', $rankId)->exists();
    }

    public function changePassword(string $newPassword)
    {
        $this->password = Hash::make($newPassword);
        $this->save();
    }

    public function getFilamentName(): string
    {
        return $this->username ?? 'Guest';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return hasHousekeepingPermission('can_access_housekeeping');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'username', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function save(array $options = [])
    {
        if (! $this->isDirty()) {
            return false;
        }

        return parent::save($options);
    }

    public function avatar()
    {
        return $this->hasOne(PlayerAvatarData::class, 'player_id');
    }

    public function data()
    {
        return $this->hasOne(PlayerData::class, 'player_id');
    }

    public function website()
    {
        return $this->hasOne(PlayerWebsiteData::class, 'player_id');
    }

    public function rank()
    {
        return $this->hasOneThrough(
            UserRole::class,
            PlayerRole::class,
            'player_id',
            'id',
            'id',
            'role_id',
        );
    }

    public function role()
    {
        return $this->hasOne(PlayerRole::class, 'player_id');
    }

    public function getRankAttribute(): int
    {
        return $this->role->role_id ?? 1;
    }

    public function roleLink()
    {
        return $this->hasOne(PlayerRole::class, 'player_id');
    }

    public function staffRole()
    {
        return $this->hasOneThrough(
            Role::class,
            PlayerRole::class,
            'player_id',
            'id',
            'id',
            'role_id',
        );
    }

    public function getOnlineAttribute(): bool
    {
        return (bool) ($this->data?->is_online ?? false);
    }

    public function getLookAttribute(): ?string
    {
        return $this->avatar->figure_code ?? null;
    }

    public function getMottoAttribute(): ?string
    {
        return $this->avatar->motto ?? null;
    }
}
