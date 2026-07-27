<?php

use Illuminate\Support\Collection;
use Livewire\Component;

new class extends Component {
    public Collection $friends;

    public Collection $approvedFriends;

    public Collection $pendingFriends;

    public Collection $pendingRequests;

    public function mount(): void
    {
        $friendRequests = auth()->user()->friend_requests;

        $this->friends = auth()->user()->friends;
        $this->approvedFriends = $this->friends->where('status', 'approved')
            ->merge($friendRequests->where('status', 'approved'));
        $this->pendingFriends = $this->friends->where('status', 'pending');
        $this->pendingRequests = $friendRequests->where('status', 'pending');
    }
};
?>

<a href="{{ route('friends') }}" wire:navigate class="flex h-full flex-col rounded-2xl border border-line bg-surface p-4 shadow-sm transition hover:border-brand-300 hover:shadow-md dark:bg-gray-800">
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Friends</span>
        <flux:icon.chevron-right class="size-4 text-content-faint" />
    </div>

    <div class="mt-3 grid flex-1 grid-cols-3 gap-2">
        <div class="flex flex-col items-center justify-center rounded-xl border border-line py-2.5 text-center">
            <div class="text-xl font-bold text-content dark:text-white">{{ $approvedFriends->count() }}</div>
            <div class="mt-0.5 text-[10px] font-bold tracking-wide text-content-faint uppercase">Approved</div>
        </div>

        <div class="flex flex-col items-center justify-center rounded-xl border border-line py-2.5 text-center">
            <div class="text-xl font-bold text-content dark:text-white">{{ $pendingFriends->count() }}</div>
            <div class="mt-0.5 text-[10px] font-bold tracking-wide text-content-faint uppercase">Awaiting</div>
        </div>

        <div @class([
            'flex flex-col items-center justify-center rounded-xl border py-2.5 text-center',
            'border-brand-300 bg-brand-50' => $pendingRequests->isNotEmpty(),
            'border-line' => $pendingRequests->isEmpty(),
        ])>
            <div @class([
                'text-xl font-bold dark:text-white',
                'text-brand-700' => $pendingRequests->isNotEmpty(),
                'text-content' => $pendingRequests->isEmpty(),
            ])>{{ $pendingRequests->count() }}</div>
            <div @class([
                'mt-0.5 text-[10px] font-bold tracking-wide uppercase',
                'text-brand-600' => $pendingRequests->isNotEmpty(),
                'text-content-faint' => $pendingRequests->isEmpty(),
            ])>Requests</div>
        </div>
    </div>
</a>
