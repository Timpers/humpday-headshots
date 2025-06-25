<div class="message-item flex space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors" data-timestamp="{{ $message->created_at->toISOString() }}">
    <!-- User Avatar -->
    <div class="flex-shrink-0">
        <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
            {{ strtoupper(substr($message->user->name, 0, 1)) }}
        </div>
    </div>
    
    <!-- Message Content -->
    <div class="flex-1 min-w-0">
        <div class="flex items-baseline space-x-2">
            <h4 class="text-sm font-semibold text-gray-900">{{ $message->user->name }}</h4>
            <time class="text-xs text-gray-500" datetime="{{ $message->created_at->toISOString() }}">
                {{ $message->created_at->diffForHumans() }}
            </time>
            @if($message->is_edited)
                <span class="text-xs text-gray-400">(edited)</span>
            @endif
            @if($message->type !== 'text')
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                    {{ $message->type === 'announcement' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($message->type) }}
                </span>
            @endif
        </div>
        
        <div class="mt-1">
            <p class="text-sm text-gray-700 break-words message-content" data-message-id="{{ $message->id }}">
                {{ $message->message }}
            </p>
        </div>
        
        <!-- Message Actions -->
        @auth
        <div class="flex items-center space-x-3 mt-2 text-xs">
            @can('update', $message)
                <button onclick="editMessage({{ $message->id }})" class="text-blue-600 hover:text-blue-800 transition-colors">
                    Edit
                </button>
            @endcan
            
            @can('delete', $message)
                <button onclick="deleteMessage({{ $message->id }})" class="text-red-600 hover:text-red-800 transition-colors">
                    Delete
                </button>
            @endcan
        </div>
        @endauth
    </div>
</div>

@push('scripts')
<script>
// Edit message function
function editMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    const currentText = messageElement.textContent.trim();
    
    // Create edit form
    const editForm = document.createElement('div');
    editForm.innerHTML = `
        <div class="edit-form mt-2">
            <textarea class="w-full text-sm border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" rows="2" maxlength="1000">${currentText}</textarea>
            <div class="flex justify-end space-x-2 mt-2">
                <button onclick="cancelEdit(${messageId})" class="text-gray-600 hover:text-gray-800 text-xs px-2 py-1">Cancel</button>
                <button onclick="saveEdit(${messageId})" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded">Save</button>
            </div>
        </div>
    `;
    
    messageElement.style.display = 'none';
    messageElement.parentNode.appendChild(editForm);
}

// Cancel edit
function cancelEdit(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    const editForm = messageElement.parentNode.querySelector('.edit-form');
    
    messageElement.style.display = 'block';
    editForm.remove();
}

// Save edit
function saveEdit(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    const editForm = messageElement.parentNode.querySelector('.edit-form');
    const textarea = editForm.querySelector('textarea');
    const newText = textarea.value.trim();
    
    if (!newText) {
        alert('Message cannot be empty');
        return;
    }
    
    const sessionId = '{{ $message->gaming_session_id }}';
    
    fetch(`/gaming-sessions/${sessionId}/messages/${messageId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            message: newText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageElement.textContent = newText;
            messageElement.style.display = 'block';
            editForm.remove();
            
            // Add edited indicator if not already present
            const messageContainer = messageElement.closest('.message-item');
            const timeElement = messageContainer.querySelector('time');
            if (!timeElement.nextElementSibling || !timeElement.nextElementSibling.textContent.includes('edited')) {
                const editedSpan = document.createElement('span');
                editedSpan.className = 'text-xs text-gray-400';
                editedSpan.textContent = '(edited)';
                timeElement.insertAdjacentElement('afterend', editedSpan);
            }
        } else {
            alert('Failed to update message');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update message');
    });
}

// Delete message
function deleteMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) {
        return;
    }
    
    const sessionId = '{{ $message->gaming_session_id }}';
    
    fetch(`/gaming-sessions/${sessionId}/messages/${messageId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const messageElement = document.querySelector(`[data-message-id="${messageId}"]`).closest('.message-item');
            messageElement.remove();
        } else {
            alert('Failed to delete message');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete message');
    });
}
</script>
@endpush
