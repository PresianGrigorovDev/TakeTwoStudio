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
        Schema::table('wedding_galleries', function (Blueprint $table) {
            $table->string('slug')->after('title')->unique()->nullable();
        });

        Schema::table('baptism_galleries', function (Blueprint $table) {
            $table->string('slug')->after('title')->unique()->nullable();
        });

        // Populate existing records
        \App\Models\WeddingGallery::all()->each(function ($item) {
            if (empty($item->slug)) {
                $item->slug = \App\Support\Transliteration::slug($item->title);
                $item->save();
            }
        });

        \App\Models\BaptismGallery::all()->each(function ($item) {
            if (empty($item->slug)) {
                $item->slug = \App\Support\Transliteration::slug($item->title);
                $item->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wedding_galleries', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('baptism_galleries', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
