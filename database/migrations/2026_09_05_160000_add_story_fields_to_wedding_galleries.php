<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Turns wedding galleries into case-study pages (/svatbi/{slug}) with a venue, story and film. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_galleries', function (Blueprint $table) {
            $table->string('venue', 150)->nullable()->after('event_date');
            $table->string('location', 100)->nullable()->after('venue');
            $table->text('description')->nullable()->after('location');
            $table->text('couple_quote')->nullable()->after('description');
            $table->string('video_url', 255)->nullable()->after('couple_quote');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_galleries', function (Blueprint $table) {
            $table->dropColumn(['venue', 'location', 'description', 'couple_quote', 'video_url']);
        });
    }
};
