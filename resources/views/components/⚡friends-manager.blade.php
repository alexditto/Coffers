<?php

use App\Models\Friend;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $friendEmail = '';

    public ?int $removingFriendId = null;

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function friends(): Collection
    {
        $user = auth()->user();

        $initiated = $user->friends()->with('friend')->where('status', 'approved')->get()
            ->map(fn (Friend $row) => $row->friend);

        $received = $user->friend_requests()->with('user')->where('status', 'approved')->get()
            ->map(fn (Friend $row) => $row->user);

        return $initiated->merge($received)->unique('id')->sortBy('name')->values();
    }

    /**
     * @return Collection<int, Friend>
     */
    #[Computed]
    public function pendingRequests(): Collection
    {
        return auth()->user()->friend_requests()->with('user')->where('status', 'pending')->latest()->get();
    }

    /**
     * @return Collection<int, Friend>
     */
    #[Computed]
    public function sentRequests(): Collection
    {
        return auth()->user()->friends()->with('friend')->where('status', 'pending')->latest()->get();
    }

    #[Computed]
    public function removingFriend(): ?User
    {
        if (! $this->removingFriendId) {
            return null;
        }

        return $this->friends->firstWhere('id', $this->removingFriendId);
    }

    public function sendRequest(): void
    {
        $data = $this->validate([
            'friendEmail' => ['required', 'email'],
        ]);

        $target = User::where('email', $data['friendEmail'])->first();

        if (! $target) {
            $this->addError('friendEmail', 'No user was found with that email address.');

            return;
        }

        if ($target->id === auth()->id()) {
            $this->addError('friendEmail', 'You cannot send a friend request to yourself.');

            return;
        }

        $alreadyConnected = Friend::query()
            ->where(fn ($query) => $query->where('user_id', auth()->id())->where('friend_id', $target->id))
            ->orWhere(fn ($query) => $query->where('user_id', $target->id)->where('friend_id', auth()->id()))
            ->exists();

        if ($alreadyConnected) {
            $this->addError('friendEmail', 'You already have a pending or existing friendship with this user.');

            return;
        }

        Friend::create([
            'user_id' => auth()->id(),
            'friend_id' => $target->id,
            'status' => 'pending',
        ]);

        $this->reset('friendEmail');

        unset($this->sentRequests);

        Flux::toast('Friend request sent to '.$target->name.'.', variant: 'success');
    }

    public function approve(int $friendId): void
    {
        $request = auth()->user()->friend_requests()
            ->where('id', $friendId)
            ->where('status', 'pending')
            ->first();

        if (! $request) {
            return;
        }

        $request->update(['status' => 'approved']);

        unset($this->friends, $this->pendingRequests);

        Flux::toast('You are now friends with '.$request->user->name.'.', variant: 'success');
    }

    public function reject(int $friendId): void
    {
        auth()->user()->friend_requests()
            ->where('id', $friendId)
            ->where('status', 'pending')
            ->delete();

        unset($this->pendingRequests);
    }

    public function cancelRequest(int $friendId): void
    {
        auth()->user()->friends()
            ->where('id', $friendId)
            ->where('status', 'pending')
            ->delete();

        unset($this->sentRequests);
    }

    public function confirmRemoveFriend(int $friendUserId): void
    {
        if (! $this->friends->contains('id', $friendUserId)) {
            return;
        }

        $this->removingFriendId = $friendUserId;

        Flux::modal('confirm-remove-friend')->show();
    }

    public function removeFriend(): void
    {
        $friend = $this->removingFriend;

        if (! $friend) {
            return;
        }

        Friend::query()
            ->where(fn ($query) => $query->where('user_id', auth()->id())->where('friend_id', $friend->id))
            ->orWhere(fn ($query) => $query->where('user_id', $friend->id)->where('friend_id', auth()->id()))
            ->where('status', 'approved')
            ->delete();

        $this->removingFriendId = null;

        unset($this->friends);

        Flux::modal('confirm-remove-friend')->close();

        Flux::toast($friend->name.' removed from your friends.', variant: 'success');
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-8">
    <div>
        <flux:heading size="xl">Friends</flux:heading>
        <flux:text class="mt-1">Manage the people you adventure with.</flux:text>
    </div>

    <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
        <div class="text-[10px] font-bold tracking-widest text-content-faint uppercase">Add a friend</div>

        <form wire:submit="sendRequest" class="mt-3 flex items-start gap-2">
            <div class="flex-1">
                <flux:input wire:model="friendEmail" type="email" placeholder="friend@example.com" />
                <flux:error name="friendEmail" />
            </div>

            <flux:button type="submit" variant="primary">Send request</flux:button>
        </form>
    </div>

    @if ($this->pendingRequests->isNotEmpty())
        <div>
            <div class="mb-3 text-[10px] font-bold tracking-widest text-content-faint uppercase">Requests · {{ $this->pendingRequests->count() }}</div>

            <div class="flex flex-col gap-2">
                @foreach ($this->pendingRequests as $request)
                    <div wire:key="request-{{ $request->id }}" class="flex items-center gap-3 rounded-2xl border border-brand-300 bg-brand-50 p-3">
                        <flux:avatar size="sm" name="{{ $request->user->name }}" color="auto" />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $request->user->name }}</div>
                            <div class="truncate text-xs text-content-muted">{{ $request->user->email }}</div>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="reject({{ $request->id }})">Decline</flux:button>
                            <flux:button size="sm" variant="primary" wire:click="approve({{ $request->id }})">Accept</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->sentRequests->isNotEmpty())
        <div>
            <div class="mb-3 text-[10px] font-bold tracking-widest text-content-faint uppercase">Sent · {{ $this->sentRequests->count() }}</div>

            <div class="flex flex-col gap-2">
                @foreach ($this->sentRequests as $sent)
                    <div wire:key="sent-{{ $sent->id }}" class="flex items-center gap-3 rounded-2xl border border-line p-3">
                        <flux:avatar size="sm" name="{{ $sent->friend->name }}" color="auto" />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $sent->friend->name }}</div>
                            <div class="truncate text-xs text-content-muted">{{ $sent->friend->email }}</div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <flux:badge size="sm">Pending</flux:badge>
                            <flux:button size="sm" variant="ghost" wire:click="cancelRequest({{ $sent->id }})">Cancel</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <div class="mb-3 text-[10px] font-bold tracking-widest text-content-faint uppercase">Your Friends · {{ $this->friends->count() }}</div>

        @if ($this->friends->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-line p-6 text-center text-sm text-content-muted">
                No friends yet — send a request above to get started.
            </div>
        @else
            <div class="flex flex-col gap-2">
                @foreach ($this->friends as $friend)
                    <div wire:key="friend-{{ $friend->id }}" class="flex items-center gap-3 rounded-2xl border border-line bg-surface p-3">
                        <flux:avatar size="sm" name="{{ $friend->name }}" color="auto" />

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-bold text-content">{{ $friend->name }}</div>
                            <div class="truncate text-xs text-content-muted">{{ $friend->email }}</div>
                        </div>

                        <flux:button size="sm" variant="ghost" wire:click="confirmRemoveFriend({{ $friend->id }})">Remove</flux:button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <flux:modal name="confirm-remove-friend" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove friend?</flux:heading>
                <flux:text class="mt-2">
                    This will remove {{ $this->removingFriend?->name }} from your friends list. You can send a new request later if you change your mind.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />

                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="removeFriend">Remove</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
