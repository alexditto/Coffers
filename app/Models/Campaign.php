<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'campaign_shop');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class);
    }
}
