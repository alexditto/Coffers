<?php

use App\Models\Campaign;
use App\Models\Shop;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

$userIsOnCampaign = function ($user, ?Campaign $campaign): bool {
    if (! $campaign) {
        return false;
    }

    return $campaign->owner_id === $user->id || $campaign->players()->where('users.id', $user->id)->exists();
};

Broadcast::channel('campaign.{campaignId}.shops', function ($user, $campaignId) use ($userIsOnCampaign) {
    return $userIsOnCampaign($user, Campaign::find($campaignId));
});

Broadcast::channel('shop.{shopId}', function ($user, $shopId) use ($userIsOnCampaign) {
    $shop = Shop::with('campaigns')->find($shopId);

    if (! $shop) {
        return false;
    }

    return $shop->campaigns->contains(fn (Campaign $campaign) => $userIsOnCampaign($user, $campaign));
});
