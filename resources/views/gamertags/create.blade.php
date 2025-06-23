@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Add New Gamertag</h1>
                    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>

                <form action="{{ route('gamertags.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Platform Selection -->
                    <div>
                        <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Gaming Platform
                        </label>
                        <select 
                            id="platform" 
                            name="platform" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('platform') border-red-500 @enderror"
                        >
                            <option value="">Select a platform</option>
                            @foreach(\App\Models\Gamertag::PLATFORMS as $key => $name)
                                <option value="{{ $key }}" {{ old('platform') === $key ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                        @error('platform')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
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
                            value="{{ old('gamertag') }}"
                            required
                            placeholder="Enter your gamertag"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white @error('gamertag') border-red-500 @enderror"
                        >
                        @error('gamertag')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Enter your exact username as it appears on the platform
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
                            value="{{ old('display_name') }}"
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
                                {{ old('is_public', true) ? 'checked' : '' }}
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
                                {{ old('is_primary') ? 'checked' : '' }}
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

                    <!-- Platform-specific Info -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Platform Information</h4>
                        <div id="platform-info" class="text-sm text-gray-600 dark:text-gray-400">
                            Select a platform to see specific requirements and tips.
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6">
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
                            Add Gamertag
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const platformSelect = document.getElementById('platform');
    const platformInfo = document.getElementById('platform-info');
    
    const platformTips = {
        steam: 'Enter your Steam custom URL or display name. Example: "myusername" for steamcommunity.com/id/myusername',
        xbox_live: 'Enter your Xbox Gamertag exactly as it appears on Xbox Live. Case sensitive.',
        playstation_network: 'Enter your PlayStation Network ID (PSN ID). This is your unique username.',
        nintendo_online: 'Enter your Nintendo Account username or friend code.',
        battlenet: 'Enter your Battle.net BattleTag including the # and numbers. Example: Username#1234'
    };
    
    platformSelect.addEventListener('change', function() {
        const selectedPlatform = this.value;
        if (selectedPlatform && platformTips[selectedPlatform]) {
            platformInfo.textContent = platformTips[selectedPlatform];
        } else {
            platformInfo.textContent = 'Select a platform to see specific requirements and tips.';
        }
    });
});
</script>
@endsection
