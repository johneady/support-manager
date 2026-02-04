<x-layouts::app.sidebar>
    <flux:main>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <livewire:tickets.show-ticket :ticket="\App\Models\Ticket::findOrFail(request()->route('ticket'))" />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
