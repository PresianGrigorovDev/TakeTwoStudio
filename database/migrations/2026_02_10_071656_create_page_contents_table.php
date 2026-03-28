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
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page_slug', 50);
            $table->string('section_slug', 50);
            $table->string('field_key', 50);
            $table->text('content_bg');
            $table->text('content_en')->nullable();
            $table->unique(['page_slug', 'section_slug', 'field_key'], 'page_section_field');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
