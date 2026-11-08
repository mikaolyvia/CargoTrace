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
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('event_type', [
                'ORDER_CREATED',
                'BOOKING_CONFIRMED',
                'PICKED_UP',
                'DEPARTED',
                'IN_TRANSIT',
                'ARRIVED',
                'DELIVERED',
                'EXCEPTION',
            ])->index();
            $table->dateTime('event_time')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description');
            $table->string('weather_condition', 100)->nullable();
            $table->decimal('weather_temp_c', 5, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Compound index for fast chronological timeline queries per shipment
            $table->index(['shipment_id', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
