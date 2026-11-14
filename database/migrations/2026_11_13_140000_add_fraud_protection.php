<?php

use App\Support\BdPhone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('phone_normalized', 11)->nullable()->after('phone');
            $table->string('cart_hash', 64)->nullable()->after('phone_normalized');
            $table->string('placed_ip', 45)->nullable()->after('cart_hash');
            $table->string('checkout_token', 64)->nullable()->unique()->after('placed_ip');
            $table->string('risk_level', 10)->nullable()->after('checkout_token');
            $table->json('risk_flags')->nullable()->after('risk_level');
            $table->timestamp('risk_checked_at')->nullable()->after('risk_flags');
            $table->timestamp('risk_reviewed_at')->nullable()->after('risk_checked_at');
            $table->foreignId('risk_reviewed_by')->nullable()->after('risk_reviewed_at')->constrained('admins')->nullOnDelete();
            $table->decimal('advance_amount', 10, 2)->nullable()->after('risk_reviewed_by');

            $table->index(['phone_normalized', 'created_at']);
            $table->index(['placed_ip', 'created_at']);
            $table->index('risk_level');
        });

        // Existing orders get a normalised phone so their delivery history counts for repeat customers.
        DB::table('orders')->whereNotNull('phone')->select('id', 'phone')->orderBy('id')->chunkById(500, function ($orders) {
            foreach ($orders as $order) {
                if ($phone = BdPhone::normalize($order->phone)) {
                    DB::table('orders')->where('id', $order->id)->update(['phone_normalized' => $phone]);
                }
            }
        });

        Schema::create('customer_risk_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 11)->index();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('cancelled')->default(0);
            $table->decimal('success_rate', 5, 2)->nullable();
            $table->string('source', 30);
            $table->string('risk_level', 10);
            $table->json('raw_response')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        Schema::create('phone_blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 11)->unique();
            $table->string('reason', 255);
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_blacklists');
        Schema::dropIfExists('customer_risk_logs');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized', 'created_at']);
            $table->dropIndex(['placed_ip', 'created_at']);
            $table->dropIndex(['risk_level']);
            $table->dropUnique(['checkout_token']);
            $table->dropConstrainedForeignId('risk_reviewed_by');
            $table->dropColumn(['phone_normalized', 'cart_hash', 'placed_ip', 'checkout_token', 'risk_level', 'risk_flags', 'risk_checked_at', 'risk_reviewed_at', 'advance_amount']);
        });
    }
};
