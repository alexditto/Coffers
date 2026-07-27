<?php

use App\Models\Campaign;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function upcomingCampaign(): ?Campaign
    {
        $user = auth()->user();

        return $user->campaigns
            ->merge($user->owned_campaigns)
            ->unique('id')
            ->whereNotNull('next_session_date')
            ->filter(fn (Campaign $campaign) => ! $campaign->next_session_date->isBefore(today()))
            ->sortBy('next_session_date')
            ->first();
    }
};
?>

<div class="flex h-full flex-col rounded-2xl border border-line bg-surface p-4 shadow-sm dark:bg-gray-800">
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Upcoming Campaign</span>
        @if ($this->upcomingCampaign)
            <flux:icon.chevron-right class="size-4 text-content-faint" />
        @endif
    </div>

    @if ($this->upcomingCampaign)
        @php
            $date = $this->upcomingCampaign->next_session_date;
            $relative = match (true) {
                $date->isToday() => 'Today',
                $date->isTomorrow() => 'Tomorrow',
                default => $date->diffForHumans(['parts' => 1]),
            };
        @endphp

        <a href="{{ route('campaign-builder', ['campaign' => $this->upcomingCampaign->id]) }}" wire:navigate class="mt-3 flex flex-1 items-center gap-3 rounded-xl transition hover:opacity-80">
            <flux:avatar size="lg" name="{{ $this->upcomingCampaign->name }}" color="auto" />

            <div class="min-w-0 flex-1">
                <div class="truncate text-lg font-bold text-content dark:text-white">{{ $this->upcomingCampaign->name }}</div>
                <div class="mt-0.5 text-sm text-content-muted dark:text-gray-400">{{ $date->format('D, M j') }} · {{ $relative }}</div>
            </div>
        </a>
    @else
        <div class="mt-3 flex flex-1 items-center justify-center rounded-xl border-2 border-dashed border-line p-4 text-center text-sm text-content-muted dark:text-gray-400">
            No sessions on the calendar
        </div>
    @endif
</div>
