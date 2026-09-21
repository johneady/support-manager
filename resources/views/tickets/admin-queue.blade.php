<x-layouts::app :title="__('Ticket Queue')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:tickets.admin-queue :ticket="request()->integer('ticket') ?: null" />
    </div>
</x-layouts::app>
