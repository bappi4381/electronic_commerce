<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 191);
            $table->string('normalized', 191)->index();
            $table->unsignedInteger('results_count')->default(0);
            $table->string('lang', 5)->nullable();
            $table->string('script', 10)->nullable();
            $table->string('source', 20)->default('api');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['results_count', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
