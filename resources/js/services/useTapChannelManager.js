import { reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'
import crossPlatformToast from '@/helpers/crossPlatformToast'
import { notify } from '@/Composables/useLocalNotifications'

/**
 * Subscribes to a private `student.{id}` channel per linked student plus the
 * account `user.{id}` channel, and keeps a shared reactive store of the latest
 * tap per student. Pages watch the store and refetch their own data.
 */

const toast = crossPlatformToast()
const page = usePage()

const state = reactive({
    byStudent: {},
    lastEventAt: null,
})

const subscribed = new Set()
let accountChannel = null

function ensureStudent(studentId) {
    if (!state.byStudent[studentId]) {
        state.byStudent[studentId] = {
            presence: null,
            lastTap: null,
            lastEventAt: null,
        }
    }
    return state.byStudent[studentId]
}

function applyTap(studentId, tap) {
    const entry = ensureStudent(studentId)
    entry.lastTap = tap
    entry.presence = tap.direction === 'in' ? 'at_school' : 'left'
    entry.lastEventAt = new Date()
    state.lastEventAt = entry.lastEventAt
}

function linkedStudentIds() {
    const shared = page.props?.gate?.linkedStudentIds
    if (Array.isArray(shared) && shared.length) return shared

    // Fallback to whatever the current page happens to expose.
    const fromPage = page.props?.students ?? []
    return Array.isArray(fromPage)
        ? fromPage.map((s) => (typeof s === 'object' ? (s.remote_id ?? s.id) : s))
        : []
}

export function subscribeToStudent(studentId) {
    if (!window.Echo || !studentId || subscribed.has(studentId)) return
    subscribed.add(studentId)

    window.Echo.private(`student.${studentId}`)
        .listen('.TapRecorded', (e) => {
            applyTap(studentId, e)

            const arriving = e.direction === 'in'
            const verb = arriving ? 'tapped IN' : 'tapped OUT'
            const late = e.is_late ? ' — late' : ''

            toast.show(`${e.student_name} ${verb} at ${e.gate_name ?? 'the gate'}${late}`)

            notify({
                title: `${e.student_name} ${verb}`,
                body: `${e.gate_name ?? 'School gate'} · ${e.at ?? ''}${late}`.trim(),
                tag: `student-${studentId}`,
                url: '/home',
                kind: e.is_late ? 'late_alert' : (arriving ? 'arrival' : 'departure'),
            })
        })
        .listen('.StudentMarkedAbsent', (e) => {
            toast.error(`${e.student_name} was marked absent on ${e.date}`)

            notify({
                title: `${e.student_name} was marked absent`,
                body: `No gate taps on ${e.date}. Contact the school if this looks wrong.`,
                tag: `absent-${studentId}`,
                url: '/alerts',
                kind: 'late_alert',
                priority: 'high',
            })
        })
        .error((error) => console.warn(`student.${studentId} channel error`, error))
}

export function subscribeToLinkedStudents() {
    linkedStudentIds().filter(Boolean).forEach(subscribeToStudent)

    const user = page.props?.auth?.user

    if (user?.remote_id && window.Echo && !accountChannel) {
        accountChannel = window.Echo.private(`user.${user.remote_id}`)
        accountChannel
            .listen('.GuardianLinkedToStudent', (e) => {
                toast.success(`Linked to ${e.student_name}`)
                notify({ title: `Linked to ${e.student_name}`, tag: 'link', url: '/settings' })
                subscribeToStudent(e.student_id)
            })
            .listen('.StudentMarkedAbsent', (e) => {
                toast.error(`${e.student_name} was marked absent on ${e.date}`)
                notify({
                    title: `${e.student_name} was marked absent`,
                    body: `No gate taps on ${e.date}.`,
                    tag: `absent-${e.student_id ?? 'x'}`,
                    url: '/alerts',
                    kind: 'late_alert',
                    priority: 'high',
                })
            })
            .error((error) => console.warn('account channel error', error))
    }
}

export function getStudentState(studentId) {
    return ensureStudent(studentId)
}

export function getTapState() {
    return state
}
