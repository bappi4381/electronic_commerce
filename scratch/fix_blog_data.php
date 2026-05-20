<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Article;

$cat = Category::firstOrCreate(['name' => 'Tech News', 'type' => 'blog']);
Article::query()->update(['category_id' => $cat->id]);

echo "Fixed: Created Tech News category and assigned all articles to it.\n";
