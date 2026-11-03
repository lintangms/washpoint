<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Anggota 2: laundry_orders
     */
    public function up(): void
    {
        if (!Schema::hasTable('laundry_orders')) {
            Schema::create('laundry_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('service_type_id')->constrained('service_types');
                $table->string('customer_name', 100);
                $table->string('customer_phone', 25);
                $table->decimal('weight_kg', 8, 2);
                $table->unsignedInteger('total_price');
                $table->string('status', 30)->default('received');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laundry_orders');
    }
};

