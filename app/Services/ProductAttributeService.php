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
        return Category::nested(null, ['attributes']);
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
            return collect([[
                'id' => $category->id,
                'attribute_ids' => $category->attributes->pluck('id')->all(),
            ]])->concat($this->categoryAttributeMappings($category->children));
        })->all();
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
