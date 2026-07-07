<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add parent_id column for infinite nesting (if not already there)
        if (!Schema::hasColumn('categories', 'parent_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade')->after('id');
            });
        }

        $driver = DB::connection()->getDriverName();

        // Convert existing name text to JSON format if MySQL is used
        if ($driver === 'mysql') {
            DB::statement("UPDATE categories SET name = JSON_OBJECT('en', name) WHERE name IS NOT NULL AND NOT JSON_VALID(name)");
        }

        // Only do the rename trick if name_translations column doesn't exist yet.
        // SQLite does not support DROP COLUMN easily, so skip this transform there.
        if ($driver === 'mysql' && !Schema::hasColumn('categories', 'name_translations')) {
            DB::statement("ALTER TABLE categories ADD COLUMN name_translations LONGTEXT NULL");
            DB::statement("UPDATE categories SET name_translations = name");
            DB::statement("ALTER TABLE categories DROP COLUMN name");
            DB::statement("ALTER TABLE categories CHANGE name_translations name LONGTEXT NOT NULL");
        }

        // Drop subcategory_id foreign key and column from products BEFORE dropping subcategories
        if ($driver === 'mysql' && Schema::hasColumn('products', 'subcategory_id')) {
            Schema::table('products', function (Blueprint $table) {
                try { $table->dropForeign(['subcategory_id']); } catch (\Exception $e) {}
                $table->dropColumn('subcategory_id');
            });
        }

        // Drop subcategories only when MySQL supports it cleanly
        if ($driver === 'mysql') {
            Schema::dropIfExists('subcategories');
        }
    }

    public function down()
    {
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->onDelete('set null');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'name_bn', 'description_bn']);
        });
    }
};
