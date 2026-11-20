<?php

use App\Models\Order;
use App\Support\OrderCalendar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'is_primary_for_order')) {
            Schema::table('bookings', fn (Blueprint $table) => $table->boolean('is_primary_for_order')->nullable());
        }

        if (! Schema::hasIndex('bookings', ['order_id', 'is_primary_for_order'], 'unique')) {
            Schema::table('bookings', fn (Blueprint $table) => $table->unique(['order_id', 'is_primary_for_order']));
        }

        $columns = collect(Schema::getColumns('bookings'))->keyBy('name');
        if (! $columns['start_time']['nullable'] || ! $columns['end_time']['nullable']) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('start_time', 5)->nullable()->change();
                $table->string('end_time', 5)->nullable()->change();
            });
        }

        Order::withoutEvents(function () {
            Order::where(fn ($orders) => $orders->whereNotNull('event_date')->orWhereHas('bookings'))
                ->eachById(fn (Order $order) => DB::transaction(fn () => OrderCalendar::connectExisting(
                    Order::query()->lockForUpdate()->findOrFail($order->id)
                )));
        });
    }

    public function down(): void
    {
        // Retain dated order entries when restoring the older, time-based calendar schema.
        DB::table('bookings')->whereNull('start_time')->update(['start_time' => '']);
        DB::table('bookings')->whereNull('end_time')->update(['end_time' => '']);

        // MariaDB may have replaced the foreign key's supporting index with the new unique index.
        if (! Schema::hasIndex('bookings', ['order_id'])) {
            Schema::table('bookings', fn (Blueprint $table) => $table->index('order_id'));
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['order_id', 'is_primary_for_order']);
            $table->dropColumn('is_primary_for_order');
            $table->string('start_time', 5)->nullable(false)->change();
            $table->string('end_time', 5)->nullable(false)->change();
        });
    }
};
