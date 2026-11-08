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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('cargo_type', 100);
            $table->decimal('total_weight_kg', 10, 2)->default(0.00);
            $table->decimal('total_volume_cbm', 10, 2)->default(0.00);
            $table->enum('current_status', [
                'CREATED',
                'BOOKED',
                'PICKED_UP',
                'IN_TRANSIT',
                'DELIVERED',
            ])->default('CREATED')->index();
            $table->dateTime('expected_departure');
            $table->dateTime('expected_arrival')->index();
            $table->dateTime('estimated_arrival')->index();
            $table->dateTime('actual_arrival')->nullable();
            $table->boolean('is_delayed')->default(false)->index();
            $table->decimal('delay_hours', 8, 2)->default(0.00);
            $table->enum('delay_severity', ['NONE', 'LOW', 'MEDIUM', 'HIGH'])->default('NONE')->index();
            $table->timestamps();
            $table->softDeletes();

            // Compound indexes for frequent dashboard & filter queries
            $table->index(['current_status', 'carrier_id']);
            $table->index(['customer_id', 'current_status']);
            $table->index(['is_delayed', 'delay_severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
