<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplementary profile data, gathered after the account exists.
 *
 * Registration stays short — name, phone, password, code — and the rest is a
 * second step the operator can switch on from the dashboard
 * (system_settings.profile_completion_enabled). While the switch is off the
 * feature is invisible: its endpoints answer 404 and no response mentions it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            // hidden ones stay in the table so existing profiles keep their link
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('subscriber_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // one profile per account
            $table->foreignUuid('subscriber_id')->unique()->constrained('subscribers')->cascadeOnDelete();
            $table->foreignUuid('governorate_id')->nullable()->constrained('governorates')->nullOnDelete();

            $table->string('gender', 10)->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->unsignedTinyInteger('family_order')->nullable();
            $table->string('delivery_type', 20)->nullable();

            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('system_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('system_settings', 'profile_completion_enabled')) {
                $table->boolean('profile_completion_enabled')->default(false)->after('otp_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('system_settings', 'profile_completion_enabled')) {
                $table->dropColumn('profile_completion_enabled');
            }
        });

        Schema::dropIfExists('subscriber_profiles');
        Schema::dropIfExists('governorates');
    }
};
