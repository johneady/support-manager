@props(['wireModel' => ''])

<div
    wire:ignore
    x-data="window.tiptapEditorInit('{{ $wireModel }}')"
    class="markdown-editor rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden"
>
    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-0.5 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-2 py-1.5">
        {{-- Text formatting --}}
        <button type="button" x-on:click="toggleBold()" :class="isActive('bold') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Bold">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
        </button>
        <button type="button" x-on:click="toggleItalic()" :class="isActive('italic') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Italic">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
        </button>
        <button type="button" x-on:click="toggleCode()" :class="isActive('code') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Inline Code">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </button>
        <button type="button" x-on:click="toggleStrike()" :class="isActive('strike') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Strikethrough">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16" y1="4" x2="8" y2="4"/><line x1="12" y1="4" x2="12" y2="10"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="12" y1="14" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
        </button>
        <button type="button" x-on:click="toggleHighlight()" :class="isActive('highlight') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Highlight">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </button>

        <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></div>

        {{-- Headings --}}
        <button type="button" x-on:click="toggleHeading(2)" :class="isActive('heading', { level: 2 }) && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded px-1.5 py-1 text-xs font-bold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Heading 2">
            H2
        </button>
        <button type="button" x-on:click="toggleHeading(3)" :class="isActive('heading', { level: 3 }) && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded px-1.5 py-1 text-xs font-bold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Heading 3">
            H3
        </button>
        <button type="button" x-on:click="toggleHeading(4)" :class="isActive('heading', { level: 4 }) && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded px-1.5 py-1 text-xs font-bold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Heading 4">
            H4
        </button>

        <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></div>

        {{-- Lists --}}
        <button type="button" x-on:click="toggleBulletList()" :class="isActive('bulletList') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Bullet List">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1" fill="currentColor"/><circle cx="3" cy="12" r="1" fill="currentColor"/><circle cx="3" cy="18" r="1" fill="currentColor"/></svg>
        </button>
        <button type="button" x-on:click="toggleOrderedList()" :class="isActive('orderedList') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Ordered List">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="1" y="8" font-size="8" fill="currentColor" stroke="none" font-family="sans-serif">1</text><text x="1" y="14" font-size="8" fill="currentColor" stroke="none" font-family="sans-serif">2</text><text x="1" y="20" font-size="8" fill="currentColor" stroke="none" font-family="sans-serif">3</text></svg>
        </button>

        <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></div>

        {{-- Block elements --}}
        <button type="button" x-on:click="toggleBlockquote()" :class="isActive('blockquote') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Blockquote">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C9.591 11.69 11 13.166 11 15c0 1.933-1.567 3.5-3.5 3.5-1.252 0-2.421-.617-2.917-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C19.591 11.69 21 13.166 21 15c0 1.933-1.567 3.5-3.5 3.5-1.252 0-2.421-.617-2.917-1.179z"/></svg>
        </button>
        <button type="button" x-on:click="toggleCodeBlock()" :class="isActive('codeBlock') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Code Block">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><polyline points="9 8 5 12 9 16"/><polyline points="15 8 19 12 15 16"/></svg>
        </button>
        <button type="button" x-on:click="setHorizontalRule()" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Horizontal Rule">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"/></svg>
        </button>

        <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></div>

        {{-- Link --}}
        <button type="button" x-on:click="setLink()" :class="isActive('link') && 'bg-zinc-200 dark:bg-zinc-600'" class="rounded p-1.5 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors" title="Link">
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </button>
    </div>

    {{-- Editor content area --}}
    <div x-ref="editor" class="markdown-editor-content"></div>
</div>
