// Service Worker for Push Notifications
const CACHE_NAME = 'humpday-headshots-v1';

// Install event
self.addEventListener('install', event => {
    console.log('Service Worker installing');
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', event => {
    console.log('Service Worker activating');
    event.waitUntil(self.clients.claim());
});

// Push event
self.addEventListener('push', event => {
    console.log('Push message received:', event);
    
    let data = {};
    
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'New Notification', body: 'You have a new notification' };
    }
    
    const options = {
        body: data.body || 'You have a new notification',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: data.tag || 'humpday-headshots-notification',
        data: data.url || '#',
        requireInteraction: false,
        silent: false,
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/favicon.ico'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'New Notification', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    if (event.action === 'dismiss') {
        return;
    }
    
    const url = event.notification.data || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Check if there's already a window/tab open with the target URL
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // If no existing window/tab, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});
