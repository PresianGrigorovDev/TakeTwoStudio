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
        Schema::create('service_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->enum('input_type', ['checkbox', 'radio', 'number'])->default('checkbox');
            $table->string('group_name_bg', 100)->nullable();
            $table->string('label_bg', 100);
            $table->decimal('price_eur', 10, 2);
            $table->string('description_bg', 255)->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_extras');
    }
};
