<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Re-introduces the admin-configurable OTPIQ API key (removed earlier
     * because it was never actually wired to the send pipeline). This time
     * OtpiqSmsGateway genuinely reads it, falling back to .env/config('otp.*')
     * when unset — no fake "provider" dropdown, since OTPIQ is the only
     * implemented gateway, and no sender-ID field, since this account's
     * OTPIQ plan doesn't support one.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('otp_api_key')->nullable()->after('otp_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['otp_api_key']);
        });
    }
};
