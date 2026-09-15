<x-layouts.guest :title="$faq->question.' - FAQ'">
    {{-- Breadcrumb --}}
    <div class="mx-auto max-w-5xl px-4 pt-8 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('faq') }}" class="transition-colors hover:text-blue-600 dark:hover:text-blue-400">FAQ</a>
            <flux:icon name="chevron-right" class="size-4" />
            <span class="truncate text-zinc-900 dark:text-white">{{ Str::limit($faq->question, 60) }}</span>
        </nav>
    </div>

    {{-- Article --}}
    <article class="py-8 pb-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div>
                <div class="mb-4 flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                    <span class="inline-flex items-center gap-1">
                        <flux:icon name="clock" class="size-4" />
                        {{ $faq->readingTime() }} min read
                    </span>
                </div>

                <flux:heading size="xl" level="1" class="text-2xl text-zinc-900! sm:text-3xl dark:text-white!">
                    {{ $faq->question }}
                </flux:heading>
            </div>

            <div class="mt-8">
                <div class="rounded-2xl border border-blue-100 bg-white/80 px-6 py-6 backdrop-blur-sm sm:px-8 dark:border-zinc-700 dark:bg-zinc-800/80">
                    <div class="prose dark:prose-invert prose-blue prose-headings:font-semibold prose-a:text-blue-600 dark:prose-a:text-blue-400 max-w-none">
                        {!! $faq->renderedAnswer() !!}
                    </div>
                </div>
            </div>

            {{-- Back link --}}
            <div class="mt-8">
                <flux:button
                    href="{{ route('faq') }}"
                    variant="ghost"
                    icon="arrow-left"
                    class="text-blue-600! dark:text-blue-400!"
                >
                    Back to all FAQs
                </flux:button>
            </div>
        </div>
    </article>
</x-layouts.guest>
