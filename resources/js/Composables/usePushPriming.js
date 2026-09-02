import { usePage } from '@inertiajs/vue3'
import { requestNotificationPermission } from '@/Composables/useLocalNotifications'

/**
 * First-run notification permission (mobile only, runs once — remembered in
 * localStorage).
 *
 * Primary: FCM via `fatlum/nativephp-push` — `pushNotifications.enroll()`
 * shows the real OS permission modal and hooks token delivery.
 * Fallback: the Web Notifications permission, used by useLocalNotifications
 * when the plugin isn't present.
 */

const STORE_KEY = 'gate.notify.prime' // 'granted' | 'denied' | 'unsupported' | 'asked'
const status = (r) => (typeof r === 'string' ? r : (r?.status ?? null))

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

async function tryFcmEnrol() {
    let native
    try {
        native = await import('#nativephp')
    } catch {
        return false
    }

    const push = native.pushNotifications
    if (!push?.enroll || !push?.checkPermission) {
        return false // plugin absent
    }

    try {
        const current = status(await push.checkPermission())
        if (current === 'granted' || current === 'denied') {
            remember(current)
            return true
        }

        push.enroll() // native OS permission modal
        remember('asked')

        setTimeout(async () => {
            try {
                const after = status(await push.checkPermission())
                if (['granted', 'denied'].includes(after)) remember(after)
            } catch {
                /* leave as 'asked' */
            }
        }, 2000)

        return true
    } catch {
        return false
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

    setTimeout(async () => {
        if (await tryFcmEnrol()) {
            return
        }
        // Fallback: Web Notifications permission (drives useLocalNotifications).
        const result = await requestNotificationPermission()
        if (['granted', 'denied', 'unsupported'].includes(result)) {
            remember(result)
        }
    }, 1200)
}
