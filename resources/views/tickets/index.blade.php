<x-layouts::app :title="__('My Tickets')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:tickets.ticket-list :create="request()->get('create')" />
    </div>
</x-layouts::app>
