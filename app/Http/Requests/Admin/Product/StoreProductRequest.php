<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Adjust authorization logic as needed (e.g., admin check)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'stock' => 'required|integer|min:0',
            'images.*' => 'nullable|image|max:2048',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'battery_capacity' => 'nullable|string|max:255',
            'screen_size' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'warranty_period' => 'nullable|string|max:255',
            'specifications' => 'nullable|array',
        ];
    }
}
?>
