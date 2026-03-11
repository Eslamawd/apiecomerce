<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:30',
            'products_limit' => 'nullable|integer|min:1|max:50',
            'categories_limit' => 'nullable|integer|min:1|max:50',
        ];
    }

    protected function prepareForValidation(): void
    {
        $query = $this->input('q');

        if (is_string($query)) {
            $this->merge([
                'q' => trim($query),
            ]);
        }
    }
}
