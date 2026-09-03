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

async function loadNative() {
    try {
        return await import('#nativephp')
    } catch {
        return null
    }
}

async function loadPush() {
    return (await loadNative())?.pushNotifications ?? null
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

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
 * The one entry point for asking the user to enable push notifications, with a
 * strict fallback chain:
 *
 *   1. Native permission prompt — `pushNotifications.enroll()`.
 *   2. If the native layer can't be loaded, OR the prompt didn't yield "granted"
 *      → open this app's OS settings screen (`System.OpenAppSettings`).
 *   3. If opening settings also fails
 *      → a dialog note telling the user to open the app settings by hand.
 *
 * On web (no native runtime) it uses the Web Notifications permission and stops.
 * The outcome is remembered in localStorage.
 *
 * @returns {Promise<'granted'|'denied'|'settings-opened'|'manual'|'unsupported'>}
 */
export async function runNotificationOptIn() {
    // Web: no app-settings screen — best-effort Web Notifications and done.
    if (!isMobile()) {
        const web = await requestNotificationPermission()
        remember(RESOLVED.includes(web) ? web : 'asked')
        return RESOLVED.includes(web) ? web : 'asked'
    }

    // --- 1. native permission prompt ---
    const push = await loadPush()
    let nativeOk = false

    if (push?.enroll && push?.checkPermission) {
        try {
            let status = asStatus(await push.checkPermission())

            if (status !== 'granted' && status !== 'denied') {
                push.enroll() // the real OS permission sheet
                await wait(1500) // it resolves asynchronously
                status = asStatus(await push.checkPermission())
            }

            nativeOk = true

            if (status === 'granted') {
                remember('granted')
                return 'granted'
            }
            if (status === 'denied') {
                remember('denied')
            }
        } catch {
            /* native prompt errored — treat as "failed to load" */
        }
    }

    // --- 2 + 3. open app settings, then a manual-steps dialog if that fails ---
    if (!nativeOk) {
        // native layer never came up — note that so the UI stops offering it
        remember('denied')
    }

    const outcome = await openAppNotificationSettings()

    return outcome === 'opened' ? 'settings-opened' : 'manual'
}

/** User tapped "Not now" — don't auto-show the modal again (Settings can re-ask). */
export function dismissNotificationOptIn() {
    remember('dismissed')
}

/**
 * Steps 2 + 3 of the fallback chain (also reachable directly from a Settings
 * "Open settings" button):
 *   1. a native guidance dialog,
 *   2. open this app's OS settings screen (`System.OpenAppSettings`),
 *   3. if that call fails, a dialog with the manual steps.
 *
 * @returns {Promise<'opened'|'manual'|'unavailable'>}
 */
export async function openAppNotificationSettings() {
    if (!isMobile()) {
        return 'unavailable'
    }

    const native = await loadNative()

    if (!native) {
        return 'unavailable'
    }

    const say = async (title, message) => {
        try {
            await native.dialog?.alert?.(title, message, ['OK'], 'gate-notify-settings')
        } catch {
            /* dialog is best-effort */
        }
    }

    await say(
        'Turn on notifications',
        "We can't set up notifications for TextBitz Gate, so we can't alert you when your child taps in or out."
        + "\n\nTap OK to open this app's settings, switch Notifications on, then come back.",
    )

    try {
        await native.bridgeCall('System.OpenAppSettings', {})
        return 'opened'
    } catch {
        await say(
            'Open Settings manually',
            "We couldn't open Settings for you."
            + "\n\nOn your phone: open Settings → Apps → TextBitz Gate → Notifications, and turn them on.",
        )
        return 'manual'
    }
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
