<?php

namespace App\Models;

use App\Observers\CharacterObserver;
use App\Observers\ImageObserver;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(ImageObserver::class)]
#[ObservedBy(CharacterObserver::class)]
class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    public function character_sheet(): HasOne
    {
        return $this->hasOne(CharacterSheet::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
