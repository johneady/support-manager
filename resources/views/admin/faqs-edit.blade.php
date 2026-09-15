<x-layouts::app.sidebar>
    <flux:main>
        <div class="mx-auto max-w-5xl px-4 py-8">
            <livewire:admin.faq-form :faq-id="request()->route('faq')" />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
