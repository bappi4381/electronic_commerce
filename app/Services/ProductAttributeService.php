<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Support\Collection;

class ProductAttributeService
{
    public function getAllAttributesWithValues(): Collection
    {
        return Attribute::with('values')->get();
    }

    public function getRootCategoriesWithAttributes(): Collection
    {
        return Category::whereNull('parent_id')
            ->with(['children.attributes', 'attributes'])
            ->get();
    }

    public function getAttributesForCategory(?int $categoryId): Collection
    {
        if (!$categoryId) {
            return $this->getAllAttributesWithValues();
        }

        return Attribute::with('values')
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $categoryId))
            ->get();
    }

    public function toJsAttributes(Collection $attributes): array
    {
        return $attributes->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'type' => $attribute->type,
                'is_filterable' => $attribute->is_filterable,
                'values' => $attribute->values->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->toArray(),
            ];
        })->toArray();
    }

    public function categoryAttributeMappings(Collection $categories): array
    {
        return $categories->flatMap(function ($category) {
            $mapping = [
                [
                    'id' => $category->id,
                    'attribute_ids' => $category->attributes->pluck('id')->toArray(),
                ],
            ];

            if ($category->children->isNotEmpty()) {
                return array_merge($mapping, $category->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'attribute_ids' => $child->attributes->pluck('id')->toArray(),
                    ];
                })->toArray());
            }

            return $mapping;
        })->toArray();
    }

    public function makeJsPayload(Collection $categories, Collection $attributes, int $initialVariantIndex = 0): array
    {
        return [
            'attributes' => $this->toJsAttributes($attributes),
            'categoryAttributeMappings' => $this->categoryAttributeMappings($categories),
            'initialVariantIndex' => $initialVariantIndex,
        ];
    }
}
