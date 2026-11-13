<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->string('logo')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('brands')->restrictOnDelete();
        });

        // Turn the distinct free-text brands into brand rows (case-insensitive, trimmed). Counted in PHP because
        // the column collation is case-insensitive; the most used spelling of each brand becomes its name.
        $names = DB::table('products')->whereNotNull('brand')->where('brand', '!=', '')->pluck('brand')
            ->countBy()->sortDesc()->keys();
        $idsByKey = [];

        foreach ($names as $raw) {
            $name = trim(preg_replace('/\s+/', ' ', $raw));
            $key = mb_strtolower($name);

            if ($name === '' || isset($idsByKey[$key])) {
                continue;
            }

            $slug = Str::slug($name) ?: 'brand';
            $base = $slug;
            for ($i = 2; DB::table('brands')->where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }

            $idsByKey[$key] = DB::table('brands')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($names as $raw) {
            $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $raw)));
            if (isset($idsByKey[$key])) {
                DB::table('products')->where('brand', $raw)->update(['brand_id' => $idsByKey[$key]]);
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('category_id');
        });

        foreach (DB::table('brands')->get(['id', 'name']) as $brand) {
            DB::table('products')->where('brand_id', $brand->id)->update(['brand' => $brand->name]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });

        Schema::dropIfExists('brands');
    }
};
