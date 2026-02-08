<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Terms of Service - {{ config('app.name', 'Support Manager') }}</title>

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
                        Terms of Service
                    </flux:heading>

                    <div class="mt-8 rounded-2xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-blue-100 dark:border-zinc-700 px-6 sm:px-8 py-6">
                        <div class="prose dark:prose-invert prose-blue max-w-none prose-headings:font-semibold prose-a:text-blue-600 dark:prose-a:text-blue-400">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Last updated: {{ now()->format('F j, Y') }}</p>

                            <h2>Acceptance of Terms</h2>
                            <p>By accessing or using this support platform, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you may not use the platform.</p>

                            <h2>Description of Service</h2>
                            <p>This platform provides a support ticket system that allows users to submit, track, and manage support requests. The service includes account registration, ticket submission, and access to a knowledge base of frequently asked questions.</p>

                            <h2>User Accounts</h2>
                            <p>To use the support features of this platform, you must register for an account. You agree to:</p>
                            <ul>
                                <li>Provide accurate and complete information during registration.</li>
                                <li>Maintain the security of your account credentials.</li>
                                <li>Notify us immediately of any unauthorized use of your account.</li>
                                <li>Accept responsibility for all activity that occurs under your account.</li>
                            </ul>
                            <p>We reserve the right to suspend or terminate accounts that violate these terms.</p>

                            <h2>Acceptable Use</h2>
                            <p>When using this platform, you agree not to:</p>
                            <ul>
                                <li>Submit false, misleading, or fraudulent support requests.</li>
                                <li>Use abusive, threatening, or harassing language in support tickets.</li>
                                <li>Attempt to gain unauthorized access to the platform or its systems.</li>
                                <li>Upload malicious files or content that could harm the platform or its users.</li>
                                <li>Use the platform for any illegal or unauthorized purpose.</li>
                                <li>Impersonate another person or entity.</li>
                            </ul>

                            <h2>Support Tickets</h2>
                            <p>All communication and support requests must be submitted through the ticket system provided on this platform. By submitting a support ticket, you understand that:</p>
                            <ul>
                                <li>Response times may vary depending on ticket volume and complexity.</li>
                                <li>Tickets are handled in order of priority and submission time.</li>
                                <li>You should provide as much relevant detail as possible to help us resolve your issue efficiently.</li>
                                <li>Ticket content may be reviewed by multiple team members as needed to resolve your request.</li>
                            </ul>

                            <h2>Intellectual Property</h2>
                            <p>The platform and its original content, features, and functionality are owned by us and are protected by applicable intellectual property laws. You retain ownership of any content you submit through support tickets, but grant us a license to use that content as necessary to provide and improve our services.</p>

                            <h2>Limitation of Liability</h2>
                            <p>To the fullest extent permitted by law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of the platform. This includes, but is not limited to, loss of data, loss of profits, or business interruption. Our total liability for any claim arising from use of the platform shall not exceed the amount you have paid to us, if any, in the twelve months preceding the claim.</p>

                            <h2>Disclaimer of Warranties</h2>
                            <p>The platform is provided on an "as is" and "as available" basis without warranties of any kind, either express or implied. We do not guarantee that the service will be uninterrupted, timely, secure, or error-free.</p>

                            <h2>Termination</h2>
                            <p>We reserve the right to suspend or terminate your access to the platform at our discretion, without notice, for conduct that we believe violates these terms or is harmful to other users, us, or third parties. Upon termination, your right to use the platform will immediately cease.</p>

                            <h2>Changes to These Terms</h2>
                            <p>We may revise these Terms of Service at any time. Changes will be posted on this page with a revised "Last updated" date. Your continued use of the platform following any changes constitutes acceptance of the new terms.</p>

                            <h2>Contact</h2>
                            <p>If you have any questions about these Terms of Service, please submit a support ticket through the platform.</p>
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
