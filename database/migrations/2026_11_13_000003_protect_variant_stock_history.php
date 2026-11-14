<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variants', 'is_active')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('stock')->index();
            });
        }

        // Stock history must never disappear with a variant.
        $this->replaceVariantForeignKey('restrict');
    }

    public function down(): void
    {
        $this->replaceVariantForeignKey('cascade');

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }

    // MyISAM tables keep no foreign keys, so there may be nothing to drop.
    private function replaceVariantForeignKey(string $onDelete): void
    {
        $exists = collect(Schema::getForeignKeys('stock_movements'))
            ->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['variant_id']);

        Schema::table('stock_movements', function (Blueprint $table) use ($exists, $onDelete) {
            if ($exists) {
                $table->dropForeign(['variant_id']);
            }
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete($onDelete);
        });
    }
};
