<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QR stickers of the /igra game, created by the studio in Filament (Маркетинг → QR стикери).
 * Each sticker is a (target, loc) pair; the public link and the QR code are derived from it,
 * and game_events rows are matched on the same two columns for the statistics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_stickers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);            // e.g. "Морска градина – главен вход"
            $table->string('target', 10);           // prom | wedding
            $table->string('loc', 40);              // slug in the QR address: /igra?target=…&loc=<loc>
            $table->text('notes')->nullable();
            $table->date('placed_at')->nullable();
            $table->timestamps();
            $table->unique(['target', 'loc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_stickers');
    }
};
