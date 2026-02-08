<x-layouts.guest :title="$faq->question . ' - FAQ'">
    {{-- Breadcrumb --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <nav class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('faq') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">FAQ</a>
            <flux:icon name="chevron-right" class="size-4" />
            <span class="text-zinc-900 dark:text-white truncate">{{ Str::limit($faq->question, 60) }}</span>
        </nav>
    </div>

    {{-- Article --}}
    <article class="py-8 pb-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div>
                <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                    <span class="inline-flex items-center gap-1">
                        <flux:icon name="clock" class="size-4" />
                        {{ $faq->readingTime() }} min read
                    </span>
                </div>

                <flux:heading size="xl" level="1" class="text-2xl sm:text-3xl text-zinc-900! dark:text-white!">
                    {{ $faq->question }}
                </flux:heading>
            </div>

            <div class="mt-8">
                <div class="rounded-2xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-blue-100 dark:border-zinc-700 px-6 sm:px-8 py-6">
                    <div class="prose dark:prose-invert prose-blue max-w-none prose-headings:font-semibold prose-a:text-blue-600 dark:prose-a:text-blue-400">
                        {!! $faq->renderedAnswer() !!}
                    </div>
                </div>
            </div>

            {{-- Back link --}}
            <div class="mt-8">
                <flux:button href="{{ route('faq') }}" variant="ghost" icon="arrow-left" class="text-blue-600! dark:text-blue-400!">
                    Back to all FAQs
                </flux:button>
            </div>
        </div>
    </article>
</x-layouts.guest>
