<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); 
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('status')->default('pending'); 
            
            $table->string('invoice_path')->nullable(); 
            $table->string('shipping_address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable(); 
            $table->decimal('delivery_charge', 8, 2)->default(0); 
            $table->string('payment_method')->default('cod');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
