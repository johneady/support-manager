<x-layouts.guest :title="'FAQ'">
    {{-- Hero Section --}}
    <section class="relative py-12 sm:py-16 overflow-hidden">
        {{-- Decorative background elements --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-linear-to-br from-blue-300/30 to-sky-300/30 dark:from-blue-600/20 dark:to-sky-600/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-linear-to-br from-cyan-300/30 to-teal-300/30 dark:from-cyan-600/20 dark:to-teal-600/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-linear-to-r from-blue-100 to-sky-100 dark:from-blue-900/50 dark:to-sky-900/50 text-blue-700 dark:text-blue-300 text-sm font-medium mb-6">
                <flux:icon name="light-bulb" class="size-4" />
                Knowledge Base
            </div>
            <flux:heading size="xl" level="1" class="text-3xl sm:text-4xl bg-linear-to-r! from-blue-600! via-sky-600! to-cyan-600! dark:from-blue-400! dark:via-sky-400! dark:to-cyan-400! bg-clip-text! text-transparent!">
                Frequently Asked Questions
            </flux:heading>
            <flux:text class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                Find answers to common questions. Can't find what you're looking for? Submit a support ticket.
            </flux:text>
        </div>
    </section>

    {{-- FAQs Section --}}
    <section class="pb-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <livewire:faq-list />

            <div class="mt-12 text-center">
                <div class="inline-flex flex-col sm:flex-row items-center gap-4 p-6 rounded-2xl bg-white/60 dark:bg-zinc-800/60 backdrop-blur-sm border border-blue-100 dark:border-zinc-700">
                    <div class="w-12 h-12 rounded-xl bg-linear-to-br from-blue-500 to-sky-500 flex items-center justify-center">
                        <flux:icon name="chat-bubble-left-right" class="size-6 text-white" />
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="font-medium text-zinc-900 dark:text-white">Still need help?</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Our support team is ready to assist you.</p>
                    </div>
                    @auth
                        <flux:button href="{{ route('tickets.create') }}" variant="primary" class="bg-linear-to-r! from-blue-500! to-sky-500! hover:from-blue-600! hover:to-sky-600! border-0!">
                            Create a Ticket
                        </flux:button>
                    @else
                        <flux:button href="{{ route('login') }}" variant="primary" class="bg-linear-to-r! from-blue-500! to-sky-500! hover:from-blue-600! hover:to-sky-600! border-0!">
                            Sign in to Submit
                        </flux:button>
                    @endauth
                </div>
            </div>
        </div>
    </section>
</x-layouts.guest>
