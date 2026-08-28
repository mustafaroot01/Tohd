<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registration moves to Arqam's WhatsApp OTP.
 *
 * The provider generates, delivers and checks the code, so the old `otps`
 * table (our own hashed codes + attempt counters) goes; what remains is one
 * row per sent code holding Arqam's message id. Registration itself no longer
 * creates an account before the code is proven — the signup waits in the cache.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_verifications')) {
            Schema::create('phone_verifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('phone', 20);
                $table->string('message_id')->nullable();
                $table->string('purpose', 20)->default('register');
                $table->dateTime('verified_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();

                $table->index(['phone', 'purpose']);
                $table->index('expires_at');
            });
        }

        Schema::dropIfExists('otps');

        Schema::table('system_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('system_settings', 'otp_base_url')) {
                $table->string('otp_base_url')->nullable()->after('otp_enabled');
            }
            if (Schema::hasColumn('system_settings', 'otp_expiry_minutes')) {
                $table->dropColumn('otp_expiry_minutes');
            }
        });

        // an existing install gets Arqam's documented endpoint; only the key is left to enter
        DB::table('system_settings')->whereNull('otp_base_url')->update(['otp_base_url' => 'https://otp.arqam.tech/api']);
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');

        Schema::table('system_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('system_settings', 'otp_base_url')) {
                $table->dropColumn('otp_base_url');
            }
            if (! Schema::hasColumn('system_settings', 'otp_expiry_minutes')) {
                $table->unsignedSmallInteger('otp_expiry_minutes')->default(5);
            }
        });

        // otps is not recreated: its hashed codes were single-use and are all expired.
    }
};
