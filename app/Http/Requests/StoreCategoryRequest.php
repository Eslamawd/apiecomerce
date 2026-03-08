<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'name_en'        => 'required|string|max:255',
            'description'    => 'nullable|string',
            'description_en' => 'nullable|string',
            'image'          => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'video'          => 'nullable|file|mimes:mp4,mov,avi|max:51200',
            'parent_id'      => 'nullable|exists:categories,id',
            'is_active'      => 'nullable|boolean',
            'sort_order'     => 'nullable|integer',
        ];
    }
}
