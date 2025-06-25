@extends('layouts.app')

@section('title', 'Messages - ' . $session->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Session Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6 p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $session->title }}</h1>
                    <p class="text-gray-600 mb-4">{{ $session->description }}</p>
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ $session->scheduled_at->format('M j, Y g:i A') }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.828m3 2.828V9a2 2 0 012-2h1a2 2 0 012 2v8.5M9 16a1 1 0 001-1v-4.5"></path>
                            </svg>
                            {{ $session->participantUsers()->count() + 1 }} / {{ $session->max_participants ?? '∞' }} participants
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('gaming-sessions.show', $session) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                        Back to Session
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="border-b border-gray-200 p-4">
                <h2 class="text-lg font-semibold text-gray-900">Session Messages</h2>
                <p class="text-sm text-gray-600 mt-1">Chat with other participants about this session</p>
            </div>

            <!-- Messages Display -->
            <div id="messages-container" class="h-96 overflow-y-auto p-4 space-y-4">
                @forelse($messages as $message)
                    @include('gaming-sessions.partials.message', ['message' => $message])
                @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-gray-500">No messages yet. Start the conversation!</p>
                    </div>
                @endforelse
            </div>

            <!-- Message Input Form -->
            @can('postMessage', $session)
            <div class="border-t border-gray-200 p-4">
                <form id="message-form" class="flex space-x-3">
                    @csrf
                    <div class="flex-1">
                        <label for="message" class="sr-only">Message</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            rows="2" 
                            class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder="Type your message..."
                            maxlength="1000"
                            required
                        ></textarea>
                        <div class="flex justify-between items-center mt-2">
                            <span id="char-count" class="text-xs text-gray-500">0/1000</span>
                            <button 
                                type="submit" 
                                id="send-button"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                    Send
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endcan
        </div>

        <!-- Pagination -->
        @if($messages->hasPages())
        <div class="mt-6">
            {{ $messages->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    const sendButton = document.getElementById('send-button');
    const messagesContainer = document.getElementById('messages-container');
    
    // Character counter
    messageInput.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = `${length}/1000`;
        charCount.className = length > 900 ? 'text-xs text-red-500' : 'text-xs text-gray-500';
    });

    // Handle form submission
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            sendButton.disabled = true;
            sendButton.innerHTML = '<span class="flex items-center"><svg class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Sending...</span>';
            
            fetch('{{ route("gaming-sessions.messages.store", $session) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear form
                    messageInput.value = '';
                    charCount.textContent = '0/1000';
                    charCount.className = 'text-xs text-gray-500';
                    
                    // Add new message to container
                    const emptyState = messagesContainer.querySelector('.text-center');
                    if (emptyState) {
                        emptyState.remove();
                    }
                    
                    messagesContainer.insertAdjacentHTML('beforeend', data.html);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                } else {
                    alert('Failed to send message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                sendButton.disabled = false;
                sendButton.innerHTML = '<span class="flex items-center"><svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>Send</span>';
            });
        });
    }

    // Auto-scroll to bottom on load
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    // Optional: Auto-refresh messages every 10 seconds
    setInterval(function() {
        const lastMessage = messagesContainer.querySelector('.message-item:last-child');
        const since = lastMessage ? lastMessage.dataset.timestamp : null;
        
        let url = '{{ route("gaming-sessions.messages.recent", $session) }}';
        if (since) {
            url += '?since=' + encodeURIComponent(since);
        }
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                data.html.forEach(html => {
                    messagesContainer.insertAdjacentHTML('beforeend', html);
                });
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        })
        .catch(error => console.error('Error fetching new messages:', error));
    }, 10000);
});
</script>
@endpush
@endsection
