<!-- Mini Messages Widget -->
@can('viewMessages', $session)
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Messages</h3>
        <a href="{{ route('gaming-sessions.messages.index', $session) }}" 
           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            View All
        </a>
    </div>
    
    @php
        $recentMessages = $session->messages()->limit(3)->get();
    @endphp
    
    @if($recentMessages->count() > 0)
        <div class="space-y-3">
            @foreach($recentMessages as $message)
                <div class="flex space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-6 h-6 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xs font-medium">
                            {{ strtoupper(substr($message->user->name, 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline space-x-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $message->user->name }}</span>
                            <time class="text-xs text-gray-500">{{ $message->created_at->diffForHumans() }}</time>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $message->message }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        
        @can('postMessage', $session)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <form class="quick-message-form" data-session-id="{{ $session->id }}">
                @csrf
                <div class="flex space-x-2">
                    <input 
                        type="text" 
                        name="message" 
                        placeholder="Quick message..." 
                        class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        maxlength="1000"
                        required
                    >
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                    >
                        Send
                    </button>
                </div>
            </form>
        </div>
        @endcan
    @else
        <div class="text-center py-4">
            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <p class="text-gray-500 text-sm mb-3">No messages yet</p>
            
            @can('postMessage', $session)
            <form class="quick-message-form" data-session-id="{{ $session->id }}">
                @csrf
                <div class="flex space-x-2">
                    <input 
                        type="text" 
                        name="message" 
                        placeholder="Start the conversation..." 
                        class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        maxlength="1000"
                        required
                    >
                    <button 
                        type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                    >
                        Send
                    </button>
                </div>
            </form>
            @endcan
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle quick message forms
    document.querySelectorAll('.quick-message-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const sessionId = this.dataset.sessionId;
            const messageInput = this.querySelector('input[name="message"]');
            const submitBtn = this.querySelector('button[type="submit"]');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            fetch(`/gaming-sessions/${sessionId}/messages`, {
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
                    messageInput.value = '';
                    // Optionally refresh the messages section or redirect to full messages page
                    window.location.reload();
                } else {
                    alert('Failed to send message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    });
});
</script>
@endpush
@endcan
