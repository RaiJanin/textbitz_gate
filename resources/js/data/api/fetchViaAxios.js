import '../../bootstrap.js'

/**
 * Single place for every read (GET) call the app makes to its own Laravel
 * `/api/*` layer, which in turn proxies / caches the TextBitz Gate server.
 * Writes (PUT/POST/DELETE) stay next to the component that owns the action.
 */

const api = window.axios

export const fetchStudentStatus = async (remoteId) => {
    const { data } = await api.get(route('api.students.status', remoteId))

    return data
}

export const fetchStudentHistory = async (remoteId, params = {}) => {
    const { data } = await api.get(route('api.students.history', remoteId), { params })

    return data
}

export const fetchStudentAlerts = async (remoteId, params = {}) => {
    const { data } = await api.get(route('api.students.alerts', remoteId), { params })

    return data
}
