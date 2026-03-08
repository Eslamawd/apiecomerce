<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_name'      => 'required|string|max:255',
            'shipping_phone'     => 'required|string|max:50',
            'shipping_address'   => 'required|string',
            'shipping_city'      => 'required|string|max:255',
            'shipping_email'     => 'nullable|email|max:255',
            'shipping_latitude'  => 'nullable|numeric',
            'shipping_longitude' => 'nullable|numeric',
            'payment_method'     => 'required|in:cash_on_delivery,online',
            'coupon_code'        => 'nullable|string|exists:coupons,code',
            'notes'              => 'nullable|string',
        ];
    }
}
