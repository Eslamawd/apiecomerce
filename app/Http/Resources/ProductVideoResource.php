<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'video'      => $this->video,
            'title'      => $this->title,
            'sort_order' => $this->sort_order,
        ];
    }
}
