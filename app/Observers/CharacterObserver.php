<?php

namespace App\Observers;

use App\Models\Campaign;
use App\Models\Character;

class CharacterObserver
{
    /**
     * Handle the Character "created" event.
     */
    public function created(Character $character): void
    {
        //
    }

    /**
     * Handle the Character "updated" event.
     */
    public function updated(Character $character): void
    {
        if($character->isDirty("campaign_id")){
            if($character->campaign_id){
                $user = $character->user()->first();
                $user->campaigns()->attach($character->campaign()->first());
            } else {
                $user = $character->user()->first();
                $campaign = Campaign::find($character->getOriginal("campaign_id"));
                $user->campaigns()->detach($campaign);
            }
        }
    }

    /**
     * Handle the Character "deleted" event.
     */
    public function deleted(Character $character): void
    {
        //
    }

    /**
     * Handle the Character "restored" event.
     */
    public function restored(Character $character): void
    {
        //
    }

    /**
     * Handle the Character "force deleted" event.
     */
    public function forceDeleted(Character $character): void
    {
        //
    }
}
