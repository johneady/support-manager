<x-layouts.guest :title="'FAQ'">
    {{-- Hero Section --}}
    <section class="relative overflow-hidden py-8 sm:py-10">
        {{-- Decorative background elements --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-linear-to-br from-blue-300/30 to-sky-300/30 blur-3xl dark:from-blue-600/20 dark:to-sky-600/20"></div>
            <div class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-linear-to-br from-cyan-300/30 to-teal-300/30 blur-3xl dark:from-cyan-600/20 dark:to-teal-600/20"></div>
        </div>

        <div class="relative mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-blue-100 to-sky-100 px-4 py-2 text-sm font-medium text-blue-700 dark:from-blue-900/50 dark:to-sky-900/50 dark:text-blue-300">
                <flux:icon name="light-bulb" class="size-4" />
                Knowledge Base
            </div>
            <flux:heading
                size="xl"
                level="1"
                class="bg-linear-to-r! from-blue-600! via-sky-600! to-cyan-600! bg-clip-text! text-3xl text-transparent! sm:text-4xl dark:from-blue-400! dark:via-sky-400! dark:to-cyan-400!"
            >
                Frequently Asked Questions
            </flux:heading>
            <flux:text class="mx-auto mt-4 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                Find answers to common questions. Can't find what you're looking for? Submit a support ticket.
            </flux:text>
        </div>
    </section>

    {{-- FAQs Section --}}
    <section class="pb-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <livewire:faq-list />

            <div class="mt-12 text-center">
                <div class="inline-flex flex-col items-center gap-4 rounded-2xl border border-blue-100 bg-white/60 p-6 backdrop-blur-sm sm:flex-row dark:border-zinc-700 dark:bg-zinc-800/60">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br from-blue-500 to-sky-500">
                        <flux:icon name="chat-bubble-left-right" class="size-6 text-white" />
                    </div>
                    <div class="text-center sm:text-left">
                        <p class="font-medium text-zinc-900 dark:text-white">Still need help?</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Our support team is ready to assist you.</p>
                    </div>
                    @auth
                        <flux:button
                            href="{{ url('/dashboard') }}"
                            variant="primary"
                            icon="ticket"
                            class="border-0! bg-linear-to-r! from-blue-500! to-sky-500! shadow-lg! shadow-blue-500/25! hover:from-blue-600! hover:to-sky-600!"
                        >
                            Submit a Ticket
                        </flux:button>
                    @else
                        <div class="flex flex-col items-center gap-4 sm:flex-row">
                            <flux:button
                                href="{{ route('register') }}"
                                variant="primary"
                                icon="ticket"
                                class="border-0! bg-linear-to-r! from-blue-500! to-sky-500! shadow-lg! shadow-blue-500/25! hover:from-blue-600! hover:to-sky-600!"
                            >
                                Submit a Ticket
                            </flux:button>
                            <flux:text size="sm" class="text-zinc-500">
                                Already have an account?
                                <flux:link href="{{ route('login') }}" class="text-blue-600! dark:text-blue-400!"
                                    >Log in</flux:link>
                            </flux:text>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </section>
</x-layouts.guest>
