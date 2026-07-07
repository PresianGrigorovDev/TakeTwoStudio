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
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('image_path');
            $table->string('phone', 30)->nullable()->after('address');
            $table->string('email', 100)->nullable()->after('phone');
            $table->string('egn', 10)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'email', 'egn']);
        });
    }
};
