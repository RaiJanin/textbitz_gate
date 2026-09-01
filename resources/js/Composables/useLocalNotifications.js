import { reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * Firebase-free notifications.
 *
 * There is no push server. Alerts are raised on-device from data the app
 * already receives — realtime Reverb events (useTapChannelManager) and the
 * recurring PullTapsFromServer sync (useSyncNotifier).
 *
 * Delivery, best available first:
 *   1. Web Notifications API (+ service worker)  — real tray notification.
 *   2. Native fallback: Device.vibrate(), and Dialog.alert() for high-priority
 *      items. Used when the Android WebView doesn't expose `Notification`
 *      (the common case in NativePHP builds today).
 *   3. Nothing extra — the caller's own in-app / native toast still fires.
 *
 * `state.mode` reports which tier is active. Background/tray delivery while the
 * app is killed is NOT possible without FCM/APNS or a native local-notification
 * plugin — that's the trade-off for staying Firebase-free.
 */

const state = reactive({
    webApi: typeof Notification !== 'undefined',
    permission: typeof Notification !== 'undefined' ? Notification.permission : 'unsupported',
    swReady: false,
    mode: 'toast-only', // 'web' | 'native-fallback' | 'toast-only'
})

let swRegistration = null
let nativePromise = null
let initialised = false

function isMobile() {
    const p = usePage().props?.platform
    return !!(p?.isAndroid || p?.isIos)
}

function loadNative() {
    if (!nativePromise) {
        nativePromise = import('#nativephp').catch(() => null)
    }
    return nativePromise
}

function refreshMode() {
    if (state.webApi && state.permission === 'granted') {
        state.mode = 'web'
    } else if (isMobile()) {
        state.mode = 'native-fallback'
    } else {
        state.mode = 'toast-only'
    }
}

export async function initLocalNotifications() {
    if (initialised) return
    initialised = true

    if (state.webApi) {
        state.permission = Notification.permission

        if ('serviceWorker' in navigator) {
            try {
                swRegistration = await navigator.serviceWorker.register('/notification-sw.js')
                state.swReady = true
            } catch {
                /* page-level Notification() still works */
            }
            navigator.serviceWorker?.addEventListener('message', (event) => {
                if (event.data?.type === 'notification-click' && event.data.url) {
                    router.visit(event.data.url)
                }
            })
        }
    }

    if (isMobile()) {
        await loadNative()
    }

    refreshMode()
}

/**
 * The OS notification-permission prompt. Only meaningful where the Web
 * Notifications API exists; a silent no-op otherwise.
 */
export async function requestNotificationPermission() {
    if (!state.webApi) return 'unsupported'

    if (Notification.permission === 'default') {
        try {
            state.permission = await Notification.requestPermission()
        } catch {
            state.permission = Notification.permission
        }
    } else {
        state.permission = Notification.permission
    }

    refreshMode()
    return state.permission
}

/** Guardian preference gate: 'arrival' | 'departure' | 'late_alert' | 'weekly_summary'. */
function prefAllows(kind) {
    if (!kind) return true
    const prefs = usePage().props?.gate?.notificationPreferences
    return prefs ? prefs[kind] !== false : true
}

/**
 * Raise a notification through the best available channel. Never toasts — the
 * caller keeps its own toast so there is always feedback and never a double.
 *
 * @param {{title:string, body?:string, tag?:string, url?:string, kind?:string, priority?:'normal'|'high'}} opts
 * @returns {Promise<'web'|'native-fallback'|'none'>}
 */
export async function notify({ title, body = '', tag, url = '/home', kind, priority = 'normal' } = {}) {
    if (!title || !prefAllows(kind)) return 'none'

    // 1. Web Notifications API
    if (state.webApi && Notification.permission === 'granted') {
        const options = { body, tag: tag || 'gate', renotify: !!tag, icon: '/icon.png', badge: '/icon.png', data: { url } }
        try {
            if (swRegistration) {
                await swRegistration.showNotification(title, options)
            } else {
                const n = new Notification(title, options)
                n.onclick = () => { window.focus(); router.visit(url); n.close() }
            }
            return 'web'
        } catch {
            /* fall through */
        }
    }

    // 2. Native fallback (Android WebView with no Notification API)
    if (isMobile()) {
        const native = await loadNative()
        try {
            native?.device?.vibrate?.()
        } catch {
            /* ignore */
        }
        if (priority === 'high') {
            try {
                await native?.dialog?.alert?.(title, body || title, ['OK'], tag || 'gate-alert')
                return 'native-fallback'
            } catch {
                /* ignore */
            }
        }
        return native ? 'native-fallback' : 'none'
    }

    return 'none'
}

export function useLocalNotifications() {
    return { state, notify, requestNotificationPermission, initLocalNotifications }
}
