<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'status'          => $this->status,
            'subtotal'        => $this->subtotal,
            'discount'        => $this->discount,
            'shipping_cost'   => $this->shipping_cost,
            'total'           => $this->total,
            'payment_method'  => $this->payment_method,
            'payment_status'  => $this->payment_status,
            'notes'           => $this->notes,
            'shipping_name'   => $this->shipping_name,
            'shipping_phone'  => $this->shipping_phone,
            'shipping_email'  => $this->shipping_email,
            'shipping_address'=> $this->shipping_address,
            'shipping_city'   => $this->shipping_city,
            'shipping_latitude' => $this->shipping_latitude,
            'shipping_longitude'=> $this->shipping_longitude,
            'coupon'          => new CouponResource($this->whenLoaded('coupon')),
            'items'           => OrderItemResource::collection($this->whenLoaded('items')),
            'user'            => [
                'id'   => $this->whenLoaded('user', fn() => $this->user->id),
                'name' => $this->whenLoaded('user', fn() => $this->user->name),
            ],
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
