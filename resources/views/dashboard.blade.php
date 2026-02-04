<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        {{-- Welcome Header --}}
        <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-violet-400/85 via-pink-400/85 to-amber-400/85 p-6 text-white shadow-md shadow-violet-500/15">
            <div class="absolute inset-0 bg-linear-to-r from-violet-600/20 to-transparent"></div>
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <h1 class="text-2xl font-bold text-white">Welcome back! 👋</h1>
                <p class="mt-1 text-white">Here's what's happening with your support tickets.</p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-violet-100 dark:border-zinc-700 p-5 shadow-sm hover:shadow-lg hover:shadow-violet-200/30 dark:hover:shadow-violet-900/20 transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Open Tickets</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-linear-to-br from-violet-500 to-pink-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 003 5.5v2.879a2.5 2.5 0 00.732 1.767l6.5 6.5a2.5 2.5 0 003.536 0l2.878-2.878a2.5 2.5 0 000-3.536l-6.5-6.5A2.5 2.5 0 008.38 3H5.5zM6 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-500">Awaiting response</p>
            </div>

            <div class="relative overflow-hidden rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-pink-100 dark:border-zinc-700 p-5 shadow-sm hover:shadow-lg hover:shadow-pink-200/30 dark:hover:shadow-pink-900/20 transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">In Progress</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-linear-to-br from-pink-500 to-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-500">Being worked on</p>
            </div>

            <div class="relative overflow-hidden rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-amber-100 dark:border-zinc-700 p-5 shadow-sm hover:shadow-lg hover:shadow-amber-200/30 dark:hover:shadow-amber-900/20 transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Resolved</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">0</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-linear-to-br from-amber-500 to-orange-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-6 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-500">Completed this month</p>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="relative flex-1 overflow-hidden rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-violet-100 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('tickets.create') }}" class="flex items-center gap-4 p-4 rounded-xl bg-linear-to-br from-violet-50 to-pink-50 dark:from-zinc-700/50 dark:to-zinc-700/30 border border-violet-100 dark:border-zinc-600 hover:shadow-md hover:shadow-violet-200/30 dark:hover:shadow-violet-900/20 transition-all duration-300 group">
                    <div class="w-10 h-10 rounded-lg bg-linear-to-br from-violet-500 to-pink-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">New Ticket</p>
                        <p class="text-xs text-zinc-500">Create a support request</p>
                    </div>
                </a>

                <a href="{{ route('tickets.index') }}" class="flex items-center gap-4 p-4 rounded-xl bg-linear-to-br from-pink-50 to-rose-50 dark:from-zinc-700/50 dark:to-zinc-700/30 border border-pink-100 dark:border-zinc-600 hover:shadow-md hover:shadow-pink-200/30 dark:hover:shadow-pink-900/20 transition-all duration-300 group">
                    <div class="w-10 h-10 rounded-lg bg-linear-to-br from-pink-500 to-rose-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 4.75A.75.75 0 016.75 4h10.5a.75.75 0 010 1.5H6.75A.75.75 0 016 4.75zM6 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75A.75.75 0 016 10zm0 5.25a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75a.75.75 0 01-.75-.75zM1.99 4.75a1 1 0 011-1H3a1 1 0 011 1v.01a1 1 0 01-1 1h-.01a1 1 0 01-1-1v-.01zM1.99 15.25a1 1 0 011-1H3a1 1 0 011 1v.01a1 1 0 01-1 1h-.01a1 1 0 01-1-1v-.01zM1.99 10a1 1 0 011-1H3a1 1 0 011 1v.01a1 1 0 01-1 1h-.01a1 1 0 01-1-1V10z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">View Tickets</p>
                        <p class="text-xs text-zinc-500">See all your tickets</p>
                    </div>
                </a>

                <a href="{{ route('faq') }}" class="flex items-center gap-4 p-4 rounded-xl bg-linear-to-br from-amber-50 to-orange-50 dark:from-zinc-700/50 dark:to-zinc-700/30 border border-amber-100 dark:border-zinc-600 hover:shadow-md hover:shadow-amber-200/30 dark:hover:shadow-amber-900/20 transition-all duration-300 group">
                    <div class="w-10 h-10 rounded-lg bg-linear-to-br from-amber-500 to-orange-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.94 6.94a.75.75 0 11-1.06-1.061 3 3 0 112.871 5.026v.345a.75.75 0 01-1.5 0v-.5c0-.72.57-1.172 1.081-1.287A1.5 1.5 0 108.94 6.94zM10 15a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">Browse FAQs</p>
                        <p class="text-xs text-zinc-500">Find quick answers</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-layouts::app>
