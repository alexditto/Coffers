<?php

namespace App\Models;

use Database\Factories\CharacterStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CharacterStatus extends Model
{
    /** @use HasFactory<CharacterStatusFactory> */
    use HasFactory;

    public function character_sheet(): BelongsToMany
    {
        return $this->belongsToMany(CharacterSheet::class);
    }
}
