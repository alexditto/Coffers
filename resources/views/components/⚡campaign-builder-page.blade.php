<?php

use Livewire\Component;

new class extends Component {
    public function mount(): void
    {
        $campaignId = request()->integer('campaign');

        if (!$campaignId) {
            return;
        }

        $user = auth()->user();

        $hasAccess = $user->campaigns->merge($user->owned_campaigns)->contains('id', $campaignId);

        if ($hasAccess) {
            session(['selected_campaign_id' => $campaignId]);
        }
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="hidden lg:block">
        <livewire:campaign-selector-banner/>
    </div>

    <livewire:campaign-details/>

    <livewire:campaign-characters/>
</div>
