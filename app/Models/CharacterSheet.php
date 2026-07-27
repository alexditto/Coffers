<?php

namespace App\Models;

use Database\Factories\CharacterSheetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CharacterSheet extends Model
{
    /** @use HasFactory<CharacterSheetFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'health' => 'integer',
            'total_health' => 'integer',
            'ac' => 'integer',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function statuses(): BelongsToMany
    {
        return $this->belongsToMany(CharacterStatus::class);
    }
}
