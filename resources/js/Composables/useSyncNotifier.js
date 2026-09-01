import crossPlatformToast from '@/helpers/crossPlatformToast'

/**
 * Toasts when the recurring background pull (PullTapsFromServer) brings in new
 * attendance data. Polls the plain-JSON `/api/sync/status` endpoint — never
 * Inertia, so a stray 401 can't bounce the app to the login screen.
 */

const toast = crossPlatformToast()
const SEEN_KEY = 'gate.sync.lastSeenAt'

let started = false
let lastSeenAt = readSeen()
let pendingWrites = null

function readSeen() {
    try {
        return sessionStorage.getItem(SEEN_KEY)
    } catch {
        return null
    }
}

function rememberSeen(at) {
    lastSeenAt = at
    try {
        sessionStorage.setItem(SEEN_KEY, at)
    } catch {
        /* private mode / storage disabled — in-memory only */
    }
}

function announce({ report, pending_writes }) {
    if (report?.at && report.at !== lastSeenAt) {
        const total = (report.new_taps ?? 0) + (report.updated_taps ?? 0)
        const first = lastSeenAt === null

        rememberSeen(report.at)

        if (total > 0 && !first) {
            const who = (report.students ?? []).join(', ')
            toast.show(
                total === 1
                    ? `New gate activity${who ? ` — ${who}` : ''}`
                    : `${total} attendance updates synced${who ? ` — ${who}` : ''}`,
            )
        }
    }

    if (pendingWrites !== null && pendingWrites > 0 && pending_writes === 0) {
        toast.success('Your changes are now synced')
    }
    pendingWrites = pending_writes ?? pendingWrites
}

async function poll() {
    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return

    try {
        const { data } = await window.axios.get(route('api.sync.status'))
        announce(data)
    } catch {
        /* offline / 401 — handled by the bootstrap interceptor, nothing to do */
    }
}

export function startSyncNotifier({ intervalMs = 45000 } = {}) {
    if (started) return
    started = true

    poll()
    setInterval(poll, intervalMs)

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') poll()
        })
    }
}

/** Manual pull-to-refresh: trigger a pull now and surface the result. */
export async function pullNow() {
    try {
        const { data } = await window.axios.post(route('api.sync.pull'))
        announce(data)
        return data
    } catch {
        return null
    }
}
