import { usePage } from '@inertiajs/vue3'

/**
 * FCM/APNs token wiring — the primary push path (`fatlum/nativephp-push`).
 *
 * Mobile only; no-op on web. Does NOT request permission (that's
 * primePushNotifications). Here we: listen for the OS token (initial +
 * rotations) and, if permission is already granted, register the current token
 * now. Every token is POSTed to /api/device-tokens → forwarded to the server,
 * which sends the actual pushes (SendPushNotification / FcmHttpV1Sender).
 *
 * Inert until the plugin is installed — `pushNotifications.getToken` is absent
 * from `#nativephp` without it, and useLocalNotifications is the fallback.
 */

// `#nativephp` exports lowercase `pushNotifications` / `on` / `Events`.
const normalise = (r) => (typeof r === 'string' ? r : (r?.token ?? r?.status ?? null))

export async function startPushNotifications() {
    const platform = usePage().props?.platform
    if (!platform?.isAndroid && !platform?.isIos) {
        return
    }

    let native
    try {
        native = await import('../../../vendor/nativephp/mobile/resources/jump/dist/native.js')
    } catch {
        return
    }

    const { pushNotifications, on, Events } = native
    if (!pushNotifications?.getToken) {
        return // firebase plugin not bundled
    }

    const platformName = platform.isIos ? 'ios' : 'android'
    const tokenEvent = Events?.PushNotification?.TokenGenerated
        ?? 'Native\\Mobile\\Events\\PushNotification\\TokenGenerated'

    const sendToken = (raw) => {
        const token = normalise(raw)
        if (!token) return
        window.axios
            .post(route('api.device-tokens.store', undefined, false), { token, platform: platformName })
            .catch(() => {})
    }

    try {
        on(tokenEvent, (payload) => sendToken(payload?.token ?? payload))
    } catch {
        /* ignore */
    }

    try {
        if (normalise(await pushNotifications.checkPermission()) === 'granted') {
            pushNotifications.enroll()
            sendToken(await pushNotifications.getToken())
        }
    } catch {
        /* ignore */
    }
}
