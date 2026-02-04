<x-layouts::app.sidebar>
    <flux:main>
        <div class="px-4 py-4">
            {{-- Welcome Header --}}
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-violet-400/85 via-pink-400/85 to-amber-400/85 p-6 text-white shadow-md shadow-violet-500/15 mb-6">
                <div class="absolute inset-0 bg-linear-to-r from-violet-500/15 to-transparent"></div>
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative">
                    <h1 class="text-2xl font-bold text-white">My Support Tickets 🎫</h1>
                    <p class="mt-1 text-white">View and manage your support requests.</p>
                </div>
            </div>

            <livewire:tickets.ticket-list />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
