<?php

namespace App\Providers;

use App\Contracts\SmsGatewayInterface;
use App\Services\Sms\OtpiqSmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGatewayInterface::class, OtpiqSmsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->warnIfSqliteInProduction();
    }

    /**
     * SQLite has no true row-level locking (Laravel's grammar drops
     * `lockForUpdate()` silently on this driver), so the double-redemption
     * protection in RedeemActivationAction is not guaranteed under real
     * concurrency. The supported production database is MySQL/PostgreSQL.
     */
    protected function warnIfSqliteInProduction(): void
    {
        if ($this->app->environment('production') && config('database.default') === 'sqlite') {
            Log::warning('SQLite is configured as the database driver in a production environment. '.
                'Row-level locking (lockForUpdate) used to protect activation-code redemption from '.
                'double-use does not work reliably on SQLite — MySQL or PostgreSQL is required for production.');
        }
    }
}
