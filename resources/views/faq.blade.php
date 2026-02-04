<x-layouts::auth.simple>
    <div class="w-full max-w-2xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Frequently Asked Questions</h1>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Find answers to common questions. Can't find what you're looking for?
                <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Sign in</a>
                to submit a support ticket.
            </p>
        </div>

        <livewire:faq-list />

        <div class="mt-8 text-center">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Still need help?
                @auth
                    <a href="{{ route('tickets.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Create a support ticket</a>
                @else
                    <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Sign in</a> to create a support ticket.
                @endauth
            </p>
        </div>
    </div>
</x-layouts::auth.simple>
