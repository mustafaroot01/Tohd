<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('address')->nullable()->after('phone_verified_at');
            $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn(['email', 'email_verified_at']);
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->unique('phone');
            $table->string('status', 20)->default('UNVERIFIED')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at', 'address', 'last_activity_at']);
            $table->string('status', 20)->default('ACTIVE')->change();
        });
    }
};
