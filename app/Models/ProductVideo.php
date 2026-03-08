<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $value ? asset('storage/' . $value) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
