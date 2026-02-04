<x-filament-panels::page>
    {{ $this->infolist }}

    <x-filament::section heading="Conversation">
        @php
            $replies = $this->getRecord()->replies()->with('user')->orderBy('created_at')->get();
        @endphp

        @if($replies->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">No replies yet.</p>
        @else
            <div class="space-y-4">
                @foreach($replies as $reply)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 {{ $reply->is_from_admin ? 'bg-primary-50 dark:bg-primary-900/20 ml-8' : 'bg-gray-50 dark:bg-gray-800 mr-8' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-sm {{ $reply->is_from_admin ? 'text-primary-600 dark:text-primary-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $reply->user->name }}
                                </span>
                                @if($reply->is_from_admin)
                                    <span class="inline-flex items-center rounded-full bg-primary-100 dark:bg-primary-800 px-2 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300">
                                        Admin
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $reply->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <div class="prose dark:prose-invert prose-sm max-w-none">
                            {!! nl2br(e($reply->body)) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
