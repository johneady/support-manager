<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Privacy Policy - {{ config('app.name', 'Support Manager') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="antialiased">
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

            {{-- Content --}}
            <article class="py-12 sm:py-16">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <flux:heading size="xl" level="1" class="text-3xl sm:text-4xl bg-linear-to-r! from-blue-600! via-sky-600! to-cyan-600! dark:from-blue-400! dark:via-sky-400! dark:to-cyan-400! bg-clip-text! text-transparent!">
                        Privacy Policy
                    </flux:heading>

                    <div class="mt-8 rounded-2xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-blue-100 dark:border-zinc-700 px-6 sm:px-8 py-6">
                        <div class="prose dark:prose-invert prose-blue max-w-none prose-headings:font-semibold prose-a:text-blue-600 dark:prose-a:text-blue-400">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Last updated: {{ now()->format('F j, Y') }}</p>

                            <h2>Introduction</h2>
                            <p>We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your personal information when you use our support platform.</p>

                            <h2>Information We Collect</h2>
                            <p>We collect information that you provide directly when using our platform, including:</p>
                            <ul>
                                <li><strong>Account information</strong> &mdash; your name, email address, and password when you register for an account.</li>
                                <li><strong>Support ticket content</strong> &mdash; the messages, descriptions, and any attachments you submit through support tickets.</li>
                                <li><strong>Usage data</strong> &mdash; information about how you interact with our platform, including pages visited and features used.</li>
                            </ul>

                            <h2>How We Use Your Information</h2>
                            <p>We use the information we collect to:</p>
                            <ul>
                                <li>Provide, maintain, and improve our support services.</li>
                                <li>Process and respond to your support tickets.</li>
                                <li>Send you important notifications about your account or tickets.</li>
                                <li>Monitor and analyze usage patterns to improve the platform.</li>
                                <li>Protect against unauthorized access and ensure platform security.</li>
                            </ul>

                            <h2>Contact and Communication</h2>
                            <p>All communication with our team is conducted exclusively through support tickets submitted via this platform. We do not offer support through external email, phone, or social media channels. Any correspondence related to your account or inquiries should be submitted as a support ticket.</p>

                            <h2>Data Storage and Security</h2>
                            <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the Internet or electronic storage is completely secure, and we cannot guarantee absolute security.</p>

                            <h2>Data Retention</h2>
                            <p>We retain your personal information for as long as your account is active or as needed to provide you with our services. Support ticket data is retained to maintain a complete history of interactions for reference purposes. If you wish to request deletion of your data, please submit a support ticket.</p>

                            <h2>Third-Party Services</h2>
                            <p>We do not sell, trade, or rent your personal information to third parties. We may share information with trusted service providers who assist us in operating our platform, provided they agree to keep your information confidential.</p>

                            <h2>Cookies</h2>
                            <p>Our platform uses cookies that are essential for the operation of the service, such as session cookies for authentication. These cookies are necessary for the platform to function correctly and cannot be disabled.</p>

                            <h2>Your Rights</h2>
                            <p>You have the right to:</p>
                            <ul>
                                <li>Access the personal information we hold about you.</li>
                                <li>Request correction of inaccurate information.</li>
                                <li>Request deletion of your personal data, subject to any legal obligations we may have to retain it.</li>
                                <li>Withdraw consent where processing is based on consent.</li>
                            </ul>
                            <p>To exercise any of these rights, please submit a support ticket through the platform.</p>

                            <h2>Changes to This Policy</h2>
                            <p>We may update this Privacy Policy from time to time. We will notify you of any significant changes by posting the updated policy on this page with a revised "Last updated" date. Your continued use of the platform after any changes constitutes acceptance of the updated policy.</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <flux:button href="{{ url('/') }}" variant="ghost" icon="arrow-left" class="text-blue-600! dark:text-blue-400!">
                            Back to Home
                        </flux:button>
                    </div>
                </div>
            </article>

            {{-- Footer --}}
            <footer class="bg-white/40 dark:bg-zinc-900/80 backdrop-blur-sm border-t border-blue-100 dark:border-zinc-700 py-8">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <flux:text size="sm" class="text-zinc-500">
                            &copy; {{ date('Y') }} Support Manager. All rights reserved.
                        </flux:text>
                        <div class="flex items-center gap-6">
                            <flux:link href="{{ route('privacy-policy') }}" variant="subtle" class="text-sm text-blue-600! dark:text-blue-400!">Privacy Policy</flux:link>
                            <flux:link href="{{ route('terms-of-service') }}" variant="subtle" class="text-sm text-blue-600! dark:text-blue-400!">Terms of Service</flux:link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
