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
        Schema::create('graduation_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Семейна, Спомен, Луксозна
            $table->decimal('price', 8, 2);                  // 180.00
            $table->string('description')->nullable();       // Кратко описание под цената
            $table->json('features');                        // ["1 час снимане", "25 снимки", ...]
            $table->boolean('is_featured')->default(false);  // Highlighted card
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graduation_packages');
    }
};
