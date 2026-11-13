<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_comments', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('user_id');
            $table->string('status', 20)->default('pending')->after('comment');
            $table->foreignId('moderated_by')->nullable()->after('status')->constrained('admins')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');

            $table->index(['product_id', 'status']);
        });

        // Comments posted before moderation existed were already public.
        DB::table('product_comments')->update(['status' => 'approved', 'moderated_at' => now()]);

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->nullable()->after('low_stock_threshold');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'rating_avg')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['rating_avg', 'rating_count']);
            });
        }

        // MySQL drops the implicit product_id foreign-key index once the composite one can serve it.
        $hasProductIndex = collect(Schema::getIndexes('product_comments'))->contains(fn (array $index) => $index['columns'] === ['product_id']);

        Schema::table('product_comments', function (Blueprint $table) use ($hasProductIndex) {
            if (!$hasProductIndex) {
                $table->index('product_id');
            }
            $table->dropIndex(['product_id', 'status']);
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn(['rating', 'status', 'moderated_at']);
        });
    }
};
