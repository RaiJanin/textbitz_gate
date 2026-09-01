import { onAppForegrounded, onAppBackgrounded } from '../../../vendor/djurovicigoor/app-lifecycle/resources/js/appLifecycle.js'
import { subscribeToLinkedStudents } from '@/services/useTapChannelManager'
import { pullNow } from '@/Composables/useSyncNotifier'

/**
 * Drives the client-side "workers" off the app-lifecycle plugin.
 *
 * On a device there is no schedule:work / queue:work, and the web view's JS
 * timers are throttled while backgrounded. So when the app returns to the
 * foreground we re-establish realtime and pull fresh data; the PHP listeners
 * (RunMobileWorkers / FlushOnBackground) handle the heartbeat + pending writes.
 */

let started = false

export function startAppLifecycle() {
    if (started) return
    started = true

    onAppForegrounded(() => {
        try {
            window.Echo?.connector?.pusher?.connect?.()
        } catch {
            /* ignore */
        }
        subscribeToLinkedStudents()
        pullNow() // server pull → toast / local notification on anything new
    })

    onAppBackgrounded(() => {
        // The PHP FlushOnBackground listener pushes pending writes; nothing to
        // do JS-side. Left here as the hook point.
    })
}
