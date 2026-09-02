import '../../bootstrap.js'

const api = window.axios

// Relative URLs (route(..., false)) — on a device Ziggy's absolute base is
// APP_URL, which isn't the on-device origin the web view actually serves from.

export const fetchStudentStatus = async (remoteId) => {
    const { data } = await api.get(route('api.students.status', remoteId, false))

    return data
}

export const fetchStudentHistory = async (remoteId, params = {}) => {
    const { data } = await api.get(route('api.students.history', remoteId, false), { params })

    return data
}

export const fetchStudentAlerts = async (remoteId, params = {}) => {
    const { data } = await api.get(route('api.students.alerts', remoteId, false), { params })

    return data
}
