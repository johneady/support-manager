<x-layouts::app :title="__('Health')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        {{-- Welcome Header --}}
        <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="rounded-full bg-white/20 p-3">
                        <flux:icon.shield-check class="size-8 text-white" />
                    </div>
                    <div>
                        <flux:heading size="2xl" class="text-white">System Health</flux:heading>
                        <flux:text class="text-blue-100">Monitor the health status of your application.</flux:text>
                    </div>
                </div>
                <form method="GET" action="{{ route('health') }}">
                    <input type="hidden" name="fresh" value="1" />
                    <button
                        type="submit"
                        class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50"
                    >
                        <span wire:loading.remove>Run Health Check</span>
                        <span wire:loading>Running...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Health Check Results --}}
        <div class="flex-1 overflow-hidden rounded-xl border border-blue-100 bg-white/80 p-6 backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-800/80">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Health Check Results</h2>
                @if ($lastRanAt)
                    <div class="{{ $lastRanAt->diffInMinutes() > 5 ? 'text-red-400' : 'text-zinc-500 dark:text-zinc-400' }} text-sm font-medium">
                        Last run: {{ $lastRanAt->diffForHumans() }}
                    </div>
                @endif
            </div>

            @if (count($checkResults?->storedCheckResults ?? []))
                <dl class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2 md:gap-5 lg:grid-cols-3">
                    @foreach ($checkResults->storedCheckResults as $result)
                        <div class="flex transform items-start space-x-3 overflow-hidden rounded-xl border border-zinc-200 bg-white px-4 py-5 transition hover:shadow-md hover:shadow-blue-200/30 sm:p-6 md:min-h-[130px] dark:border-zinc-700 dark:bg-zinc-900 dark:hover:shadow-blue-900/20">
                            @php
                                $statusColor = match ($result->status) {
                                    'ok' => 'bg-green-500',
                                    'warning' => 'bg-amber-500',
                                    'failed' => 'bg-red-500',
                                    'crashed' => 'bg-red-600',
                                    default => 'bg-zinc-500',
                                };
                            @endphp
                            <div class="mt-1 flex-shrink-0">
                                <div class="w-3 h-3 rounded-full {{ $statusColor }}"></div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <dd class="-mt-1 font-bold text-zinc-900 md:mt-1 md:text-lg dark:text-white">
                                    {{ $result->label }}
                                </dd>
                                <dt class="mt-0 text-sm font-medium text-zinc-600 md:mt-1 dark:text-zinc-300">
                                    @if (! empty($result->notificationMessage))
                                        {{ $result->notificationMessage }}
                                    @else
                                        {{ $result->shortSummary }}
                                    @endif
                                </dt>
                            </div>
                        </div>
                    @endforeach
                </dl>
            @else
                <div class="rounded-lg border border-zinc-200 bg-white/50 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                    <flux:icon.inbox class="mx-auto h-12 w-12 text-zinc-400" />
                    <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No health check results</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Health checks have not been run yet.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
