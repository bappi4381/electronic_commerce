<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Courier data lives in order_couriers (one row per order) and courier_logs:
 *  - order_couriers: when the parcel was sent, the last failed attempt, the last status check;
 *    a unique order_id makes "one booking per order" a database rule.
 *  - courier_logs: every API call and webhook with its request, response and HTTP status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_couriers', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('shipping_charge');
            $table->timestamp('failed_at')->nullable()->after('sent_at');
            $table->text('last_error')->nullable()->after('failed_at');
            $table->timestamp('status_checked_at')->nullable()->after('last_error');
            $table->unique('order_id');
            $table->index(['courier_name', 'consignment_id']);
        });

        DB::table('order_couriers')->whereNotNull('consignment_id')->whereNull('sent_at')->update(['sent_at' => DB::raw('created_at')]);

        Schema::table('courier_logs', function (Blueprint $table) {
            $table->string('action', 40)->nullable()->after('order_id');
            $table->string('status_to')->nullable()->change();
            $table->json('request_payload')->nullable()->after('payload');
            $table->json('response_payload')->nullable()->after('request_payload');
            $table->unsignedSmallInteger('status_code')->nullable()->after('response_payload');
            $table->index(['order_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::table('courier_logs', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'action']);
            $table->dropColumn(['action', 'request_payload', 'response_payload', 'status_code']);
        });
        DB::table('courier_logs')->whereNull('status_to')->update(['status_to' => '']);
        Schema::table('courier_logs', function (Blueprint $table) {
            $table->string('status_to')->nullable(false)->change();
        });

        Schema::table('order_couriers', function (Blueprint $table) {
            // The order_id foreign key needs an index once the unique one is gone.
            $table->index('order_id', 'order_couriers_order_id_index');
            $table->dropUnique(['order_id']);
            $table->dropIndex(['courier_name', 'consignment_id']);
            $table->dropColumn(['sent_at', 'failed_at', 'last_error', 'status_checked_at']);
        });
    }
};
