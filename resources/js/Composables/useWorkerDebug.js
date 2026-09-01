import { reactive } from 'vue'

/**
 * On-device view of the background workers — the scheduler (connectivity
 * heartbeat + PullTapsFromServer) and the database queue (preference / link
 * push jobs). Polls the debug-only `/debug/workers` endpoint. Enabled when
 * VITE_APP_DEBUG === "true" or localStorage.echoDebug === "1".
 */

const state = reactive({
    enabled: false,
    lastPolledAt: null,
    scheduler: { tasks: [], recent: [] },
    queue: { pending: null, failed: null, processed_ok: 0, processed_fail: 0, oldest_pending_at: null, recent: [] },
    connectivity: { online: false, checked_recently: false },
    sync: null,
    busy: '',
})

let started = false

function computeEnabled() {
    try {
        if (localStorage.getItem('echoDebug') === '1') return true
    } catch {
        /* ignore */
    }
    return import.meta.env.VITE_APP_DEBUG === 'true'
}

async function poll() {
    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return

    try {
        const { data } = await window.axios.get('/debug/workers')
        Object.assign(state, data)
        state.lastPolledAt = new Date()
    } catch {
        /* debug endpoint absent / offline */
    }
}

function start() {
    if (started) return
    started = true

    state.enabled = computeEnabled()
    if (!state.enabled) return

    poll()
    setInterval(poll, 8000)

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') poll()
        })
    }
}

async function action(path, label) {
    state.busy = label
    try {
        await window.axios.post(`/debug/workers/${path}`)
        await poll()
    } catch {
        /* ignore */
    } finally {
        state.busy = ''
    }
}

export function useWorkerDebug() {
    start()
    return state
}

export const runScheduler = () => action('run-scheduler', 'scheduler')
export const processQueue = () => action('process-queue', 'queue')
export const retryFailed = () => action('retry-failed', 'retry')
export const clearWorkerLog = () => action('clear', 'clear')
