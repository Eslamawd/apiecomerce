<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = ['user_id'];

    protected $appends = ['total', 'items_count'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute(): float
    {
        if (! $this->relationLoaded('items')) {
            return 0;
        }

        return $this->items->sum(fn($item) => $item->relationLoaded('product') && $item->product
            ? $item->product->price * $item->quantity
            : 0
        );
    }

    public function getItemsCountAttribute(): int
    {
        if (! $this->relationLoaded('items')) {
            return 0;
        }

        return $this->items->count();
    }
}
