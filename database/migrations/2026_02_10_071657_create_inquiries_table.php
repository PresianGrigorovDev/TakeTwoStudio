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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50)->nullable();
            $table->string('customer_name', 100);
            $table->string('customer_email', 100)->nullable();
            $table->string('customer_phone', 30);
            $table->date('event_date')->nullable();
            $table->string('school_class', 100)->nullable();
            $table->text('selected_details')->nullable();
            $table->decimal('final_price_eur', 10, 2)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'booked', 'completed', 'cancelled'])->default('new');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
