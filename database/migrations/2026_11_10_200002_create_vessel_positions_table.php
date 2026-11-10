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
        Schema::create('vessel_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained('vessels')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_knots', 5, 2)->nullable();
            $table->decimal('heading_degrees', 5, 2)->nullable();
            $table->string('status', 50)->default('UNDERWAY');
            $table->string('source', 50)->default('AIS_FEED');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['vessel_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vessel_positions');
    }
};
