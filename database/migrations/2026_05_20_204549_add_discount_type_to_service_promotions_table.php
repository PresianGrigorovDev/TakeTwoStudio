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
        Schema::table('service_promotions', function (Blueprint $table) {
            $table->string('discount_type')->default('percent')->after('discount_percent');
            $table->decimal('original_price', 10, 2)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('original_price');
            $table->integer('discount_percent')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_promotions', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'original_price', 'discount_amount']);
        });
    }
};
