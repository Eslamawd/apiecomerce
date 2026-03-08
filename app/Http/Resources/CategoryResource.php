<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'name_en'        => $this->name_en,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'description_en' => $this->description_en,
            'image'          => $this->image,
            'video'          => $this->video,
            'parent_id'      => $this->parent_id,
            'is_active'      => $this->is_active,
            'sort_order'     => $this->sort_order,
            'children'       => CategoryResource::collection($this->whenLoaded('children')),
            'products_count' => $this->whenCounted('products'),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
