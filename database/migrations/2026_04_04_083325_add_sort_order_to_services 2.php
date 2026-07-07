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
        Schema::table('services', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('is_active');
        });

        // Set initial order based on id
        $services = \App\Models\Service::orderBy('id')->get();
        foreach ($services as $i => $service) {
            $service->update(['sort_order' => $i]);
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
