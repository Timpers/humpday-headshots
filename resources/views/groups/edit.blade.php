@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Edit Group') }}: {{ $group->name }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <!-- Breadcrumb -->
                <div class="mb-6">
                    <nav class="text-sm">
                        <a href="{{ route('groups.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">All Groups</a>
                        <span class="mx-2 text-gray-500">/</span>
                        <a href="{{ route('groups.show', $group) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $group->name }}</a>
                        <span class="mx-2 text-gray-500">/</span>
                        <span class="text-gray-700 dark:text-gray-300">Edit</span>
                    </nav>
                </div>

                <form action="{{ route('groups.update', $group) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Group Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Group Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $group->name) }}"
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
                                  placeholder="Describe your group, what you play, when you play, skill level, etc.">{{ old('description', $group->description) }}</textarea>
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
                                    <option value="{{ $gameKey }}" {{ old('game', $group->game) === $gameKey ? 'selected' : '' }}>
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
                                    <option value="{{ $platformKey }}" {{ old('platform', $group->platform) === $platformKey ? 'selected' : '' }}>
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
                                   value="{{ old('max_members', $group->max_members) }}"
                                   min="2" 
                                   max="500"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Between 2 and 500 members (currently {{ $group->member_count }} members)
                            </p>
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
                                           {{ old('is_public', $group->is_public ? '1' : '0') === '1' ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Public - Anyone can join
                                    </span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="is_public" 
                                           value="0" 
                                           {{ old('is_public', $group->is_public ? '1' : '0') === '0' ? 'checked' : '' }}
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

                    <!-- Warning for Max Members -->
                    @if($group->member_count > 2)
                        <div class="bg-yellow-50 dark:bg-yellow-900/50 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 15c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        Member Limit Warning
                                    </h4>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        If you reduce the member limit below the current member count ({{ $group->member_count }}), you'll need to remove members manually.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Group Guidelines -->
                    <div class="bg-blue-50 dark:bg-blue-900/50 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                            Edit Guidelines
                        </h4>
                        <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                            <li>• Changes to group settings are immediately visible to all members</li>
                            <li>• Making a group private will hide it from public discovery</li>
                            <li>• Reducing member limits may require removing current members</li>
                            <li>• Game and platform changes help members find relevant groups</li>
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4">
                        <div class="flex gap-2">
                            <a href="{{ route('groups.show', $group) }}" 
                               class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                            
                            @if($group->isOwner(Auth::user()))
                                <button type="button" 
                                        onclick="confirmDelete()"
                                        class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Delete Group
                                </button>
                            @endif
                        </div>
                        
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Group
                        </button>
                    </div>
                </form>

                <!-- Delete Form (hidden) -->
                @if($group->isOwner(Auth::user()))
                    <form id="deleteForm" action="{{ route('groups.destroy', $group) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this group? This action cannot be undone and will remove all members.')) {
        document.getElementById('deleteForm').submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const maxMembersInput = document.getElementById('max_members');
    const currentMemberCount = {{ $group->member_count }};
    
    maxMembersInput.addEventListener('input', function() {
        const newLimit = parseInt(this.value);
        if (newLimit < currentMemberCount && newLimit >= 2) {
            // Show warning if trying to set limit below current member count
            console.log('Warning: New limit is below current member count');
        }
    });
});
</script>
@endpush
@endsection
