<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('service_type');
            $table->string('start_time', 5)->nullable()->after('event_date');
            $table->string('end_time', 5)->nullable()->after('start_time');
            $table->foreignId('team_member_id')->nullable()->after('status')
                ->constrained('team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_member_id');
            $table->dropColumn(['event_date', 'start_time', 'end_time']);
        });
    }
};
