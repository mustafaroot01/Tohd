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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('id')->primary(); // Predictable static primary key id like 'default'
            $table->string('app_name')->default('رحلة فارس');
            $table->string('app_logo')->nullable();
            $table->boolean('is_maintenance')->default(false);
            $table->boolean('otp_enabled')->default(true);
            $table->string('otp_provider')->default('native'); // native, twilio, unifonic
            $table->string('otp_api_key')->nullable();
            $table->string('otp_sender_id')->nullable();
            $table->integer('otp_expiry_minutes')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
