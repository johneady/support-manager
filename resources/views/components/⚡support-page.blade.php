<?php

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /**
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()->published()->ordered()->get();
    }
};
?>

<div>
    {{-- Hero Section --}}
    <section class="relative overflow-hidden py-8 sm:py-10">
        {{-- Decorative background elements --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-linear-to-br from-blue-300/30 to-sky-300/30 blur-3xl dark:from-blue-600/20 dark:to-sky-600/20"></div>
            <div class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-linear-to-br from-cyan-300/30 to-teal-300/30 blur-3xl dark:from-cyan-600/20 dark:to-teal-600/20"></div>
        </div>

        <div class="relative mx-auto max-w-5xl px-4 text-center sm:px-6 lg:px-8">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-linear-to-r from-blue-100 to-sky-100 px-4 py-2 text-sm font-medium text-blue-700 dark:from-blue-900/50 dark:to-sky-900/50 dark:text-blue-300">
                <flux:icon name="sparkles" class="size-4" />
                We're here to help!
            </div>
            <flux:heading
                size="xl"
                level="1"
                class="bg-linear-to-r! from-blue-600! via-sky-600! to-cyan-600! bg-clip-text! text-3xl text-transparent! sm:text-4xl lg:text-5xl dark:from-blue-400! dark:via-sky-400! dark:to-cyan-400!"
            >
                How can we help you?
            </flux:heading>
            <flux:text class="mx-auto mt-4 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                Browse our frequently asked questions below or get in touch with our support team.
            </flux:text>
            @auth
                <div class="mt-8">
                    <flux:button
                        href="{{ url('/dashboard') }}"
                        variant="primary"
                        icon="ticket"
                        class="border-0! bg-linear-to-r! from-blue-500! to-sky-500! shadow-lg! shadow-blue-500/25! hover:from-blue-600! hover:to-sky-600!"
                    >
                        Submit a Ticket
                    </flux:button>
                </div>
            @else
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
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
    </section>

    {{-- FAQs Section --}}
    <section class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 text-center">
                <flux:heading
                    size="lg"
                    level="2"
                    class="bg-linear-to-r from-blue-600 to-sky-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-sky-400"
                >Frequently Asked Questions</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Find answers to common questions about our services.
                </flux:text>
            </div>

            @if ($this->faqs->count() > 0)
                <div class="space-y-4">
                    @foreach ($this->faqs as $faq)
                        <a
                            href="{{ route('faq.show', $faq) }}"
                            class="group block overflow-hidden rounded-xl border border-blue-100 bg-white/80 px-6 py-4 shadow-sm backdrop-blur-sm transition-all duration-300 hover:shadow-md hover:shadow-blue-200/50 dark:border-zinc-700 dark:bg-zinc-800/80 dark:hover:shadow-blue-900/20"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="font-medium text-zinc-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                        {{ $faq->question }}
                                    </h3>
                                    <p class="mt-2 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $faq->summary() }}
                                    </p>
                                    <span class="mt-3 inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
                                        <flux:icon name="clock" class="size-3.5" />
                                        {{ $faq->readingTime() }} min read
                                    </span>
                                </div>
                                <flux:icon
                                    name="chevron-right"
                                    class="mt-0.5 size-5 shrink-0 text-blue-400 transition-transform group-hover:translate-x-0.5"
                                />
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <flux:card class="border-blue-100! bg-white/80! py-12 text-center backdrop-blur-sm dark:border-zinc-700! dark:bg-zinc-800/80!">
                    <flux:icon name="question-mark-circle" class="mx-auto size-12 text-blue-300 dark:text-blue-600" />
                    <flux:heading size="lg" class="mt-4 text-blue-600 dark:text-blue-400">No FAQs yet</flux:heading>
                    <flux:text class="mt-2 text-zinc-500"> Check back later for frequently asked questions. </flux:text>
                </flux:card>
            @endif
        </div>
    </section>
</div>
