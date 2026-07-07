<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path')->nullable();
            $table->string('redirect_url')->nullable()->comment('e.g. /weddings');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('popup_days_interval')->default(7)->comment('Days before showing popup again after closing');
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
