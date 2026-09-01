import { reactive } from 'vue'

/**
 * Live Reverb/Echo connection introspection for on-device debugging.
 * Taps the underlying pusher-js connection: state changes, socket id, every
 * raw inbound frame, and subscription errors. Enabled when VITE_APP_DEBUG is
 * "true" or localStorage.echoDebug === "1".
 */

const state = reactive({
    enabled: false,
    available: false,
    connection: 'initializing',
    socketId: null,
    reconnects: 0,
    target: '',
    channels: [],
    events: [],
    lastError: null,
    authRedirects: null,
    lastSyncAt: null,
})

let started = false

function computeEnabled() {
    let ls = false
    try {
        ls = localStorage.getItem('echoDebug') === '1'
    } catch {
        /* ignore */
    }
    return import.meta.env.VITE_APP_DEBUG === 'true' || ls
}

function refreshChannels() {
    try {
        const all = window.Echo?.connector?.pusher?.channels?.channels ?? {}
        state.channels = Object.entries(all).map(([name, ch]) => ({
            name,
            subscribed: !!ch.subscribed,
        }))
    } catch {
        /* ignore */
    }
}

function pushEvent(channel, event, data) {
    let preview = ''
    try {
        preview = typeof data === 'string' ? data : JSON.stringify(data)
    } catch {
        /* ignore */
    }
    state.events.unshift({
        t: new Date(),
        channel: channel || '—',
        event: event || '?',
        preview: (preview || '').slice(0, 160),
    })
    if (state.events.length > 40) state.events.length = 40
}

async function pollServerDiagnostics() {
    try {
        const { data } = await window.axios.get('/debug/auth-redirects')
        state.authRedirects = data?.count ?? (data?.entries?.length ?? 0)
    } catch {
        /* ignore */
    }
    try {
        const { data } = await window.axios.get(route('api.sync.status'))
        state.lastSyncAt = data?.report?.at ?? null
    } catch {
        /* ignore */
    }
}

function start() {
    if (started) return
    started = true

    state.enabled = computeEnabled()
    if (!state.enabled) return

    if (!window.Echo?.connector?.pusher) {
        return
    }

    state.available = true
    const pusher = window.Echo.connector.pusher
    const conn = pusher.connection

    state.target = `${import.meta.env.VITE_REVERB_HOST}:${import.meta.env.VITE_REVERB_PORT ?? 443} · ${import.meta.env.VITE_REVERB_SCHEME ?? 'https'}`
    state.connection = conn.state
    state.socketId = conn.socket_id ?? null

    conn.bind('state_change', ({ previous, current }) => {
        if (current === 'connected' && previous && previous !== 'connected' && previous !== 'initialized') {
            state.reconnects++
        }
        state.connection = current
        state.socketId = conn.socket_id ?? state.socketId
        pushEvent('·connection', `${previous} → ${current}`)
        refreshChannels()
    })

    conn.bind('connected', () => {
        state.socketId = conn.socket_id
        refreshChannels()
    })

    conn.bind('error', (err) => {
        try {
            state.lastError = JSON.stringify(err).slice(0, 240)
        } catch {
            state.lastError = String(err)
        }
        pushEvent('·error', 'connection error', err)
    })

    // Every raw inbound frame: { event, channel, data }
    conn.bind('message', (payload) => {
        pushEvent(payload?.channel, payload?.event, payload?.data)
        if (String(payload?.event).includes('subscription')) refreshChannels()
    })

    refreshChannels()
    pollServerDiagnostics()
    setInterval(refreshChannels, 3000)
    setInterval(pollServerDiagnostics, 15000)
}

export function useEchoDebug() {
    start()
    return state
}

export function toggleEchoDebug() {
    state.enabled = !state.enabled
    try {
        localStorage.setItem('echoDebug', state.enabled ? '1' : '0')
    } catch {
        /* ignore */
    }
    if (state.enabled && !state.available) start()
}

export function reconnectEcho() {
    try {
        window.Echo.connector.pusher.disconnect()
        window.Echo.connector.pusher.connect()
    } catch {
        /* ignore */
    }
}

export function clearEchoDebugEvents() {
    state.events.length = 0
    state.reconnects = 0
    state.lastError = null
}
