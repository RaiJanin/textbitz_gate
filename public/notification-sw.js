/*
 * Minimal service worker for TextBitz Gate local notifications.
 *
 * There is NO push server (Firebase-free). Notifications are created by the app
 * itself from data it already has — realtime Reverb events and the recurring
 * PullTapsFromServer sync — via registration.showNotification(). This worker
 * only exists so those notifications can be shown while the web view is
 * backgrounded, and to handle taps on them.
 */

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/home';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.postMessage({ type: 'notification-click', url });
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
            return undefined;
        }),
    );
});
