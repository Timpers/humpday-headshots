@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Connection Requests') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Received Requests -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Received Requests ({{ $receivedRequests->count() }})
                    </h3>
                    
                    @if($receivedRequests->count() > 0)
                        <div class="space-y-4">
                            @foreach($receivedRequests as $request)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <div class="flex items-center mb-3">
                                        <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <span class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                                {{ substr($request->requester->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $request->requester->name }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->requester->email }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500">{{ $request->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>

                                    @if($request->message)
                                        <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $request->message }}</p>
                                        </div>
                                    @endif

                                    @if($request->requester->gamertags->count() > 0)
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Gamertags:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($request->requester->gamertags->take(3) as $gamertag)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                        {{ $gamertag->formatted_platform }}: {{ $gamertag->gamertag }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex space-x-2">
                                        <form action="{{ route('connections.accept', $request) }}" method="POST" class="flex-1">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                                Accept
                                            </button>
                                        </form>
                                        <form action="{{ route('connections.decline', $request) }}" method="POST" class="flex-1">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                                Decline
                                            </button>
                                        </form>
                                        <form action="{{ route('connections.block', $request) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                                    title="Block User"
                                                    onclick="return confirm('Are you sure you want to block {{ $request->requester->name }}?')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400">No pending requests</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sent Requests -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Sent Requests ({{ $sentRequests->count() }})
                    </h3>
                    
                    @if($sentRequests->count() > 0)
                        <div class="space-y-4">
                            @foreach($sentRequests as $request)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <div class="flex items-center mb-3">
                                        <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <span class="text-lg font-medium text-gray-700 dark:text-gray-300">
                                                {{ substr($request->recipient->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $request->recipient->name }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $request->recipient->email }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500">Sent {{ $request->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>

                                    @if($request->message)
                                        <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                            <p class="text-sm text-gray-700 dark:text-gray-300">Your message: "{{ $request->message }}"</p>
                                        </div>
                                    @endif

                                    @if($request->recipient->gamertags->count() > 0)
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Their gamertags:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($request->recipient->gamertags->take(3) as $gamertag)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                        {{ $gamertag->formatted_platform }}: {{ $gamertag->gamertag }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex justify-between items-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Pending
                                        </span>
                                        <form action="{{ route('connections.cancel', $request) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                                    onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel Request
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            <p class="text-gray-600 dark:text-gray-400">No sent requests</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
