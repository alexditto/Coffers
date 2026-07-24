<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCount extends Model
{
    /** @use HasFactory<\Database\Factories\ItemCountFactory> */
    use HasFactory;

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
