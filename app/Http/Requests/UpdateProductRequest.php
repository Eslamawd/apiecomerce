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
            'product_type'    => 'nullable|string|in:general,clothing,automotive,food,electronics,other',
            'description'     => 'nullable|string',
            'description_en'  => 'nullable|string',
            'specifications'  => 'nullable|array',
            'price'           => 'nullable|numeric|min:0',
            'old_price'       => 'nullable|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'sku'             => ['nullable', 'string', Rule::unique('products', 'sku')->ignore($productId)],
            'quantity'        => 'nullable|integer|min:0',
            'variants'        => 'nullable|array',
            'variants.*.sku'  => 'nullable|string|max:255',
            'variants.*.price' => 'required_with:variants|numeric|min:0',
            'variants.*.quantity' => 'required_with:variants|integer|min:0',
            'variants.*.attributes' => 'nullable|array',
            'category_id'     => 'nullable|exists:categories,id',
            'is_active'       => 'nullable|boolean',
            'is_featured'     => 'nullable|boolean',
            'images'          => 'nullable|array|max:10',
            'images.*'        => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'videos'          => 'nullable|array|max:5',
            'videos.*'        => 'file|mimes:mp4,mov,avi|max:51200',
        ];
    }

    protected function prepareForValidation(): void
    {
        $specifications = $this->input('specifications');
        $variants = $this->input('variants');

        $merge = [];

        if (is_string($specifications) && $specifications !== '') {
            $decoded = json_decode($specifications, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $merge['specifications'] = $decoded;
            }
        }

        if (is_string($variants) && $variants !== '') {
            $decoded = json_decode($variants, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $merge['variants'] = $decoded;
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
