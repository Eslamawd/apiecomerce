<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVideo extends Model
{
    protected $fillable = [
        'product_id',
        'video',
        'title',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function getVideoAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Str::startsWith($value, ['http://', 'https://']) ? $value : asset('storage/' . $value);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
