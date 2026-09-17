<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anonymous events from the QR sticker mini-game (/igra).
     * Deliberately no updated_at, IP, user agent or user_id: the page sets no
     * cookies and stores no personal data, only the game type, the sticker
     * location, the event, the browser-generated voucher code and a few counters.
     */
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->string('target', 10);               // prom | wedding
            $table->string('loc', 40)->nullable();      // sanitized sticker slug (mg, morska ...)
            $table->string('event', 20);                // scan | win | lose | voucher | share
            $table->string('code', 20)->nullable();     // VN-PROM-XXXX / VN-WED-XXXX (generated in the browser)
            $table->json('meta')->nullable();           // lives_left, seconds, attempts, solved, method, device
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index(['target', 'loc', 'event']);
            $table->index('code');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
