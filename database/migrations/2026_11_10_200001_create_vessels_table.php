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
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->string('name', 150);
            $table->string('imo_number', 20)->unique();
            $table->string('mmsi', 20)->nullable()->unique();
            $table->string('flag_country', 100)->default('Panama');
            $table->string('vessel_type', 50)->default('CONTAINER_SHIP');
            $table->unsignedInteger('capacity_teu')->nullable(); // Twenty-foot Equivalent Unit
            $table->string('status', 50)->default('UNDERWAY'); // UNDERWAY, MOORED, ANCHORED, MAINTENANCE
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->decimal('last_speed_knots', 5, 2)->nullable();
            $table->decimal('last_heading_degrees', 5, 2)->nullable();
            $table->timestamp('last_position_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('imo_number');
            $table->index('mmsi');
            $table->index('carrier_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
