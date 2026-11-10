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
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('container_number', 50)->nullable()->after('shipment_number');
            $table->foreignId('vessel_id')->nullable()->after('carrier_id')->constrained('vessels')->nullOnDelete();

            $table->index('container_number');
            $table->index('vessel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropColumn(['container_number', 'vessel_id']);
        });
    }
};
