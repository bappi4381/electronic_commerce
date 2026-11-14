<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Discount used only while the flash campaign runs; null = the product's own discount is the flash discount.
            $table->decimal('flash_discount', 5, 2)->nullable()->after('discount');
            // The regular discount, parked here while flash_discount is applied, restored when the campaign stops.
            $table->decimal('pre_flash_discount', 5, 2)->nullable()->after('flash_discount');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['flash_discount', 'pre_flash_discount']);
        });
    }
};
