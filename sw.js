// sw.js - Service Worker for JAJR Company Attendance & Audit Dashboard
// Handles web push notifications for Super Admin alerts

const CACHE_NAME = 'jajr-push-v1';
const DASHBOARD_URL = '/employee/dashboard.php';

// Install event - cache essential resources
self.addEventListener('install', (event) => {
    console.log('[SW] Service Worker installing...');
    self.skipWaiting();
});

// Activate event - claim clients immediately
self.addEventListener('activate', (event) => {
    console.log('[SW] Service Worker activated');
    event.waitUntil(self.clients.claim());
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push event received:', event);

    let notificationData = {
        title: 'JAJR Company Notification',
        body: 'You have a new notification',
        icon: '/uploads/profile_images/profile_0_1769993901.png',
        badge: '/uploads/profile_images/profile_0_1769993901.png',
        tag: 'jajr-notification',
        requireInteraction: true,
        data: {
            url: DASHBOARD_URL,
            notificationId: Date.now()
        }
    };

    // Try to parse the push payload
    if (event.data) {
        try {
            const payload = event.data.json();
            notificationData = {
                title: payload.title || notificationData.title,
                body: payload.body || notificationData.body,
                icon: payload.icon || notificationData.icon,
                badge: payload.badge || notificationData.badge,
                tag: payload.tag || notificationData.tag,
                requireInteraction: payload.requireInteraction !== undefined ? payload.requireInteraction : true,
                data: {
                    url: payload.url || DASHBOARD_URL,
                    notificationId: payload.notificationId || Date.now()
                }
            };
        } catch (e) {
            console.error('[SW] Error parsing push payload:', e);
            // Use text data if JSON parsing fails
            notificationData.body = event.data.text() || notificationData.body;
        }
    }

    // Show the notification
    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            requireInteraction: notificationData.requireInteraction,
            data: notificationData.data,
            actions: [
                {
                    action: 'open',
                    title: 'Open Dashboard'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        })
    );
});

// Notification click event - handle user interaction
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event);
    
    event.notification.close();

    const notificationData = event.notification.data;
    let targetUrl = notificationData?.url || DASHBOARD_URL;

    // Handle action buttons
    if (event.action === 'close') {
        // Just close the notification
        return;
    }

    // Default action or 'open' action - focus or open the dashboard
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if dashboard is already open
            for (const client of clientList) {
                if (client.url.includes('/employee/') && 'focus' in client) {
                    return client.focus();
                }
            }
            // Open new window if not already open
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

// Handle push subscription change
self.addEventListener('pushsubscriptionchange', (event) => {
    console.log('[SW] Push subscription changed:', event);
    // The application should handle re-subscription
    // This event fires when the subscription expires or changes
});

// Message event - handle messages from the main thread
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('[SW] Service Worker script loaded');
