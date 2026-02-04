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

<div class="min-h-screen bg-linear-to-br from-blue-50 via-sky-50 to-cyan-50 dark:from-zinc-900 dark:via-blue-950/30 dark:to-zinc-900">
    {{-- Header --}}
    <header class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border-b border-blue-100 dark:border-zinc-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-2 group">
                    <x-app-logo-icon class="size-8 transition-transform group-hover:scale-110" />
                    <span class="font-semibold bg-linear-to-r from-blue-600 to-sky-600 dark:from-blue-400 dark:to-sky-400 bg-clip-text text-transparent">Support Manager</span>
                </a>
                <div class="flex items-center gap-4">
                    <flux:button href="{{ route('faq') }}" variant="ghost" size="sm">FAQ</flux:button>
                    @auth
                        <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm">Dashboard</flux:button>
                    @else
                        @if (Route::has('login'))
                            <flux:button href="{{ route('login') }}" variant="ghost" size="sm">Log in</flux:button>
                        @endif
                        @if (Route::has('register'))
                            <flux:button href="{{ route('register') }}" variant="primary" size="sm" class="bg-linear-to-r! from-blue-500! to-sky-500! hover:from-blue-600! hover:to-sky-600! border-0!">Register</flux:button>
                        @endif
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    {{-- Hero Section --}}
    <section class="relative py-16 sm:py-24 overflow-hidden">
        {{-- Decorative background elements --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-linear-to-br from-blue-300/30 to-sky-300/30 dark:from-blue-600/20 dark:to-sky-600/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-linear-to-br from-cyan-300/30 to-teal-300/30 dark:from-cyan-600/20 dark:to-teal-600/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-linear-to-r from-blue-100 to-sky-100 dark:from-blue-900/50 dark:to-sky-900/50 text-blue-700 dark:text-blue-300 text-sm font-medium mb-6">
                <flux:icon name="sparkles" class="size-4" />
                We're here to help!
            </div>
            <flux:heading size="xl" level="1" class="text-3xl sm:text-4xl lg:text-5xl bg-linear-to-r! from-blue-600! via-sky-600! to-cyan-600! dark:from-blue-400! dark:via-sky-400! dark:to-cyan-400! bg-clip-text! text-transparent!">
                How can we help you?
            </flux:heading>
            <flux:text class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                Browse our frequently asked questions below or get in touch with our support team.
            </flux:text>
            @auth
                <div class="mt-8">
                    <flux:button href="{{ url('/dashboard') }}" variant="primary" icon="ticket" class="bg-linear-to-r! from-blue-500! to-sky-500! hover:from-blue-600! hover:to-sky-600! border-0! shadow-lg! shadow-blue-500/25!">
                        Submit a Ticket
                    </flux:button>
                </div>
            @else
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <flux:button href="{{ route('register') }}" variant="primary" icon="ticket" class="bg-linear-to-r! from-blue-500! to-sky-500! hover:from-blue-600! hover:to-sky-600! border-0! shadow-lg! shadow-blue-500/25!">
                        Submit a Ticket
                    </flux:button>
                    <flux:text size="sm" class="text-zinc-500">
                        Already have an account?
                        <flux:link href="{{ route('login') }}" class="text-blue-600! dark:text-blue-400!">Log in</flux:link>
                    </flux:text>
                </div>
            @endauth
        </div>
    </section>

    {{-- FAQs Section --}}
    <section class="py-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <flux:heading size="lg" level="2" class="bg-linear-to-r from-blue-600 to-sky-600 dark:from-blue-400 dark:to-sky-400 bg-clip-text text-transparent">Frequently Asked Questions</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Find answers to common questions about our services.
                </flux:text>
            </div>

            @if($this->faqs->count() > 0)
                <div class="space-y-4">
                    @foreach($this->faqs as $faq)
                        <div
                            x-data="{ open: false }"
                            class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm rounded-xl border border-blue-100 dark:border-zinc-700 overflow-hidden shadow-sm hover:shadow-md hover:shadow-blue-200/50 dark:hover:shadow-blue-900/20 transition-all duration-300"
                        >
                            <button
                                @click="open = !open"
                                class="w-full px-6 py-4 text-left flex items-center justify-between gap-4 hover:bg-blue-50/50 dark:hover:bg-zinc-700/50 transition-colors"
                            >
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $faq->question }}</span>
                                <flux:icon
                                    name="chevron-down"
                                    class="size-5 text-blue-400 transition-transform duration-200"
                                    ::class="open && 'rotate-180'"
                                />
                            </button>
                            <div
                                x-show="open"
                                x-collapse
                                class="px-6 pb-4"
                            >
                                <flux:text class="text-zinc-600 dark:text-zinc-400">
                                    {!! nl2br(e($faq->answer)) !!}
                                </flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:card class="text-center py-12 bg-white/80! dark:bg-zinc-800/80! backdrop-blur-sm border-blue-100! dark:border-zinc-700!">
                    <flux:icon name="question-mark-circle" class="size-12 text-blue-300 dark:text-blue-600 mx-auto" />
                    <flux:heading size="lg" class="mt-4 text-blue-600 dark:text-blue-400">No FAQs yet</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">
                        Check back later for frequently asked questions.
                    </flux:text>
                </flux:card>
            @endif
        </div>
    </section>

    {{-- Contact Section --}}
    <section class="py-16 bg-white/60 dark:bg-zinc-800/60 backdrop-blur-sm border-t border-blue-100 dark:border-zinc-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <flux:heading size="lg" level="2" class="bg-linear-to-r from-blue-600 to-sky-600 dark:from-blue-400 dark:to-sky-400 bg-clip-text text-transparent">Still need help?</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Our support team is here to assist you.
                </flux:text>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:card class="text-center bg-white/80! dark:bg-zinc-800/80! backdrop-blur-sm border-blue-100! dark:border-zinc-700! hover:shadow-lg hover:shadow-blue-200/30 dark:hover:shadow-blue-900/20 transition-all duration-300 group">
                    <div class="w-14 h-14 mx-auto rounded-xl bg-linear-to-br from-blue-500 to-sky-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="ticket" class="size-7 text-white" />
                    </div>
                    <flux:heading class="mt-4">Submit a Ticket</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        Create a support ticket and we'll respond within 24 hours.
                    </flux:text>
                    @auth
                        <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm" class="mt-4 text-blue-600! dark:text-blue-400!">
                            Go to Dashboard
                        </flux:button>
                    @else
                        <flux:button href="{{ route('register') }}" variant="ghost" size="sm" class="mt-4 text-blue-600! dark:text-blue-400!">
                            Get Started
                        </flux:button>
                    @endauth
                </flux:card>

                <flux:card class="text-center bg-white/80! dark:bg-zinc-800/80! backdrop-blur-sm border-sky-100! dark:border-zinc-700! hover:shadow-lg hover:shadow-sky-200/30 dark:hover:shadow-sky-900/20 transition-all duration-300 group">
                    <div class="w-14 h-14 mx-auto rounded-xl bg-linear-to-br from-sky-500 to-cyan-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="envelope" class="size-7 text-white" />
                    </div>
                    <flux:heading class="mt-4">Email Us</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        Send us an email and we'll get back to you soon.
                    </flux:text>
                    <flux:button href="mailto:support@example.com" variant="ghost" size="sm" class="mt-4 text-sky-600! dark:text-sky-400!">
                        support@example.com
                    </flux:button>
                </flux:card>

                <flux:card class="text-center bg-white/80! dark:bg-zinc-800/80! backdrop-blur-sm border-cyan-100! dark:border-zinc-700! hover:shadow-lg hover:shadow-cyan-200/30 dark:hover:shadow-cyan-900/20 transition-all duration-300 group">
                    <div class="w-14 h-14 mx-auto rounded-xl bg-linear-to-br from-cyan-500 to-teal-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="book-open" class="size-7 text-white" />
                    </div>
                    <flux:heading class="mt-4">Documentation</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        Browse our documentation for detailed guides.
                    </flux:text>
                    <flux:button href="#" variant="ghost" size="sm" class="mt-4 text-cyan-600! dark:text-cyan-400!">
                        View Docs
                    </flux:button>
                </flux:card>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-white/40 dark:bg-zinc-900/80 backdrop-blur-sm border-t border-blue-100 dark:border-zinc-700 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <flux:text size="sm" class="text-zinc-500">
                    &copy; {{ date('Y') }} Support Manager. All rights reserved.
                </flux:text>
                <div class="flex items-center gap-6">
                    <flux:link href="#" variant="subtle" class="text-sm text-blue-600! dark:text-blue-400!">Privacy Policy</flux:link>
                    <flux:link href="#" variant="subtle" class="text-sm text-blue-600! dark:text-blue-400!">Terms of Service</flux:link>
                </div>
            </div>
        </div>
    </footer>
</div>
