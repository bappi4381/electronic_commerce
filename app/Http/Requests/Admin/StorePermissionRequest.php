<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                'unique:permissions,name,NULL,id,guard_name,admin',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The permission name is required.',
            'name.unique'   => 'This permission already exists under the admin guard.',
            'name.regex'    => 'Permission name may only contain lowercase letters, numbers, and hyphens (e.g. manage-products).',
        ];
    }
}
