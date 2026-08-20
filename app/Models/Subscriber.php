<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Subscriber extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'phone_verified_at',
        'address',
        'password',
        'status',
        'last_login_at',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => SubscriberStatus::class,
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === SubscriberStatus::ACTIVE;
    }

    public function isUnverified(): bool
    {
        return $this->status === SubscriberStatus::UNVERIFIED;
    }

    public function isSuspended(): bool
    {
        return $this->status === SubscriberStatus::SUSPENDED;
    }

    public function curriculumAssignments(): HasMany
    {
        return $this->hasMany(UserCurriculumAssignment::class, 'subscriber_id');
    }

    public function activeCurriculumAssignment(): HasOne
    {
        return $this->hasOne(UserCurriculumAssignment::class, 'subscriber_id')
            ->where('status', 'ACTIVE')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latestOfMany();
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class, 'subscriber_id');
    }

    public function skillProgress(): HasMany
    {
        return $this->hasMany(UserSkillProgress::class, 'subscriber_id');
    }

    public function activations(): HasMany
    {
        return $this->hasMany(ActivationCode::class, 'activated_by');
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SubscriberActivity::class)->latest('created_at');
    }
}
