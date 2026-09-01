<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * NOTE (Phase 4): `role` is intentionally NOT mass assignable — role
     * escalation via mass assignment is a privilege-escalation vector
     * (directive §98). Roles are assigned only through explicit, audited
     * code paths (registration relies on the column default 'customer').
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'username',
        'phone',
        'country_code',
        'is_active',
        'last_login_at',
        'kyc_status',
        'kyc_tier',
        'pin_hash',
        'pin_attempts',
        'pin_locked_until',
        'virtual_account_number',
        'virtual_bank_name',
        'region_id',
        'referred_by',
    ];

    public function sendEmailVerificationNotification()
    {
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject(Lang::get('Welcome to KoriePay – Verify Your Identity'))
                ->view('emails.welcome-verify', [
                    'name' => $notifiable->name,
                    'url' => $url,
                ]);
        });

        $this->notify(new VerifyEmail);
    }

    // Automatically generate a referral code when a user is created
    protected static function booted()
    {
        static::creating(function ($user) {
            $prefix = strtoupper(substr($user->name, 0, 4));
            $user->referral_code = 'KORIE-' . $prefix . rand(1000, 9999);

            // KoriePay public identity (§128 receive journey) — every user
            // gets one. base36 over a CSPRNG token (36^10 ≈ 3.6e15) — the
            // unique index guards the astronomically unlikely collision.
            if (blank($user->koriepay_id)) {
                $charset = '0123456789abcdefghijklmnopqrstuvwxyz';
                $token = '';
                for ($i = 0; $i < 10; $i++) {
                    $token .= $charset[random_int(0, strlen($charset) - 1)];
                }
                $user->koriepay_id = 'KP-'.strtoupper($token);
            }
        });
    }

    // Relationship for referrals
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** The attributes that should be hidden for serialization. */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Get the attributes that should be cast. */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'pin_locked_until' => 'datetime',
        ];
    }

    // ── Identity relations (Phase 4) ──────────────────────────────────────
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'iso3');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class);
    }

    public function loginEvents(): HasMany
    {
        return $this->hasMany(LoginEvent::class);
    }

    // ── Scope: country data isolation (directive §63) ─────────────────────
    public function scopeForCountry(Builder $query, ?string $countryCode): Builder
    {
        if ($countryCode === null || $countryCode === 'all' || $countryCode === '*') {
            return $query;
        }

        return $query->where('country_code', strtoupper($countryCode));
    }

    // ── Identity helpers ───────────────────────────────────────────────────
    public function isActiveAccount(): bool
    {
        return $this->is_active && ($this->status ?? 'active') !== 'suspended';
    }

    public function isSuperAdmin(): bool
    {
        return ($this->role ?? null) === 'superadmin';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * A user can have many support tickets.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function adashiMemberships(): HasMany
    {
        return $this->hasMany(AdashiMember::class, 'user_id');
    }

    /**
     * Get all audit logs where this user was the target.
     */
    public function targetLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    /**
     * Enterprise Portal Routing Logic
     * Ensures distinct roles land on their respective command centers.
     */
    public function getRedirectRoute()
    {
        return match($this->role) {
            'superadmin' => '/admin/dashboard',
            'technical' => '/tech/system-health',
            'manager' => '/manager/dashboard', // Updated previously for consistency
            'investor' => '/investor/portfolio',
            'agent' => '/agent/terminal',
            'support' => '/support/tickets',
            default => '/dashboard', // Default Customer View
        };
    }
}
