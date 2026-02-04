<x-layouts::app.sidebar>
    <flux:main>
        <div class="max-w-5xl mx-auto px-4 py-8">
            <div class="mb-6">
                <flux:heading size="xl">My Support Tickets</flux:heading>
                <flux:text class="mt-1">View and manage your support requests.</flux:text>
            </div>

            <livewire:tickets.ticket-list />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
