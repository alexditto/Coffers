<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_session_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'campaign_shop');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campaign_user');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function scenes(): HasMany
    {
        return $this->hasMany(Scene::class);
    }

    public function userIsDm()
    {
        return $this->owner_id === auth()->user()->id;
    }

    public function userIsPlayer()
    {
        return $this->characters()->where('user_id', auth()->user()->id)->exists();
    }
}
