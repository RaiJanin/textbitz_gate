import { usePage } from '@inertiajs/vue3'

/**
 * Mobile-only push-notification enrolment. On web this is a no-op — the guard
 * is `page.props.platform` (populated by the PHP PlatformService). Enrols with
 * the NativePHP push plugin, registers the FCM/APNS token with the server, and
 * keeps it fresh when the OS rotates it.
 */
export async function startPushNotifications() {
    const platform = usePage().props?.platform

    if (!platform?.isAndroid && !platform?.isIos) {
        return // web / desktop — nothing to do
    }

    let nativephp
    try {
        nativephp = await import('../../../vendor/nativephp/mobile/resources/dist/native.js')
    } catch {
        return // plugin not bundled in this build
    }

    const { PushNotifications, On, Events } = nativephp
    const platformName = platform.isIos ? 'ios' : 'android'

    const sendToken = (token) => {
        if (!token) return
        window.axios
            .post(route('api.device-tokens.store'), { token, platform: platformName })
            .catch(() => {})
    }

    // Ask permission + enrol.
    try {
        PushNotifications.enroll()
    } catch {
        /* ignore */
    }

    // A token may already exist (permission previously granted).
    try {
        const token = await PushNotifications.getToken()
        sendToken(token)
    } catch {
        /* ignore */
    }

    // And whenever the OS issues / rotates one.
    try {
        On(Events?.PushNotification?.TokenGenerated ?? 'Native\\Mobile\\Events\\PushNotification\\TokenGenerated', (payload) => {
            sendToken(payload?.token)
        })
    } catch {
        /* ignore */
    }
}
