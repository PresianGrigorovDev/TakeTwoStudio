<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Where the team has to be for a calendar entry (hall, address, school...). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'location')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('location')->nullable()->after('end_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'location')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
