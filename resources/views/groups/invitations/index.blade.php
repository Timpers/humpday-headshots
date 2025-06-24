@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        My Group Invitations
    </h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            My Group Invitations
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Manage your group invitations
                        </p>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('groups.my-invitations', ['status' => 'all']) }}" 
                           class="px-3 py-2 text-sm rounded-md {{ $status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }} transition-colors">
                            All
                        </a>
                        <a href="{{ route('groups.my-invitations', ['status' => 'pending']) }}" 
                           class="px-3 py-2 text-sm rounded-md {{ $status === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }} transition-colors">
                            Pending
                        </a>
                        <a href="{{ route('groups.my-invitations', ['status' => 'accepted']) }}" 
                           class="px-3 py-2 text-sm rounded-md {{ $status === 'accepted' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }} transition-colors">
                            Accepted
                        </a>
                        <a href="{{ route('groups.my-invitations', ['status' => 'declined']) }}" 
                           class="px-3 py-2 text-sm rounded-md {{ $status === 'declined' ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }} transition-colors">
                            Declined
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if($invitations->count() > 0)
                    <!-- Bulk Actions for Pending Invitations -->
                    @if($status === 'pending' || $status === 'all')
                        @php
                            $pendingInvitations = $invitations->where('status', 'pending');
                        @endphp
                        
                        @if($pendingInvitations->count() > 0)
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    Bulk Actions
                                </h4>
                                <form method="POST" action="{{ route('group-invitations.bulk-action') }}" id="bulkActionForm">
                                    @csrf
                                    <div class="flex flex-wrap items-center gap-3">
                                        <button type="submit" name="action" value="accept" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 transition-colors" onclick="return confirmBulkAction('accept')">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Accept Selected
                                        </button>
                                        <button type="submit" name="action" value="decline" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 transition-colors" onclick="return confirmBulkAction('decline')">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Decline Selected
                                        </button>
                                        <span class="text-gray-400">|</span>
                                        <button type="button" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors" onclick="selectAllPending()">
                                            Select All Pending
                                        </button>
                                        <button type="button" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors" onclick="deselectAll()">
                                            Deselect All
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endif

                    <!-- Invitations Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($invitations as $invitation)
                            <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md hover:shadow-lg transition-shadow {{ $invitation->status === 'pending' ? 'border-2 border-yellow-300 dark:border-yellow-600' : 'border border-gray-200 dark:border-gray-600' }}">
                                <!-- Card Header -->
                                <div class="p-4 border-b border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-start">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {{ $invitation->group->name }}
                                        </h4>
                                        <div class="flex items-center gap-2 ml-2">
                                            @if($invitation->status === 'pending')
                                                <input type="checkbox" name="invitation_ids[]" value="{{ $invitation->id }}" 
                                                       form="bulkActionForm" class="pending-invitation rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            @endif
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $invitation->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : ($invitation->status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300') }}">
                                                {{ ucfirst($invitation->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="p-4">
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                                        {{ Str::limit($invitation->group->description, 80) }}
                                    </p>
                                    
                                    <div class="grid grid-cols-2 gap-4 mb-4 text-center">
                                        <div class="bg-gray-50 dark:bg-gray-600 rounded-lg p-2">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Game</div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                                {{ $invitation->group->game ?? 'Any' }}
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-600 rounded-lg p-2">
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Members</div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                                {{ $invitation->group->members_count ?? $invitation->group->members()->count() }}/{{ $invitation->group->max_members ?? '∞' }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2 mb-4">
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Invited by:</span>
                                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                                {{ $invitation->invitedBy->name }}
                                            </div>
                                        </div>

                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $invitation->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        @if($invitation->message)
                                            <div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Message:</span>
                                                <div class="text-sm text-gray-700 dark:text-gray-300 italic">
                                                    "{{ Str::limit($invitation->message, 60) }}"
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-600 rounded-b-lg">
                                    <div class="flex justify-between items-center">
                                        <a href="{{ route('group-invitations.show', $invitation) }}" class="inline-flex items-center px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </a>

                                        @if($invitation->status === 'pending')
                                            <div class="flex gap-2">
                                                <form method="POST" action="{{ route('group-invitations.accept', $invitation) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center p-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:ring-2 focus:ring-green-500 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('group-invitations.decline', $invitation) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center p-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 transition-colors" 
                                                            onclick="return confirm('Decline this invitation?')">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif($invitation->status === 'accepted')
                                            <a href="{{ route('groups.show', $invitation->group) }}" class="inline-flex items-center px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                View Group
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $invitations->appends(request()->query())->links() }}
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <div class="mx-auto h-24 w-24 text-gray-400 mb-4">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                            No Invitations Found
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            @if($status === 'pending')
                                You don't have any pending group invitations.
                            @elseif($status === 'accepted')
                                You haven't accepted any group invitations yet.
                            @elseif($status === 'declined')
                                You haven't declined any group invitations.
                            @else
                                You haven't received any group invitations yet.
                            @endif
                        </p>
                        <a href="{{ route('groups.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Browse Groups
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function selectAllPending() {
    document.querySelectorAll('.pending-invitation').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAll() {
    document.querySelectorAll('.pending-invitation').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function confirmBulkAction(action) {
    const selectedInvitations = document.querySelectorAll('.pending-invitation:checked');
    if (selectedInvitations.length === 0) {
        alert('Please select at least one invitation.');
        return false;
    }
    
    const actionText = action === 'accept' ? 'accept' : 'decline';
    return confirm(`Are you sure you want to ${actionText} ${selectedInvitations.length} invitation(s)?`);
}
</script>
@endsection
