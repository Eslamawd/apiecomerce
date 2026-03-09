<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'name_en'             => $this->name_en,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'description_en'      => $this->description_en,
            'price'               => $this->price,
            'old_price'           => $this->old_price,
            'cost_price'          => $this->cost_price,
            'discount_percentage' => $this->discount_percentage,
            'sku'                 => $this->sku,
            'quantity'            => $this->quantity,
            'is_active'           => $this->is_active,
            'is_featured'         => $this->is_featured,
            'average_rating'      => $this->average_rating,
            'reviews_count'       => $this->reviews_count,
            'category'            => new CategoryResource($this->whenLoaded('category')),
            'vendor'              => [
                'id'   => $this->whenLoaded('vendor', fn() => $this->vendor->id),
                'name' => $this->whenLoaded('vendor', fn() => $this->vendor->name),
            ],
            'images'              => ProductImageResource::collection($this->whenLoaded('images')),
            'videos'              => ProductVideoResource::collection($this->whenLoaded('videos')),
            'primary_image'       => new ProductImageResource($this->whenLoaded('primaryImage')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
