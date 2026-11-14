<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Payment records are financial history and must survive their order.
        $this->replaceOrderForeignKey('restrict');
    }

    public function down(): void
    {
        $this->replaceOrderForeignKey('cascade');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    // MyISAM tables keep no foreign keys, so there may be nothing to drop.
    private function replaceOrderForeignKey(string $onDelete): void
    {
        $exists = collect(Schema::getForeignKeys('payments'))
            ->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['order_id']);

        Schema::table('payments', function (Blueprint $table) use ($exists, $onDelete) {
            if ($exists) {
                $table->dropForeign(['order_id']);
            }
            $table->foreign('order_id')->references('id')->on('orders')->onDelete($onDelete);
        });
    }
};
