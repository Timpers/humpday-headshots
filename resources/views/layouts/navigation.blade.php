<!-- Navigation -->
<nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0">
                    <a href="{{ route('welcome') }}" class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        🎮 {{ config('app.name', 'Laravel') }}
                    </a>
                </div>                <!-- Navigation Links -->
                @auth                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }} text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('games.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('games.*') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }} text-sm font-medium">
                            Games
                        </a>
                        <a href="{{ route('gamertags.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('gamertags.*') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }} text-sm font-medium">
                            Gamertags
                        </a>                        <a href="{{ route('social.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('social.*') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }} text-sm font-medium">
                            Social
                            @php
                                $pendingCount = \App\Models\UserConnection::where('recipient_id', Auth::id())
                                    ->where('status', \App\Models\UserConnection::STATUS_PENDING)
                                    ->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ $pendingCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('groups.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('groups.*') && !request()->routeIs('groups.my-invitations') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }} text-sm font-medium">
                            Groups
                        </a>
                        <a href="{{ route('groups.my-invitations') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('groups.my-invitations') ? 'border-indigo-500 text-gray-900 dark:text-gray-100' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }} text-sm font-medium">
                            Invitations
                            @php
                                $pendingInvitations = \App\Models\GroupInvitation::where('invited_user_id', Auth::id())
                                    ->where('status', 'pending')
                                    ->count();
                            @endphp
                            @if($pendingInvitations > 0)
                                <span class="ml-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ $pendingInvitations }}</span>
                            @endif
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Authentication Links -->
            <div class="flex items-center space-x-4">
                @if (Route::has('login'))
                    @auth
                        <!-- User Dropdown -->
                        <div class="relative inline-block text-left">
                            <div class="flex items-center space-x-3">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('dashboard') }}" class="px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 sm:hidden">
                                        Dashboard
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition-colors">
                                Register
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    @auth
        <div class="sm:hidden">            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('dashboard') ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300' }} text-base font-medium">
                    Dashboard
                </a>
                <a href="{{ route('gamertag.test') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('gamertag.test') ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300' }} text-base font-medium">
                    Browse Gamertags
                </a>
                <a href="{{ route('social.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('social.*') ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300' }} text-base font-medium">
                    Social
                </a>
                <a href="{{ route('groups.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('groups.*') && !request()->routeIs('groups.my-invitations') ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300' }} text-base font-medium">
                    Groups
                </a>
                <a href="{{ route('groups.my-invitations') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('groups.my-invitations') ? 'border-indigo-500 text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300' }} text-base font-medium">
                    Invitations
                </a>
            </div>
        </div>
    @endauth
</nav>
