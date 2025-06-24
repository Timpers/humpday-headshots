@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Create Gaming Group') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form action="{{ route('groups.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Group Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Group Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name') }}"
                               required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., Halo Legends, COD Squad, FIFA Champions">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Description
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="4"
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Describe your group, what you play, when you play, skill level, etc.">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Game and Platform Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Game -->
                        <div>
                            <label for="game" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Primary Game
                            </label>
                            <select name="game" 
                                    id="game" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select a game (optional)</option>
                                @foreach($games as $gameKey => $gameName)
                                    <option value="{{ $gameKey }}" {{ old('game') === $gameKey ? 'selected' : '' }}>
                                        {{ $gameName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('game')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Platform -->
                        <div>
                            <label for="platform" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Platform
                            </label>
                            <select name="platform" 
                                    id="platform" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="">Select platform (optional)</option>
                                @foreach($platforms as $platformKey => $platformName)
                                    <option value="{{ $platformKey }}" {{ old('platform') === $platformKey ? 'selected' : '' }}>
                                        {{ $platformName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('platform')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Settings Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Max Members -->
                        <div>
                            <label for="max_members" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Maximum Members
                            </label>
                            <input type="number" 
                                   name="max_members" 
                                   id="max_members" 
                                   value="{{ old('max_members', 50) }}"
                                   min="2" 
                                   max="500"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Between 2 and 500 members</p>
                            @error('max_members')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Privacy Setting -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Group Privacy
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="is_public" 
                                           value="1" 
                                           {{ old('is_public', '1') === '1' ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Public - Anyone can join
                                    </span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="is_public" 
                                           value="0" 
                                           {{ old('is_public') === '0' ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Private - Invitation only
                                    </span>
                                </label>
                            </div>
                            @error('is_public')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Group Guidelines -->
                    <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                            Group Guidelines
                        </h4>
                        <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                            <li>• Choose a clear, descriptive name for your group</li>
                            <li>• Include information about skill level, play times, and expectations</li>
                            <li>• Be respectful and create an inclusive gaming environment</li>
                            <li>• You can edit these settings later as the group owner</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4">
                        <a href="{{ route('groups.index') }}" 
                           class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Create Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-suggest functionality for custom game input
    const gameSelect = document.getElementById('game');
    
    // Add "Other/Custom" option handler if needed
    gameSelect.addEventListener('change', function() {
        if (this.value === 'other') {
            // Could add custom input field here
            console.log('Other game selected');
        }
    });
});
</script>
@endpush
@endsection
