@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Gamertag</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ \App\Models\Gamertag::PLATFORMS[$gamertag->platform] }} • {{ $gamertag->gamertag }}
                        </p>
                    </div>
                    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>

                <form action="{{ route('gamertags.update', $gamertag) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Platform Display (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Gaming Platform
                        </label>
                        <div class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md text-gray-700 dark:text-gray-300">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full mr-2
                                {{ $gamertag->platform === 'steam' ? 'bg-gray-200 text-gray-800 dark:bg-gray-500 dark:text-gray-200' : '' }}
                                {{ $gamertag->platform === 'xbox_live' ? 'bg-green-200 text-green-800 dark:bg-green-500 dark:text-green-200' : '' }}
                                {{ $gamertag->platform === 'playstation_network' ? 'bg-blue-200 text-blue-800 dark:bg-blue-500 dark:text-blue-200' : '' }}
                                {{ $gamertag->platform === 'nintendo_online' ? 'bg-red-200 text-red-800 dark:bg-red-500 dark:text-red-200' : '' }}
                                {{ $gamertag->platform === 'battlenet' ? 'bg-purple-200 text-purple-800 dark:bg-purple-500 dark:text-purple-200' : '' }}">
                                {{ \App\Models\Gamertag::PLATFORMS[$gamertag->platform] }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Platform cannot be changed. Create a new gamertag for different platforms.
                        </p>
                    </div>

                    <!-- Gamertag -->
                    <div>
                        <label for="gamertag" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Gamertag / Username
                        </label>
                        <input 
                            type="text" 
                            id="gamertag" 
                            name="gamertag" 
                            value="{{ old('gamertag', $gamertag->gamertag) }}"
                            required
                            placeholder="Enter your gamertag"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('gamertag') border-red-500 @enderror"
                        >
                        @error('gamertag')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Enter your exact username as it appears on {{ \App\Models\Gamertag::PLATFORMS[$gamertag->platform] }}
                        </p>
                    </div>

                    <!-- Display Name (Optional) -->
                    <div>
                        <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Display Name (Optional)
                        </label>
                        <input 
                            type="text" 
                            id="display_name" 
                            name="display_name" 
                            value="{{ old('display_name', $gamertag->display_name) }}"
                            placeholder="Friendly display name"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('display_name') border-red-500 @enderror"
                        >
                        @error('display_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            An optional friendly name to display instead of your gamertag
                        </p>
                    </div>

                    <!-- Privacy Settings -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Privacy & Settings</h3>
                        
                        <!-- Public/Private -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="is_public" 
                                name="is_public" 
                                value="1"
                                {{ old('is_public', $gamertag->is_public) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                            >
                            <label for="is_public" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Make this gamertag public
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                            Public gamertags can be seen by other users in the community
                        </p>

                        <!-- Primary Gamertag -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="is_primary" 
                                name="is_primary" 
                                value="1"
                                {{ old('is_primary', $gamertag->is_primary) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                            >
                            <label for="is_primary" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Set as primary gamertag for this platform
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                            Your primary gamertag will be displayed prominently on your profile
                        </p>
                    </div>

                    <!-- Profile Link -->
                    @if($gamertag->profile_url)
                        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                            <h4 class="font-medium text-blue-900 dark:text-blue-200 mb-2">Profile Link</h4>
                            <a 
                                href="{{ $gamertag->profile_url }}" 
                                target="_blank" 
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 text-sm break-all"
                            >
                                {{ $gamertag->profile_url }}
                                <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                    @endif

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-between pt-6">
                        <button 
                            type="button"
                            onclick="confirmDelete()"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                        >
                            Delete Gamertag
                        </button>
                        
                        <div class="flex items-center space-x-4">
                            <a 
                                href="{{ route('dashboard') }}" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                            >
                                Update Gamertag
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Hidden Delete Form -->
                <form id="delete-form" action="{{ route('gamertags.destroy', $gamertag) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this gamertag? This action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endpush
@endsection
