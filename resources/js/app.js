import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { vLongPress } from './directives/longPress';
import '@fortawesome/fontawesome-free/css/all.min.css';
import { subscribeToLinkedStudents } from './services/useTapChannelManager';
import { startSyncNotifier } from './Composables/useSyncNotifier';
import { initLocalNotifications } from './Composables/useLocalNotifications';
import { startPushNotifications } from './Composables/usePushNotifications';
import { primePushNotifications } from './Composables/usePushPriming';
import { startAppLifecycle } from './Composables/useAppLifecycle';
import { useEchoDebug } from './Composables/useEchoDebug';
import { useWorkerDebug } from './Composables/useWorkerDebug';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .directive('long-press', vLongPress)

        app.mount(el);

        useEchoDebug();           // start capturing Reverb connection state immediately
        useWorkerDebug();         // start polling scheduler / queue status
        initLocalNotifications();   // fallback delivery layer (Web Notifications / native toast)
        startPushNotifications();   // primary: FCM device-token registration (mobile only)
        subscribeToLinkedStudents();
        startSyncNotifier();
        startAppLifecycle();       // re-sync on foreground / flush on background (mobile lifecycle plugin)
        primePushNotifications();   // first-run: OS notification permission — FCM enrol, else Web Notifications

        // Pick up newly-linked students / a page that first exposes the list.
        router.on('navigate', () => subscribeToLinkedStudents());
    },
    progress: false,
});
