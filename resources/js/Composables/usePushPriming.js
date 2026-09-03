import { usePage } from '@inertiajs/vue3'
import { requestNotificationPermission } from '@/Composables/useLocalNotifications'

/**
 * First-run notification opt-in.
 *
 * The user is shown a Vue explainer modal (NotificationOptInModal) first; only
 * when they tap "Enable" does `runNotificationOptIn()` fire the *native* prompt:
 *
 *   Primary: FCM via `fatlum/nativephp-push` — `pushNotifications.enroll()`
 *            shows the real OS permission sheet and hooks token delivery.
 *   Fallback: the Web Notifications permission (drives useLocalNotifications)
 *            when the plugin isn't in the build.
 *
 * The decision is remembered in localStorage so the modal only appears once.
 */

const STORE_KEY = 'gate.notify.optin' // 'granted' | 'denied' | 'dismissed' | 'unsupported'
const RESOLVED = ['granted', 'denied', 'unsupported']

// Only auto-offer once per app session even while the choice is still pending.
let offeredThisSession = false

const asStatus = (r) => (typeof r === 'string' ? r : (r?.status ?? r?.token ?? null))

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

function isMobile() {
    const p = usePage().props?.platform
    return !!(p?.isAndroid || p?.isIos)
}

async function loadPush() {
    try {
        const native = await import('../../../vendor/nativephp/mobile/resources/jump/dist/native')
        return native?.pushNotifications ?? null
    } catch {
        return null
    }
}

/**
 * Should the explainer modal be shown now? True only on mobile, when we've never
 * recorded a decision, and the OS itself hasn't been asked yet.
 */
export async function shouldOfferNotificationOptIn() {
    if (!isMobile() || offeredThisSession) {
        return false
    }

    const saved = stored()
    if (saved && (RESOLVED.includes(saved) || saved === 'dismissed')) {
        return false
    }

    // Sync from the platform in case permission was set outside the app.
    const push = await loadPush()
    if (push?.checkPermission) {
        const os = asStatus(await push.checkPermission())
        if (os === 'granted' || os === 'denied') {
            remember(os)
            return false
        }
        offeredThisSession = true
        return true // 'not_determined' / 'provisional' / etc.
    }

    // No plugin — fall back to the Web Notifications permission.
    if (typeof Notification === 'undefined') {
        remember('unsupported')
        return false
    }
    if (Notification.permission !== 'default') {
        remember(Notification.permission)
        return false
    }

    offeredThisSession = true
    return true
}

/**
 * Fire the native permission prompt (or the Web fallback) and record the result.
 * Call this from the modal's "Enable" button.
 *
 * @returns {Promise<'granted'|'denied'|'asked'|'unsupported'>}
 */
export async function runNotificationOptIn() {
    const push = await loadPush()

    if (push?.enroll && push?.checkPermission) {
        try {
            const current = asStatus(await push.checkPermission())
            if (current === 'granted' || current === 'denied') {
                remember(current)
                return current
            }

            push.enroll() // <-- the real OS permission sheet

            // The sheet resolves asynchronously; re-read shortly after.
            await new Promise((resolve) => setTimeout(resolve, 1500))
            const after = asStatus(await push.checkPermission())
            if (RESOLVED.includes(after)) {
                remember(after)
                return after
            }

            remember('asked')
            return 'asked'
        } catch {
            /* fall through to the web path */
        }
    }

    const result = await requestNotificationPermission()
    remember(RESOLVED.includes(result) ? result : 'asked')
    return result
}

/** User tapped "Not now" — don't auto-show the modal again (Settings can re-ask). */
export function dismissNotificationOptIn() {
    remember('dismissed')
}

/**
 * Current opt-in state for UI (e.g. a Settings "Enable notifications" affordance):
 * 'granted' | 'denied' | 'dismissed' | 'unsupported' | 'unasked'.
 */
export async function notificationOptInState() {
    const push = await loadPush()
    if (push?.checkPermission) {
        const os = asStatus(await push.checkPermission())
        if (os === 'granted' || os === 'denied') return os
    } else if (typeof Notification !== 'undefined' && Notification.permission !== 'default') {
        return Notification.permission
    } else if (typeof Notification === 'undefined' && !isMobile()) {
        return 'unsupported'
    }

    const saved = stored()
    return saved === 'dismissed' ? 'dismissed' : 'unasked'
}
