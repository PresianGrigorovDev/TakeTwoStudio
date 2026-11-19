<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_accounts')) {
            Schema::create('finance_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('kind', 16);
                $table->foreignId('team_member_id')->nullable()->unique()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('finance_projects')) {
            Schema::create('finance_projects', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('kind', 16);
                $table->foreignId('order_id')->nullable()->unique()->constrained()->nullOnDelete();
                $table->foreignId('booking_id')->nullable()->unique()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('agreed_cents')->nullable();
                $table->json('shares');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('finance_movements')) {
            Schema::create('finance_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finance_project_id')->nullable()->constrained()->restrictOnDelete();
                $table->string('kind', 20);
                $table->string('payment_stage', 16)->nullable();
                $table->date('occurred_on')->index();
                $table->unsignedBigInteger('amount_cents');
                $table->foreignId('from_account_id')->nullable()->constrained('finance_accounts')->restrictOnDelete();
                $table->foreignId('to_account_id')->nullable()->constrained('finance_accounts')->restrictOnDelete();
                $table->foreignId('handled_by_id')->nullable()->constrained('finance_accounts')->restrictOnDelete();
                $table->string('method', 16)->nullable();
                $table->string('description', 500)->nullable();
                $table->json('details')->nullable();
                $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('recorded_by_name');
                $table->timestamp('voided_at')->nullable();
                $table->foreignId('voided_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('void_reason', 500)->nullable();
                $table->timestamps();
                $table->index(['finance_project_id', 'occurred_on']);
            });
        }

        $now = now();
        DB::table('finance_accounts')->insertOrIgnore([
            ['key' => 'cash', 'name' => 'Обща каса', 'kind' => 'cash', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'bank', 'name' => 'Фирмена сметка', 'kind' => 'bank', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('team_members')->select('id', 'name')->eachById(function ($member) use ($now) {
            DB::table('finance_accounts')->insertOrIgnore([
                'key' => 'member:'.$member->id, 'kind' => 'member', 'name' => $member->name,
                'team_member_id' => $member->id, 'created_at' => $now, 'updated_at' => $now,
            ]);
        });

        if (! DB::table('finance_projects')->where('kind', 'studio')->exists()) {
            DB::table('finance_projects')->insert([
                'title' => 'Общи разходи', 'kind' => 'studio', 'shares' => '[]',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_movements');
        Schema::dropIfExists('finance_projects');
        Schema::dropIfExists('finance_accounts');
    }
};
