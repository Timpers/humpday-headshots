<!-- Notifications Dropdown Component -->
<div class="relative" x-data="notificationDropdown()" x-init="init()">
    <!-- Notification Bell -->
    <button 
        @click="toggleDropdown()"
        class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
        :class="{ 'text-blue-600 dark:text-blue-400': hasUnread }"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zm-5-15a7.5 7.5 0 107.5 7.5c0-4.08-3.27-7.5-7.5-7.5z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.73 21a1.999 1.999 0 01-3.46 0M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9z"></path>
        </svg>
        
        <!-- Notification Badge -->
        <span 
            x-show="unreadCount > 0" 
            x-text="unreadCount > 99 ? '99+' : unreadCount"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium"
        ></span>
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="isOpen = false"
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
        style="display: none;"
    >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
                <button 
                    @click="markAllAsRead()"
                    x-show="unreadCount > 0"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    Mark all read
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zm-5-15a7.5 7.5 0 107.5 7.5c0-4.08-3.27-7.5-7.5-7.5z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div 
                    @click="handleNotificationClick(notification)"
                    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                    :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read_at }"
                >
                    <div class="flex items-start space-x-3">
                        <!-- Notification Icon -->
                        <div class="flex-shrink-0 mt-1">
                            <div 
                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                :class="getNotificationIconClass(notification.type)"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" x-html="getNotificationIcon(notification.type)">
                                </svg>
                            </div>
                        </div>

                        <!-- Notification Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1" x-text="notification.created_at"></p>
                        </div>

                        <!-- Unread Indicator -->
                        <div x-show="!notification.read_at" class="flex-shrink-0">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <a href="#" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                View all notifications
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function notificationDropdown() {
    return {
        isOpen: false,
        notifications: [],
        unreadCount: 0,
        hasUnread: false,
        pushSupported: false,
        pushSubscription: null,

        async init() {
            this.loadNotifications();
            this.startPolling();
            await this.initPushNotifications();
        },

        async initPushNotifications() {
            // Check if service workers and push notifications are supported
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                this.pushSupported = true;
                
                try {
                    // Register service worker
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('Service Worker registered:', registration);
                    
                    // Request notification permission
                    await this.requestNotificationPermission();
                    
                    // Subscribe to push notifications
                    await this.subscribeToPush(registration);
                } catch (error) {
                    console.error('Service Worker registration failed:', error);
                }
            } else {
                console.log('Push messaging is not supported');
                // Fallback to browser notifications
                this.requestNotificationPermission();
            }
        },

        async requestNotificationPermission() {
            if ('Notification' in window) {
                const permission = await Notification.requestPermission();
                console.log('Notification permission:', permission);
                return permission === 'granted';
            }
            return false;
        },

        async subscribeToPush(registration) {
            try {
                // For now, skip push subscription and just use browser notifications
                // const subscription = await registration.pushManager.subscribe({
                //     userVisibleOnly: true,
                //     applicationServerKey: this.urlBase64ToUint8Array('your-vapid-public-key') // You'll need to generate VAPID keys
                // });
                
                // this.pushSubscription = subscription;
                // console.log('Push subscription:', subscription);
                
                // Send subscription to server
                // await this.sendSubscriptionToServer(subscription);
                console.log('Using browser notifications instead of push notifications');
            } catch (error) {
                console.error('Failed to subscribe to push notifications:', error);
            }
        },

        async sendSubscriptionToServer(subscription) {
            try {
                await fetch('/notifications/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        subscription: subscription
                    })
                });
            } catch (error) {
                console.error('Failed to send subscription to server:', error);
            }
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.loadNotifications();
            }
        },

        async loadNotifications() {
            try {
                const response = await fetch('/notifications');
                const data = await response.json();
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.hasUnread = this.unreadCount > 0;
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        },

        async markAllAsRead() {
            try {
                await fetch('/notifications/read-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                this.notifications.forEach(n => n.read_at = new Date().toISOString());
                this.unreadCount = 0;
                this.hasUnread = false;
            } catch (error) {
                console.error('Failed to mark notifications as read:', error);
            }
        },

        async markAsRead(notificationId) {
            try {
                await fetch(`/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                    this.hasUnread = this.unreadCount > 0;
                }
            } catch (error) {
                console.error('Failed to mark notification as read:', error);
            }
        },

        handleNotificationClick(notification) {
            if (!notification.read_at) {
                this.markAsRead(notification.id);
            }
            
            this.isOpen = false;
            
            if (notification.url && notification.url !== '#') {
                window.location.href = notification.url;
            }
        },

        startPolling() {
            setInterval(async () => {
                try {
                    const response = await fetch('/notifications/count');
                    const data = await response.json();
                    
                    const oldCount = this.unreadCount;
                    this.unreadCount = data.count;
                    this.hasUnread = this.unreadCount > 0;
                    
                    // If count increased, refresh notifications and show browser notification
                    if (data.count > oldCount) {
                        this.loadNotifications();
                        
                        // Show browser notification only if not using service worker push
                        if (!this.pushSupported || !this.pushSubscription) {
                            this.showBrowserNotification();
                        }
                    }
                } catch (error) {
                    console.error('Failed to check notification count:', error);
                }
            }, 10000); // Check every 10 seconds
        },

        showBrowserNotification() {
            if ('Notification' in window && Notification.permission === 'granted' && this.notifications.length > 0) {
                const latest = this.notifications[0];
                const notification = new Notification(latest.title, {
                    body: latest.message,
                    icon: '/favicon.ico',
                    tag: 'humpday-headshots-notification',
                    data: latest.url || '#'
                });
                
                notification.onclick = function() {
                    window.focus();
                    if (this.data && this.data !== '#') {
                        window.location.href = this.data;
                    }
                    this.close();
                };
                
                // Auto close after 5 seconds
                setTimeout(() => notification.close(), 5000);
            }
        },

        getNotificationIconClass(type) {
            const classes = {
                'gaming_session_message': 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
                'connection_request': 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
                'group_invitation': 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400',
                'gaming_session_invitation': 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-400'
            };
            return classes[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400';
        },

        getNotificationIcon(type) {
            const icons = {
                'gaming_session_message': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>',
                'connection_request': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>',
                'group_invitation': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>',
                'gaming_session_invitation': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
            };
            return icons[type] || '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        }
    }
}
</script>
@endpush
