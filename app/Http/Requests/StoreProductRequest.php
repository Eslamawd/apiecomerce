<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'name_en'         => 'required|string|max:255',
            'description'     => 'required|string',
            'description_en'  => 'required|string',
            'price'           => 'required|numeric|min:0',
            'old_price'       => 'nullable|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'sku'             => 'nullable|string|unique:products,sku',
            'quantity'        => 'required|integer|min:0',
            'category_id'     => 'required|exists:categories,id',
            'is_active'       => 'nullable|boolean',
            'is_featured'     => 'nullable|boolean',
            'images'          => 'nullable|array|max:10',
            'images.*'        => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'videos'          => 'nullable|array|max:5',
            'videos.*'        => 'file|mimes:mp4,mov,avi|max:51200',
        ];
    }
}
