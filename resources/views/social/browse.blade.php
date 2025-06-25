@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Browse Users') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('social.browse') }}" class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filter by Platform</label>
                        <select name="platform" id="platform" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" onchange="this.form.submit()">
                            <option value="">All Platforms</option>
                            @foreach($platforms as $platformKey)
                                <option value="{{ $platformKey }}" {{ $platform === $platformKey ? 'selected' : '' }}>
                                    {{ \App\Models\Gamertag::PLATFORMS[$platformKey] ?? ucfirst($platformKey) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($users as $user)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- User Info -->
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-xl font-medium text-gray-700 dark:text-gray-300">
                                    {{ substr($user->name, 0, 1) }}
                                </span>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->gamertags_count }} public gamertags</p>
                            </div>
                        </div>

                        <!-- Gamertags -->
                        @if($user->gamertags->count() > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gamertags:</h4>
                                <div class="space-y-2">
                                    @foreach($user->gamertags as $gamertag)
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $gamertag->gamertag }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $gamertag->formatted_platform }}</p>
                                            </div>
                                            @if($gamertag->is_primary)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                    Primary
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif                        <!-- Connection Actions -->
                        @php
                            $connection = \App\Models\UserConnection::where(function ($query) use ($user) {
                                $query->where('requester_id', Auth::id())
                                      ->where('recipient_id', $user->id);
                            })->orWhere(function ($query) use ($user) {
                                $query->where('requester_id', $user->id)
                                      ->where('recipient_id', Auth::id());
                            })->first();

                            $connectionStatus = $connection ? [
                                'status' => $connection->status,
                                'is_requester' => $connection->requester_id === Auth::id(),
                                'connection' => $connection,
                            ] : null;
                        @endphp

                        @if($connectionStatus === null)
                            <div class="space-y-2">
                                <form action="{{ route('user-connections.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="recipient_id" value="{{ $user->id }}">
                                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                                        Send Connection Request
                                    </button>
                                </form>
                                <a href="{{ route('user-connections.create', ['user_id' => $user->id]) }}" class="block w-full text-center px-4 py-2 border border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 focus:ring-2 focus:ring-blue-500">
                                    Add Personal Message
                                </a>
                            </div>
                        @elseif($connectionStatus['status'] === 'pending')
                            @if($connectionStatus['is_requester'])
                                <div class="flex space-x-2">
                                    <span class="flex-1 text-center px-4 py-2 bg-yellow-100 text-yellow-800 rounded-md">
                                        Request Sent
                                    </span>
                                    <form action="{{ route('user-connections.cancel', $connectionStatus['connection']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700" title="Cancel Request">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="space-y-2">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center">Connection request received</p>
                                    <div class="flex space-x-2">
                                        <form action="{{ route('user-connections.accept', $connectionStatus['connection']) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                                Accept
                                            </button>
                                        </form>
                                        <form action="{{ route('user-connections.decline', $connectionStatus['connection']) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                                Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @elseif($connectionStatus['status'] === 'accepted')
                            <div class="flex space-x-2">
                                <span class="flex-1 text-center px-4 py-2 bg-green-100 text-green-800 rounded-md">
                                    Connected
                                </span>
                                <form action="{{ route('user-connections.destroy', $connectionStatus['connection']) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700" title="Remove Connection" onclick="return confirm('Are you sure you want to remove this connection?')">>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @elseif($connectionStatus['status'] === 'declined')
                            <span class="w-full text-center px-4 py-2 bg-gray-100 text-gray-800 rounded-md">
                                Request Declined
                            </span>
                        @elseif($connectionStatus['status'] === 'blocked')
                            <span class="w-full text-center px-4 py-2 bg-red-100 text-red-800 rounded-md">
                                Blocked
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="mt-8">
                {{ $users->links() }}
            </div>
        @endif

        <!-- Empty State -->
        @if($users->count() === 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No users found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        @if(!empty($platform) && isset(\App\Models\Gamertag::PLATFORMS[$platform]))
                            No users with public gamertags on {{ \App\Models\Gamertag::PLATFORMS[$platform] }}.
                        @elseif(!empty($platform))
                            No users with public gamertags on {{ ucfirst($platform) }}.
                        @else
                            No users with public gamertags found.
                        @endif
                    </p>
                    @if(!empty($platform))
                        <a href="{{ route('social.browse') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Show All Platforms
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
