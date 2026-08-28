<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per code sent. The code itself never touches this server: Arqam
 * generates it, delivers it over WhatsApp and checks it — we only keep the
 * message id needed to ask Arqam "was this the right code?".
 */
class PhoneVerification extends Model
{
    use HasUuids;

    protected $fillable = [
        'phone',
        'message_id',
        'purpose',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isUsable(): bool
    {
        return $this->verified_at === null && ! $this->expires_at?->isPast();
    }
}
