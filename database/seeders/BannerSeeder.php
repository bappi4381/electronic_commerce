<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::query()->delete();

        $banners = [
            [
                'title' => 'The Future of Sound',
                'subtitle' => 'Premium Audio Collection 2026',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=1920&auto=format&fit=crop', // External for placeholder
                'link' => '/products',
                'type' => 'hero',
                'order' => 1,
                'status' => 1
            ],
            [
                'title' => 'Gaming Unleashed',
                'subtitle' => 'Next-Gen RTX 50 Series Laptops',
                'image' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?q=80&w=1920&auto=format&fit=crop',
                'link' => '/products',
                'type' => 'hero',
                'order' => 2,
                'status' => 1
            ]
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }
}
