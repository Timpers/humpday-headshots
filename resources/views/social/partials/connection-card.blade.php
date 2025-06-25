{{-- Connection Request Card Component --}}
@props(['connection', 'type' => 'received'])

@php
    $user = $type === 'received' ? $connection->requester : $connection->recipient;
    $isReceived = $type === 'received';
    $isPending = $connection->status === 'pending';
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between">
        {{-- User Info --}}
        <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 bg-gray-300 rounded-full flex items-center justify-center">
                    <svg class="h-6 w-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-lg font-medium text-gray-900 truncate">
                    {{ $user->name }}
                </p>
                <p class="text-sm text-gray-500">
                    {{ $user->email }}
                </p>

                {{-- Connection Status --}}
                <div class="mt-2">
                    @if($connection->status === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            Pending
                        </span>
                    @elseif($connection->status === 'accepted')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Connected
                        </span>
                    @elseif($connection->status === 'declined')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Declined
                        </span>
                    @elseif($connection->status === 'blocked')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                            </svg>
                            Blocked
                        </span>
                    @endif
                </div>

                {{-- Request Date --}}
                <p class="text-xs text-gray-400 mt-1">
                    {{ $isReceived ? 'Received' : 'Sent' }} {{ $connection->created_at->diffForHumans() }}
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col space-y-2 ml-4">
            @if($isPending && $isReceived)
                {{-- Accept/Decline buttons for received requests --}}
                <form action="{{ route('user-connections.accept', $connection) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Accept
                    </button>
                </form>

                <form action="{{ route('user-connections.decline', $connection) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Decline
                    </button>
                </form>
            @elseif($isPending && !$isReceived)
                {{-- Cancel button for sent requests --}}
                <form id="cancel-form-{{ $connection->id }}" action="{{ route('user-connections.cancel', $connection) }}" method="POST" class="hidden">
                    @csrf
                </form>
                <button onclick="cancelRequest({{ $connection->id }}, '{{ $user->name }}')" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Cancel
                </button>
            @elseif($connection->status === 'accepted')
                {{-- Remove/Block options for accepted connections --}}
                <div class="relative inline-block text-left">
                    <button type="button" onclick="toggleDropdown({{ $connection->id }})" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                        </svg>
                    </button>

                    <div id="dropdown-{{ $connection->id }}" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                        <div class="py-1">
                            <form id="remove-form-{{ $connection->id }}" action="{{ route('user-connections.destroy', $connection) }}" method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button onclick="removeConnection({{ $connection->id }}, '{{ $user->name }}')" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Remove Connection
                            </button>

                            <form id="block-form-{{ $connection->id }}" action="{{ route('user-connections.block', $connection) }}" method="POST" class="hidden">
                                @csrf
                            </form>
                            <button onclick="blockConnection({{ $connection->id }}, '{{ $user->name }}')" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                Block User
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Message --}}
    @if($connection->message)
        <div class="mt-4 p-3 bg-gray-50 rounded-md">
            <p class="text-sm text-gray-700 italic">"{{ $connection->message }}"</p>
        </div>
    @endif

    {{-- User's Gamertags --}}
    @if($user->gamertags && $user->gamertags->count() > 0)
        <div class="mt-4">
            <p class="text-xs font-medium text-gray-500 mb-2">Gaming Platforms:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($user->gamertags->where('is_public', true)->take(3) as $gamertag)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $gamertag->platform_name }}: {{ $gamertag->gamertag }}
                    </span>
                @endforeach
                @if($user->gamertags->where('is_public', true)->count() > 3)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        +{{ $user->gamertags->where('is_public', true)->count() - 3 }} more
                    </span>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function toggleDropdown(connectionId) {
    const dropdown = document.getElementById(`dropdown-${connectionId}`);
    const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');

    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `dropdown-${connectionId}`) {
            d.classList.add('hidden');
        }
    });

    // Toggle current dropdown
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="dropdown-"]') && !e.target.closest('button[onclick^="toggleDropdown"]')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(d => {
            d.classList.add('hidden');
        });
    }
});
</script>
@endpush
