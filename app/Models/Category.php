<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class Category extends Model
{
     use HasFactory, \Spatie\Translatable\HasTranslations;

    /** Fashion → Cloth → Winter → Men is four levels. */
    public const MAX_DEPTH = 4;

    protected $fillable = ['parent_id', 'name', 'image', 'type', 'icon', 'color'];

    public $translatable = ['name'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Every category of one type, nested, with a rolled-up product count.
     * $with is applied to the flat query, so relations such as attributes stay loaded on every node.
     */
    public static function nested(?string $type = null, array $with = []): Collection
    {
        $byParent = static::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->withCount('products')
            ->with($with)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (self $category) => $category->parent_id ?: 0);

        $attach = function (int $parentId, int $depth) use (&$attach, $byParent): Collection {
            return $byParent->get($parentId, collect())->each(function (self $category) use ($attach, $depth) {
                $category->setAttribute('tree_depth', $depth);
                $category->setRelation('children', $attach($category->id, $depth + 1));
                $category->setAttribute('total_count', (int) $category->products_count + (int) $category->children->sum('total_count'));
            })->values();
        };

        return $attach(0, 1);
    }

    public static function findNested(Collection $nodes, int $id): ?self
    {
        foreach ($nodes as $node) {
            if ($node->id === $id) {
                return $node;
            }
            if ($found = static::findNested($node->children, $id)) {
                return $found;
            }
        }

        return null;
    }

    public static function flatten(Collection $nodes): Collection
    {
        return $nodes->flatMap(fn (self $node) => collect([$node])->concat(static::flatten($node->children)));
    }

    /** This category plus everything nested inside it. */
    public function loadedIds(): array
    {
        return array_merge([$this->id], $this->children->flatMap(fn (self $child) => $child->loadedIds())->all());
    }

    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];
        $seen = [$this->id => true];

        while ($frontier !== []) {
            $frontier = static::whereIn('parent_id', $frontier)->pluck('id')
                ->reject(fn ($id) => isset($seen[$id]))->all();
            foreach ($frontier as $id) {
                $seen[$id] = true;
            }
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    public static function branchIds(int $id): array
    {
        return static::find($id)?->selfAndDescendantIds() ?? [$id];
    }

    /** Root categories are depth 1. */
    public function depth(): int
    {
        $depth = 1;
        $parentId = $this->parent_id;
        $seen = [];

        while ($parentId && !isset($seen[$parentId]) && $depth < 12) {
            $seen[$parentId] = true;
            $parentId = static::whereKey($parentId)->value('parent_id');
            $depth++;
        }

        return $depth;
    }

    /** Levels below this category. A leaf is 0. */
    public function descendantDepth(): int
    {
        $depth = 0;
        $frontier = [$this->id];

        while ($frontier !== [] && $depth < 12) {
            $frontier = static::whereIn('parent_id', $frontier)->pluck('id')->all();
            if ($frontier === []) {
                break;
            }
            $depth++;
        }

        return $depth;
    }

    /** This category and its parents, root first. Walks loaded parents when present. */
    public function lineage(): Collection
    {
        $line = collect();
        $node = $this;
        $guard = 0;

        while ($node && $guard++ < 12) {
            $line->prepend($node);
            $node = $node->parent;
        }

        return $line;
    }

    /** Why $parentId cannot be this category's parent, or null when it can. */
    public function parentRejection(int $parentId): ?string
    {
        if ($this->exists && $parentId === $this->id) {
            return 'A category cannot be its own parent.';
        }

        $parent = static::find($parentId);
        if (!$parent) {
            return 'Choose a category that exists.';
        }

        if ($this->exists && in_array($parentId, $this->selfAndDescendantIds(), true)) {
            return 'A category cannot be moved under one of its own subcategories.';
        }

        $extra = $this->exists ? $this->descendantDepth() : 0;
        if ($parent->depth() + 1 + $extra > self::MAX_DEPTH) {
            return 'Categories can only be nested ' . self::MAX_DEPTH . ' levels deep. Choose a category closer to the top.';
        }

        return null;
    }

    public function loadedDescendantCount(): int
    {
        return $this->children->sum(fn (self $child) => 1 + $child->loadedDescendantCount());
    }
    
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'category_attribute')
                    ->withTimestamps();
    }

    /**
     * Reason this category cannot be deleted, or null when it is safe to delete.
     * Archived products count because they still reference the category.
     */
    public function deletionBlocker(): ?string
    {
        $name = $this->getTranslation('name', 'en');

        if ($count = $this->children()->count()) {
            return "\"{$name}\" has {$count} subcategor" . ($count === 1 ? 'y' : 'ies') . '. Move or delete them first.';
        }

        if ($count = $this->products()->withTrashed()->count()) {
            return "\"{$name}\" still has {$count} product(s), including archived ones. Move them to another category first.";
        }

        if ($count = $this->articles()->count()) {
            return "\"{$name}\" still has {$count} article(s). Move them to another category first.";
        }

        return null;
    }
}
