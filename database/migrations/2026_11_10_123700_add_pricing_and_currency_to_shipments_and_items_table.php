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
            $table->decimal('total_declared_value', 14, 2)->default(0.00)->after('total_volume_cbm');
            $table->string('currency', 3)->default('USD')->after('total_declared_value');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->default(0.00)->after('weight_kg');
            $table->decimal('total_price', 14, 2)->default(0.00)->after('unit_price');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable()->after('country');
            $table->string('currency_symbol', 10)->nullable()->after('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol']);
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total_price']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['total_declared_value', 'currency']);
        });
    }
};
