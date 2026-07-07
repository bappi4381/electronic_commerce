<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $driver = DB::connection()->getDriverName();

        // Step 1: Convert existing text values to valid JSON (MySQL only)
        if ($driver === 'mysql') {
            DB::statement('UPDATE products SET name = JSON_OBJECT("en", name) WHERE name IS NOT NULL AND NOT JSON_VALID(name)');
            DB::statement('UPDATE products SET description = JSON_OBJECT("en", description) WHERE description IS NOT NULL AND NOT JSON_VALID(description)');
        }

        Schema::table('products', function (Blueprint $table) {
            // Drop columns only if they exist
            $columns = ['ram', 'storage', 'battery_capacity', 'screen_size', 'operating_system', 'color', 'stock'];
            $existingCols = array_filter($columns, fn($col) => Schema::hasColumn('products', $col));
            if (!empty($existingCols)) {
                $table->dropColumn(array_values($existingCols));
            }
        });

        // Step 2: Now change the column types to JSON for MySQL only
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY name JSON NOT NULL');
            DB::statement('ALTER TABLE products MODIFY description JSON NULL');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('video_link')->nullable()->after('description');
            $table->integer('low_stock_threshold')->default(5)->after('price');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('ram')->nullable();
            $table->string('storage')->nullable();
            $table->string('battery_capacity')->nullable();
            $table->string('screen_size')->nullable();
            $table->string('operating_system')->nullable();
            $table->string('color')->nullable();
            $table->integer('stock')->default(0);

            $table->dropColumn([
                'name_bn',
                'description_bn',
                'video_link',
                'low_stock_threshold'
            ]);
        });
    }
};
