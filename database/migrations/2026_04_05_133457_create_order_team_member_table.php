<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_team_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'team_member_id']);
        });

        // Migrate existing team_member_id data to pivot table
        $orders = DB::table('orders')->whereNotNull('team_member_id')->get();
        foreach ($orders as $order) {
            DB::table('order_team_member')->insert([
                'order_id' => $order->id,
                'team_member_id' => $order->team_member_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_member_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('team_member_id')->nullable()->constrained('team_members')->nullOnDelete();
        });

        Schema::dropIfExists('order_team_member');
    }
};
