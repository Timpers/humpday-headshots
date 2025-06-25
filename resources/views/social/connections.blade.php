@extends('layouts.app')

@section('title', 'Manage Connections')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Manage Connections</h1>
            <p class="mt-2 text-gray-600">Connect with other gamers and manage your gaming network.</p>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        @if(session('error'))
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        @endif
                        @if($errors->any())
                            @foreach($errors->all() as $error)
                                <p class="text-sm font-medium text-red-800">{{ $error }}</p>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="border-b border-gray-200 mb-8">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('social.friends') }}"
                   class="py-2 px-1 border-b-2 {{ request()->routeIs('social.friends') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap font-medium text-sm">
                    My Connections
                </a>
                <a href="{{ route('social.requests') }}"
                   class="py-2 px-1 border-b-2 {{ request()->routeIs('social.requests') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap font-medium text-sm">
                    Requests
                    @if(isset($pendingRequestsCount) && $pendingRequestsCount > 0)
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            {{ $pendingRequestsCount }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('social.browse') }}"
                   class="py-2 px-1 border-b-2 {{ request()->routeIs('social.browse') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap font-medium text-sm">
                    Find Gamers
                </a>
            </nav>
        </div>

        {{-- Content Area --}}
        <div class="bg-white shadow-sm rounded-lg">
            @yield('connection-content')
        </div>
    </div>
</div>

{{-- Connection Actions Modal --}}
<div id="connectionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 8.5c-.77.833-.23 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-5 text-center" id="modalTitle">Confirm Action</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 text-center" id="modalMessage">
                    Are you sure you want to perform this action?
                </p>
            </div>
            <div class="flex items-center px-4 py-3 space-x-3">
                <button id="modalConfirm" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Confirm
                </button>
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openModal(title, message, confirmAction) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('modalConfirm').onclick = confirmAction;
    document.getElementById('connectionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('connectionModal').classList.add('hidden');
}

// Connection action handlers
function blockConnection(connectionId, userName) {
    openModal(
        'Block Connection',
        `Are you sure you want to block ${userName}? This will prevent them from sending you connection requests.`,
        function() {
            document.getElementById(`block-form-${connectionId}`).submit();
        }
    );
}

function removeConnection(connectionId, userName) {
    openModal(
        'Remove Connection',
        `Are you sure you want to remove your connection with ${userName}?`,
        function() {
            document.getElementById(`remove-form-${connectionId}`).submit();
        }
    );
}

function cancelRequest(connectionId, userName) {
    openModal(
        'Cancel Request',
        `Are you sure you want to cancel your connection request to ${userName}?`,
        function() {
            document.getElementById(`cancel-form-${connectionId}`).submit();
        }
    );
}

// Close modal when clicking outside
document.getElementById('connectionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Escape key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
@endsection
