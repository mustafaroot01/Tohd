<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * otp_provider/otp_api_key/otp_sender_id were never actually consumed by the
     * OTP send pipeline (which is fixed to OTPIQ via config/otp.php + .env) —
     * keeping them in the settings UI misled admins into thinking they controlled
     * real behavior. otp_enabled and otp_expiry_minutes are genuinely wired now.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['otp_provider', 'otp_api_key', 'otp_sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('otp_provider')->default('native')->after('otp_enabled');
            $table->string('otp_api_key')->nullable()->after('otp_provider');
            $table->string('otp_sender_id')->nullable()->after('otp_api_key');
        });
    }
};
