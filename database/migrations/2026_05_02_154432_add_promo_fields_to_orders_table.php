<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'promo_code_id')) {
                $table->unsignedBigInteger('promo_code_id')->nullable()->after('status');
                // Removed foreign key constraint to prevent hosting compatibility issues
                // $table->foreign('promo_code_id')->references('id')->on('promo_codes')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'promo_code')) {
                $table->string('promo_code')->nullable()->after('promo_code_id')->comment('Snapshot of the code used');
            }
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 8, 2)->nullable()->after('promo_code')->comment('Amount discounted in EUR');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'promo_code_id')) {
                // $table->dropForeign(['promo_code_id']);
                $table->dropColumn('promo_code_id');
            }
            if (Schema::hasColumn('orders', 'promo_code')) {
                $table->dropColumn('promo_code');
            }
            if (Schema::hasColumn('orders', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};
