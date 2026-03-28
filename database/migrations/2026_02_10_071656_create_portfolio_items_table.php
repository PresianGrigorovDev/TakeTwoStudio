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
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('portfolio_categories')->onDelete('cascade');
            $table->string('sub_category', 50)->nullable();
            $table->enum('item_type', ['image', 'video'])->default('image');
            $table->string('file_path', 255);
            $table->string('thumbnail_path', 255)->nullable();
            $table->string('alt_text_bg', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
