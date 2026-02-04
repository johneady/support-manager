<x-layouts::app.sidebar>
    <flux:main>
        <div class="max-w-5xl mx-auto px-4 py-8">
            <div class="mb-6">
                <flux:heading size="xl">Ticket Queue</flux:heading>
                <flux:text class="mt-1">Manage and respond to open support tickets.</flux:text>
            </div>

            <livewire:tickets.admin-queue />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
