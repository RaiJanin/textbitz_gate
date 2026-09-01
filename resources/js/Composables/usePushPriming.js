import { usePage } from '@inertiajs/vue3'

/**
 * First-run notification-permission flow for the mobile app.
 *
 * Shows a NATIVE priming dialog (Dialog.alert) explaining the value before
 * triggering the real OS permission prompt (PushNotifications.enroll). Runs
 * once — the decision is remembered in localStorage. No-op on web/desktop.
 */

const NATIVE = '../../../vendor/nativephp/mobile/resources/dist/native.js'
const STORE_KEY = 'gate.push.prime'      // 'granted' | 'declined'
const DIALOG_ID = 'gate-push-prime'

function readDecision() {
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

    if (['granted', 'declined'].includes(readDecision())) {
        return
    }

    let native
    try {
        native = await import(/* @vite-ignore */ NATIVE)
    } catch {
        return // push plugin not in this build
    }

    const { Dialog, PushNotifications, On, Events } = native
    const buttonPressed = Events?.Alert?.ButtonPressed ?? 'Native\\Mobile\\Events\\Alert\\ButtonPressed'

    let status = null
    try {
        status = await PushNotifications.checkPermission()
    } catch {
        /* ignore */
    }

    // OS already granted — nothing to prime, token refresh is handled elsewhere.
    if (status === 'granted') {
        remember('granted')
        return
    }

    // OS-level denied — a priming dialog can't re-prompt; point them at Settings.
    if (status === 'denied') {
        remember('declined')
        try {
            await Dialog.alert(
                'Notifications are off',
                'To get gate alerts, enable notifications for TextBitz Gate in your device Settings.',
                ['OK'],
                `${DIALOG_ID}-denied`,
            )
        } catch {
            /* ignore */
        }
        return
    }

    // not_determined / unknown → prime, then ask natively.
    On(buttonPressed, async (payload) => {
        if (payload?.id !== DIALOG_ID) {
            return
        }

        if (payload.index !== 1) {
            remember('declined')
            return
        }

        try {
            PushNotifications.enroll() // native OS permission prompt
        } catch {
            /* ignore */
        }

        // Re-check shortly after so we store the real outcome.
        setTimeout(async () => {
            try {
                const after = await PushNotifications.checkPermission()
                remember(after === 'granted' ? 'granted' : 'declined')
            } catch {
                /* leave undecided — try again next launch */
            }
        }, 1500)
    })

    // Let the first screen settle before interrupting.
    setTimeout(() => {
        try {
            Dialog.alert(
                'Get gate alerts',
                "Turn on notifications and TextBitz Gate will tell you the moment your child taps in or out at the school gate — even when the app is closed.",
                ['Not now', 'Turn on notifications'],
                DIALOG_ID,
            )
        } catch {
            /* ignore */
        }
    }, 1200)
}
