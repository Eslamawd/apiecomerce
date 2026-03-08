<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'name'            => 'nullable|string|max:255',
            'name_en'         => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'description_en'  => 'nullable|string',
            'price'           => 'nullable|numeric|min:0',
            'old_price'       => 'nullable|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'sku'             => ['nullable', 'string', Rule::unique('products', 'sku')->ignore($productId)],
            'quantity'        => 'nullable|integer|min:0',
            'category_id'     => 'nullable|exists:categories,id',
            'is_active'       => 'nullable|boolean',
            'is_featured'     => 'nullable|boolean',
            'images'          => 'nullable|array|max:10',
            'images.*'        => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'videos'          => 'nullable|array|max:5',
            'videos.*'        => 'file|mimes:mp4,mov,avi|max:51200',
        ];
    }
}
