@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Send Connection Request') }}
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                {{-- User Info Section --}}
                @if(isset($user))
                    <div class="mb-8">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="w-20 h-20 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-2xl font-medium text-gray-700 dark:text-gray-300">
                                    {{ substr($user->name, 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                                @if($user->gamertags_count > 0)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $user->gamertags_count }} public gamertags</p>
                                @endif
                            </div>
                        </div>

                        {{-- User's Gamertags --}}
                        @if($user->gamertags && $user->gamertags->count() > 0)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Gamertags:</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($user->gamertags as $gamertag)
                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-md p-3">
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
                        @endif
                    </div>
                @endif

                {{-- Connection Request Form --}}
                <form action="{{ route('user-connections.store') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    {{-- Hidden recipient field if user is provided --}}
                    @if(isset($user))
                        <input type="hidden" name="recipient_id" value="{{ $user->id }}">
                    @else
                        {{-- User selection field if no specific user --}}
                        <div>
                            <label for="recipient_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select User to Connect With *
                            </label>
                            <select name="recipient_id" id="recipient_id" required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('recipient_id') border-red-500 @enderror">
                                <option value="">Choose a user...</option>
                                {{-- This would be populated by the controller --}}
                                @if(isset($availableUsers))
                                    @foreach($availableUsers as $availableUser)
                                        <option value="{{ $availableUser->id }}" {{ old('recipient_id') == $availableUser->id ? 'selected' : '' }}>
                                            {{ $availableUser->name }} ({{ $availableUser->email }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('recipient_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Message Field --}}
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Personal Message (Optional)
                        </label>
                        <textarea
                            name="message"
                            id="message"
                            rows="4"
                            maxlength="500"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none @error('message') border-red-500 @enderror"
                            placeholder="@if(isset($user))Hi {{ $user->name }}, I'd like to connect with you...@else Write a personal message to introduce yourself...@endif"
                        >{{ old('message') }}</textarea>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                A personal message helps build trust and increases the chance of acceptance.
                            </p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                <span id="char-count">0</span>/500
                            </span>
                        </div>
                        @error('message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Connection Guidelines --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                    Connection Guidelines
                                </h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Be respectful and genuine in your message</li>
                                        <li>Mention common interests or gaming platforms</li>
                                        <li>Users will be notified of your request</li>
                                        <li>You can cancel pending requests at any time</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex space-x-4 pt-4">
                        <button
                            type="submit"
                            class="flex items-center space-x-2 px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            id="submit-btn"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                            </svg>
                            <span>Send Connection Request</span>
                        </button>
                        
                        <a
                            href="{{ url()->previous() }}"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Quick Actions Card --}}
        <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('social.browse') }}" class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Browse Users</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Find more users to connect with</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('social.requests') }}" class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Pending Requests</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Manage your connection requests</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('social.friends') }}" class="flex items-center space-x-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">My Connections</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400">View your connected users</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for character counter and form validation --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    const submitBtn = document.getElementById('submit-btn');
    const recipientSelect = document.getElementById('recipient_id');

    // Character counter
    if (messageTextarea && charCount) {
        function updateCharCount() {
            const count = messageTextarea.value.length;
            charCount.textContent = count;
            
            // Change color as approaching limit
            if (count > 450) {
                charCount.className = 'text-red-500';
            } else if (count > 400) {
                charCount.className = 'text-yellow-500';
            } else {
                charCount.className = 'text-gray-500 dark:text-gray-400';
            }
        }

        messageTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count for old value
    }

    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Check recipient selection if field exists
        if (recipientSelect && !recipientSelect.value) {
            isValid = false;
        }
        
        // Enable/disable submit button
        if (submitBtn) {
            submitBtn.disabled = !isValid;
        }
    }

    if (recipientSelect) {
        recipientSelect.addEventListener('change', validateForm);
        validateForm(); // Initial validation
    }

    // Prevent double submission
    if (submitBtn) {
        submitBtn.closest('form').addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending...
            `;
        });
    }
});
</script>
@endpush
@endsection
