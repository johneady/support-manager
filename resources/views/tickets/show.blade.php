<x-layouts::app.sidebar>
    <flux:main>
        <div class="mx-auto max-w-4xl px-4 py-8">
            <livewire:tickets.show-ticket :ticket="\App\Models\Ticket::findOrFail(request()->route('ticket'))" />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
