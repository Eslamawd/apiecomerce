<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function getImageAttribute(?string $value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
