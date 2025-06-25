{{-- Connection Request Form Partial --}}
@props(['user', 'connectionStatus' => null])

<div class="connection-form-container">
    @if($connectionStatus === 'connected')
        {{-- Already Connected --}}
        <div class="flex items-center space-x-2 text-green-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium">Connected</span>
        </div>
    @elseif($connectionStatus === 'pending')
        {{-- Request Pending --}}
        <div class="flex items-center space-x-2 text-yellow-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium">Request Pending</span>
        </div>
    @elseif($connectionStatus === 'blocked')
        {{-- Blocked --}}
        <div class="flex items-center space-x-2 text-red-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium">Blocked</span>
        </div>
    @else
        {{-- Send Connection Request --}}
        <form action="{{ route('user-connections.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="recipient_id" value="{{ $user->id }}">
            
            <div>
                <label for="message-{{ $user->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                    Message (Optional)
                </label>
                <textarea 
                    name="message" 
                    id="message-{{ $user->id }}"
                    rows="3" 
                    maxlength="500"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                    placeholder="Hi {{ $user->name }}, I'd like to connect with you..."
                ></textarea>
                <div class="text-xs text-gray-500 mt-1">
                    <span id="char-count-{{ $user->id }}">0</span>/500 characters
                </div>
            </div>

            <div class="flex space-x-3">
                <button 
                    type="submit" 
                    class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                    </svg>
                    <span>Send Request</span>
                </button>
                
                <button 
                    type="button" 
                    onclick="this.closest('.connection-form-container').querySelector('form').style.display='none'; this.closest('.connection-form-container').querySelector('.connection-quick-actions').style.display='block';"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    Cancel
                </button>
            </div>
        </form>

        {{-- Quick Actions (shown initially) --}}
        <div class="connection-quick-actions">
            <button 
                onclick="this.closest('.connection-form-container').querySelector('form').style.display='block'; this.style.display='none';"
                class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                </svg>
                <span>Connect</span>
            </button>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for message textarea
    const textarea = document.getElementById('message-{{ $user->id }}');
    const charCount = document.getElementById('char-count-{{ $user->id }}');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }

    // Initially hide the form and show quick actions
    const form = document.querySelector('.connection-form-container form');
    const quickActions = document.querySelector('.connection-quick-actions');
    
    if (form && quickActions) {
        form.style.display = 'none';
    }
});
</script>

<style>
.connection-form-container form {
    display: none;
}
</style>
