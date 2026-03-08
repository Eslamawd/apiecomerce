<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => method_exists($this->resource, 'getRoleNames') ? $this->getRoleNames()->first() : null,
            'created_at' => $this->created_at,
        ];
    }
}
