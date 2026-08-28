<?php

namespace App\Providers;

use App\Support\ApiResponse;
use App\Support\PhoneNumber;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->warnIfSqliteInProduction();
        $this->registerOtpLimiters();
    }

    /**
     * Every code costs a WhatsApp message, and every verify is a guess at a
     * six-digit number — both are capped per phone, not per IP, because whole
     * neighbourhoods share one carrier address.
     */
    protected function registerOtpLimiters(): void
    {
        // Keyed on the phone, never on the address. Whole neighbourhoods in Iraq
        // share one carrier address, so an IP ceiling would lock out families
        // who did nothing. A caller can still send anything at all — an array,
        // an object — so unparsable input gets one shared bucket rather than a
        // string cast that would turn a public route into a 500.
        $phone = function (Request $request) {
            $input = $request->input('phone');

            $normalized = is_string($input) || is_int($input) || is_float($input)
                ? PhoneNumber::normalize((string) $input)
                : null;

            return $normalized ?? 'unparsable';
        };

        $tooMany = fn (string $message) => fn () => ApiResponse::error($message, 'TOO_MANY_REQUESTS', null, 429);

        // Signup and recovery count separately: otherwise a stranger could spend
        // a victim's whole budget on registration attempts — which send nothing
        // once the cooldown refuses them — and lock the victim out of recovering
        // their own password for ten minutes.
        $sendLimit = fn (string $flow) => fn (Request $request) => Limit::perMinutes(10, 4)
            ->by("otp-send:{$flow}:".$phone($request))
            ->response($tooMany('طلبت رموزاً كثيرة، حاول بعد عشر دقائق'));

        RateLimiter::for('otp-send', $sendLimit('signup'));
        RateLimiter::for('otp-send-recovery', $sendLimit('recovery'));

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinutes(10, 8)
            ->by('otp-verify:'.$phone($request))
            ->response($tooMany('حاولت مرات كثيرة، انتظر عشر دقائق ثم أعد المحاولة')));
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
