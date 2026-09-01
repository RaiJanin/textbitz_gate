import { usePage } from '@inertiajs/vue3'
import { requestNotificationPermission } from '@/Composables/useLocalNotifications'

/**
 * First-run notification permission for the mobile app.
 *
 * Firebase-free: this asks for the Web Notifications permission, which surfaces
 * the OS's own permission modal inside the web view. Runs once — the decision
 * is remembered in localStorage. No-op on web/desktop.
 */

const STORE_KEY = 'gate.notify.prime' // 'granted' | 'denied' | 'unsupported'

function stored() {
    try {
        return localStorage.getItem(STORE_KEY)
    } catch {
        return null
    }
}

function remember(value) {
    try {
        localStorage.setItem(STORE_KEY, value)
    } catch {
        /* ignore */
    }
}

export async function primePushNotifications() {
    const platform = usePage().props?.platform
    if (!platform?.isAndroid && !platform?.isIos) {
        return
    }

    if (['granted', 'denied', 'unsupported'].includes(stored())) {
        return
    }

    // Let the first screen settle before the OS modal interrupts.
    setTimeout(async () => {
        const result = await requestNotificationPermission()
        // Persist any terminal outcome so we don't re-prompt on every launch.
        if (['granted', 'denied', 'unsupported'].includes(result)) {
            remember(result)
        }
    }, 1200)
}
