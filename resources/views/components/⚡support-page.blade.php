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

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    {{-- Header --}}
    <header class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-2">
                    <x-app-logo-icon class="size-8" />
                    <span class="font-semibold text-zinc-900 dark:text-white">Support Manager</span>
                </a>
                <div class="flex items-center gap-4">
                    @auth
                        <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm">Dashboard</flux:button>
                    @else
                        @if (Route::has('login'))
                            <flux:button href="{{ route('login') }}" variant="ghost" size="sm">Log in</flux:button>
                        @endif
                        @if (Route::has('register'))
                            <flux:button href="{{ route('register') }}" variant="primary" size="sm">Register</flux:button>
                        @endif
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    {{-- Hero Section --}}
    <section class="bg-linear-to-b from-white to-zinc-50 dark:from-zinc-800 dark:to-zinc-900 py-16 sm:py-24">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <flux:heading size="xl" level="1" class="text-3xl sm:text-4xl lg:text-5xl">
                How can we help you?
            </flux:heading>
            <flux:text class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                Browse our frequently asked questions below or get in touch with our support team.
            </flux:text>
            @auth
                <div class="mt-8">
                    <flux:button href="{{ url('/dashboard') }}" variant="primary" icon="ticket">
                        Submit a Ticket
                    </flux:button>
                </div>
            @else
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <flux:button href="{{ route('register') }}" variant="primary" icon="ticket">
                        Submit a Ticket
                    </flux:button>
                    <flux:text size="sm" class="text-zinc-500">
                        Already have an account?
                        <flux:link href="{{ route('login') }}">Log in</flux:link>
                    </flux:text>
                </div>
            @endauth
        </div>
    </section>

    {{-- FAQs Section --}}
    <section class="py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <flux:heading size="lg" level="2">Frequently Asked Questions</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Find answers to common questions about our services.
                </flux:text>
            </div>

            @if($this->faqs->count() > 0)
                <div class="space-y-4">
                    @foreach($this->faqs as $faq)
                        <div
                            x-data="{ open: false }"
                            class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden"
                        >
                            <button
                                @click="open = !open"
                                class="w-full px-6 py-4 text-left flex items-center justify-between gap-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors"
                            >
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $faq->question }}</span>
                                <flux:icon
                                    name="chevron-down"
                                    class="size-5 text-zinc-400 transition-transform duration-200"
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
                <flux:card class="text-center py-12">
                    <flux:icon name="question-mark-circle" class="size-12 text-zinc-300 dark:text-zinc-600 mx-auto" />
                    <flux:heading size="lg" class="mt-4">No FAQs yet</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">
                        Check back later for frequently asked questions.
                    </flux:text>
                </flux:card>
            @endif
        </div>
    </section>

    {{-- Contact Section --}}
    <section class="py-16 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <flux:heading size="lg" level="2">Still need help?</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-400">
                    Our support team is here to assist you.
                </flux:text>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:card class="text-center">
                    <flux:icon name="ticket" class="size-10 text-zinc-400 mx-auto" />
                    <flux:heading class="mt-4">Submit a Ticket</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        Create a support ticket and we'll respond within 24 hours.
                    </flux:text>
                    @auth
                        <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm" class="mt-4">
                            Go to Dashboard
                        </flux:button>
                    @else
                        <flux:button href="{{ route('register') }}" variant="ghost" size="sm" class="mt-4">
                            Get Started
                        </flux:button>
                    @endauth
                </flux:card>

                <flux:card class="text-center">
                    <flux:icon name="envelope" class="size-10 text-zinc-400 mx-auto" />
                    <flux:heading class="mt-4">Email Us</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        Send us an email and we'll get back to you soon.
                    </flux:text>
                    <flux:button href="mailto:support@example.com" variant="ghost" size="sm" class="mt-4">
                        support@example.com
                    </flux:button>
                </flux:card>

                <flux:card class="text-center">
                    <flux:icon name="book-open" class="size-10 text-zinc-400 mx-auto" />
                    <flux:heading class="mt-4">Documentation</flux:heading>
                    <flux:text size="sm" class="mt-2 text-zinc-500">
                        Browse our documentation for detailed guides.
                    </flux:text>
                    <flux:button href="#" variant="ghost" size="sm" class="mt-4">
                        View Docs
                    </flux:button>
                </flux:card>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-zinc-50 dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <flux:text size="sm" class="text-zinc-500">
                    &copy; {{ date('Y') }} Support Manager. All rights reserved.
                </flux:text>
                <div class="flex items-center gap-6">
                    <flux:link href="#" variant="subtle" class="text-sm">Privacy Policy</flux:link>
                    <flux:link href="#" variant="subtle" class="text-sm">Terms of Service</flux:link>
                </div>
            </div>
        </div>
    </footer>
</div>
