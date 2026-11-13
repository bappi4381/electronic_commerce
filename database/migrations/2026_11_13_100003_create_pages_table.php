<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->json('title');
            $table->json('content')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $defaults = [
            'about'         => ['About Us', 'আমাদের সম্পর্কে'],
            'faq'           => ['Frequently Asked Questions', 'সাধারণ জিজ্ঞাসা'],
            'privacy'       => ['Privacy Policy', 'গোপনীয়তা নীতি'],
            'terms'         => ['Terms & Conditions', 'শর্তাবলী'],
            'return-policy' => ['Return & Refund Policy', 'রিটার্ন ও রিফান্ড নীতি'],
        ];

        $order = 1;
        foreach ($defaults as $slug => [$en, $bn]) {
            DB::table('pages')->insert([
                'slug' => $slug,
                'title' => json_encode(['en' => $en, 'bn' => $bn], JSON_UNESCAPED_UNICODE),
                'content' => json_encode(['en' => "<p>{$en} content coming soon.</p>", 'bn' => ''], JSON_UNESCAPED_UNICODE),
                'is_published' => false,
                'sort_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
