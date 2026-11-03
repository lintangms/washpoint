<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Anggota 4: pickup_schedules
     */
    public function up(): void
    {
        if (!Schema::hasTable('pickup_schedules')) {
            Schema::create('pickup_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('laundry_order_id')->constrained('laundry_orders')->onDelete('cascade');
                $table->dateTime('scheduled_at');
                $table->string('type', 20)->default('pickup');
                $table->string('address', 255);
                $table->string('status', 30)->default('scheduled');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_schedules');
    }
};

