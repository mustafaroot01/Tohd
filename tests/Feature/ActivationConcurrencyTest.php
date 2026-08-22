<?php

namespace Tests\Feature;

use App\Models\ActivationCode;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A guard around the single most expensive defect this project had.
 *
 * RedeemActivationAction wraps the read in `lockForUpdate()`, but SQLite's
 * grammar compiles that to an empty string — the lock silently disappeared and
 * two people redeeming the same serial at the same moment could both get a
 * subscription. Verified before the move to MySQL; six concurrent redemptions
 * of one code now produce exactly one assignment.
 *
 * These tests fail loudly if anyone points the app back at a driver that cannot
 * take a row lock, which would reopen the hole without any other symptom.
 */
class ActivationConcurrencyTest extends TestCase
{
    public function test_the_configured_driver_emits_a_real_row_lock(): void
    {
        $sql = ActivationCode::query()->where('code', 'X')->lockForUpdate()->toSql();

        $this->assertStringContainsStringIgnoringCase(
            'for update',
            $sql,
            'lockForUpdate() compiled to nothing — this driver cannot protect activation redemption '
            .'against a double redeem. Driver in use: '.DB::connection()->getDriverName()
        );
    }

    public function test_the_driver_supports_transactional_row_locking(): void
    {
        $driver = DB::connection()->getDriverName();

        $this->assertContains(
            $driver,
            ['mysql', 'mariadb', 'pgsql', 'sqlsrv'],
            "Driver [{$driver}] has no row-level locking. Activation redemption relies on it."
        );
    }

    public function test_redeem_action_still_wraps_the_read_in_a_transaction(): void
    {
        $source = file_get_contents(app_path('Actions/Activations/RedeemActivationAction.php'));

        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
    }
}
