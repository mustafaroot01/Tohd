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
        'otp_base_url',
        'otp_api_key',
        'profile_completion_enabled',
    ];

    protected $hidden = [
        'otp_api_key',
    ];

    protected $casts = [
        'is_maintenance' => 'boolean',
        'otp_enabled' => 'boolean',
        'profile_completion_enabled' => 'boolean',
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
     * Whether an Arqam API key is currently set — exposed instead of the raw
     * key, which is never returned to the client.
     */
    public function getOtpApiKeyConfiguredAttribute(): bool
    {
        return filled($this->attributes['otp_api_key'] ?? null);
    }

    /**
     * Last 4 characters of the configured key, for admin confirmation only.
     */
    public function getOtpApiKeyPreviewAttribute(): ?string
    {
        $key = $this->attributes['otp_api_key'] ?? null;

        return $key ? '••••'.substr($key, -4) : null;
    }

    /** The Arqam API key used for sending. */
    public function resolvedOtpApiKey(): ?string
    {
        return $this->attributes['otp_api_key'] ?? null;
    }

    /**
     * Get the singleton instance of SystemSetting.
     */
    /** Container-scoped so the seven callers in one request cost one read. */
    private const CACHE_KEY = 'system.settings';

    public static function get(): self
    {
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        // refresh() only on creation: firstOrCreate hands back just the
        // attributes it was given, so a new row would miss every nullable column
        $settings = self::firstOrCreate(
            ['id' => 'default'],
            [
                'app_name' => 'رحلة فارس',
                'is_maintenance' => false,
                'otp_enabled' => true,
                // Arqam's documented endpoint; the key is entered from the dashboard
                'otp_base_url' => 'https://otp.arqam.tech/api',
                'profile_completion_enabled' => false,
            ]
        );

        if ($settings->wasRecentlyCreated) {
            $settings->refresh();
        }

        app()->instance(self::CACHE_KEY, $settings);

        return $settings;
    }

    /** Call after writing settings so the next read sees them. */
    public static function forgetCached(): void
    {
        app()->forgetInstance(self::CACHE_KEY);
    }
}
