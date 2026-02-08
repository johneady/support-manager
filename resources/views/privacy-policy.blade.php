<x-layouts.guest :title="'Privacy Policy'">
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
</x-layouts.guest>
