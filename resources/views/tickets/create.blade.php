<x-layouts::app.sidebar>
    <flux:main>
        <div class="max-w-5xl mx-auto px-4 py-8">
            <div class="mb-6">
                <flux:heading size="xl">Create Support Ticket</flux:heading>
                <flux:text class="mt-1">Describe your issue and we'll get back to you as soon as possible.</flux:text>
            </div>

            <livewire:tickets.create-ticket />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
