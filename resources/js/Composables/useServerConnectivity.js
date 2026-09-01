import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import crossPlatformToast from '@/helpers/crossPlatformToast'

const isOnline = ref(true)
let initialized = false

function initConnectivityWatcher() {
    if (initialized) return
    initialized = true

    const pusher = window.Echo.connector.pusher
    const toast = crossPlatformToast()

    isOnline.value = pusher.connection.state === 'connected'

    pusher.connection.bind('state_change', (states) => {
        const wasOnline = isOnline.value
        isOnline.value = states.current === 'connected'

        if (wasOnline && !isOnline.value) {
            toast.error("You're offline. Changes will sync when you're back online.")
        } else if (!wasOnline && isOnline.value) {
            toast.success('Back online — syncing…')
            import('@/Composables/useSyncNotifier').then((m) => m.pullNow())
        }
    })
}

export function useServerConnectivity() {
    const page = usePage()
    const remoteId = page.props.auth.user?.remote_id

    if (remoteId) {
        initConnectivityWatcher()

        window.Echo.private(`user.${remoteId}`)
    }

    return { isOnline }
}