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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_id')->unique();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->json('specifications')->nullable();
            
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained()->onDelete('set null');
            
            // Tech Specs (Made nullable for flexibility)
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('ram')->nullable();
            $table->string('storage')->nullable();
            $table->string('battery_capacity')->nullable();
            $table->string('screen_size')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('color')->nullable();
            
            // Economics
            $table->decimal('price', 15, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('discounted_price', 15, 2);
            $table->integer('stock')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
