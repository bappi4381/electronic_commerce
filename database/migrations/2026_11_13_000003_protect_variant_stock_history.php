<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('stock')->index();
        });

        // Stock history must never disappear with a variant.
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->foreign('variant_id')->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->foreign('variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
