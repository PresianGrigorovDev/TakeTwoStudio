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
        Schema::create('commercial_portfolio_photos', function (Blueprint $table) {
            $table->id();
            $table->string('image_path', 255);
            $table->string('sub_category', 50)->nullable();
            $table->string('alt_text_bg', 255)->nullable();
            $table->text('description_bg')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_portfolio_photos');
    }
};
