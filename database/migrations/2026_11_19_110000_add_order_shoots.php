<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PACKAGE_TABLES = [
        'architectural_packages',
        'automotive_packages',
        'commercial_packages',
        'event_packages',
        'family_packages',
        'graduation_packages',
        'portrait_packages',
        'prom_packages',
        'service_packages',
    ];

    public function up(): void
    {
        foreach (self::PACKAGE_TABLES as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->json('shoot_plan')->nullable());
        }

        Schema::table('orders', fn (Blueprint $table) => $table->string('shoot_package_name')->nullable());

        Schema::create('order_shoots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('kind', 16);
            $table->string('name');
            $table->date('shot_on')->nullable();
            $table->foreignId('shot_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['order_id', 'shot_on', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shoots');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('shoot_package_name'));

        foreach (self::PACKAGE_TABLES as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('shoot_plan'));
        }
    }
};
