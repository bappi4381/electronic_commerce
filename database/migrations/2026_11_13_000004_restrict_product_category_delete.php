<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceCategoryForeignKey('restrict');
    }

    public function down(): void
    {
        $this->replaceCategoryForeignKey('cascade');
    }

    // MyISAM tables keep no foreign keys, so there may be nothing to drop.
    private function replaceCategoryForeignKey(string $onDelete): void
    {
        $exists = collect(Schema::getForeignKeys('products'))
            ->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['category_id']);

        Schema::table('products', function (Blueprint $table) use ($exists, $onDelete) {
            if ($exists) {
                $table->dropForeign(['category_id']);
            }
            $table->foreign('category_id')->references('id')->on('categories')->onDelete($onDelete);
        });
    }
};
