<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'app_name',
        'app_logo',
        'is_maintenance',
        'otp_enabled',
        'otp_api_key',
        'otp_expiry_minutes',
    ];

    protected $hidden = [
        'otp_api_key',
    ];

    protected $casts = [
        'is_maintenance' => 'boolean',
        'otp_enabled' => 'boolean',
        'otp_expiry_minutes' => 'integer',
    ];

    protected $appends = [
        'app_logo_url',
        'otp_api_key_configured',
        'otp_api_key_preview',
    ];

    /**
     * Get the absolute public URL for app logo.
     */
    public function getAppLogoUrlAttribute(): ?string
    {
        if ($this->app_logo) {
            return Storage::disk('public')->url($this->app_logo);
        }

        return null;
    }

    /**
     * Whether an OTPIQ API key is currently set (DB or .env fallback) —
     * exposed instead of the raw key, which is never returned to the client.
     */
    public function getOtpApiKeyConfiguredAttribute(): bool
    {
        return filled($this->attributes['otp_api_key'] ?? null) || filled(config('otp.otpiq.api_key'));
    }

    /**
     * Last 4 characters of the configured key, for admin confirmation only.
     */
    public function getOtpApiKeyPreviewAttribute(): ?string
    {
        $key = $this->attributes['otp_api_key'] ?? config('otp.otpiq.api_key');

        return $key ? '••••'.substr($key, -4) : null;
    }

    /**
     * Resolve the OTPIQ API key actually used for sending — DB override first,
     * falling back to config('otp.otpiq.api_key') from .env.
     */
    public function resolvedOtpApiKey(): ?string
    {
        return $this->attributes['otp_api_key'] ?? config('otp.otpiq.api_key');
    }

    /**
     * Get the singleton instance of SystemSetting.
     */
    public static function get(): self
    {
        return self::firstOrCreate(
            ['id' => 'default'],
            [
                'app_name' => 'رحلة فارس',
                'is_maintenance' => false,
                'otp_enabled' => true,
                'otp_expiry_minutes' => 5,
            ]
        );
    }
}
