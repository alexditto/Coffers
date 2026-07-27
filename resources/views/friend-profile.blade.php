<x-layouts::app :title="__('Friend')">
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-2">
        <livewire:friend-profile-page :friend-id="(int) $friend" />
    </div>
</x-layouts::app>
