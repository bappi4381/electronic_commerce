<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name', 100)->nullable()->after('user_id');
            $table->string('customer_email', 150)->nullable()->after('customer_name');
            $table->string('phone', 20)->nullable()->after('customer_email');
            $table->foreignId('coupon_id')->nullable()->after('delivery_charge')->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 50)->nullable()->after('coupon_id');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_code');
            // Set when reserved stock has been put back (cancel, return/refund restock, delete).
            $table->timestamp('stock_released_at')->nullable()->after('payment_status');

            $table->index('status');
            $table->index('payment_status');
            $table->index('phone');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('UPDATE orders o JOIN users u ON u.id = o.user_id
                SET o.customer_name = COALESCE(o.customer_name, u.name),
                    o.customer_email = COALESCE(o.customer_email, u.email),
                    o.phone = COALESCE(o.phone, u.phone)');
        }

        DB::table('orders')->where('status', 'completed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'cancelled')->update(['stock_released_at' => DB::raw('updated_at')]);

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'created_at']);
        });

        DB::table('order_status_histories')->insertUsing(
            ['order_id', 'from_status', 'to_status', 'note', 'created_at'],
            DB::table('orders')->select('id', DB::raw('NULL'), 'status', DB::raw("'Status before history tracking'"), 'created_at')
        );

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, completed, rejected
            $table->string('method', 30)->nullable();          // cash, bkash, nagad, bank, gateway
            $table->string('reference')->nullable();
            $table->boolean('restock')->default(false);
            $table->timestamp('restocked_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('order_couriers', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('order_id')->constrained('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_couriers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
        });

        Schema::dropIfExists('refunds');
        Schema::dropIfExists('order_status_histories');

        DB::table('orders')->where('status', 'delivered')->update(['status' => 'completed']);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['phone']);
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['customer_name', 'customer_email', 'phone', 'coupon_code', 'discount_amount', 'stock_released_at']);
        });
    }
};
