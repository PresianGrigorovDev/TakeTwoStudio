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
        Schema::create('reasons_to_choose', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug', 50)->nullable();
            $table->string('icon_class', 50);
            $table->string('title_bg', 100);
            $table->text('content_bg');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reasons_to_choose');
    }
};
