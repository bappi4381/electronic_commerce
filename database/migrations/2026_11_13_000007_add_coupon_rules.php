<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('min_order_amount', 10, 2)->nullable()->after('value');
            $table->decimal('max_discount', 10, 2)->nullable()->after('min_order_amount');
            $table->unsignedInteger('usage_limit')->nullable()->after('max_discount');
            $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
            $table->unsignedInteger('used_count')->default(0)->after('per_user_limit');
            $table->dateTime('starts_at')->nullable()->after('used_count');
            $table->dateTime('ends_at')->nullable()->after('starts_at');
        });

        DB::table('coupons')->whereNotNull('expiry_date')->update([
            'ends_at' => DB::raw("CONCAT(expiry_date, ' 23:59:59')"),
        ]);

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');

        Schema::table('coupons', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('value');
        });

        DB::table('coupons')->whereNotNull('ends_at')->update(['expiry_date' => DB::raw('DATE(ends_at)')]);

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['min_order_amount', 'max_discount', 'usage_limit', 'per_user_limit', 'used_count', 'starts_at', 'ends_at']);
        });
    }
};
