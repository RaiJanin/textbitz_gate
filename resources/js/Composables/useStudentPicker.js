import { ref, computed, watch, unref } from 'vue'

/**
 * Shared "pick one of the linked students" state used by Home, History and
 * Alerts. Defaults to the first student and calls `onChange(remoteId)` whenever
 * the selection changes (including once on setup).
 *
 * @param {Array|import('vue').Ref<Array>|(() => Array)} students - array, ref, or getter
 * @param {(remoteId: number|null) => void} [onChange]
 */
export function useStudentPicker(students, onChange) {
    const list = computed(() => {
        const value = typeof students === 'function' ? students() : unref(students)

        return Array.isArray(value) ? value : []
    })

    const selectedId = ref(list.value[0]?.remote_id ?? null)

    const selectedStudent = computed(
        () => list.value.find((s) => s.remote_id === selectedId.value) ?? null,
    )

    function select(remoteId) {
        selectedId.value = remoteId
    }

    // Never let a rejected onChange (a background fetch that 401s) escape as an
    // unhandled rejection.
    const runOnChange = (id) => {
        try {
            const result = onChange?.(id)
            if (result && typeof result.catch === 'function') result.catch(() => {})
        } catch {
            /* ignore */
        }
    }

    watch(selectedId, runOnChange)

    // Fire the initial load on a microtask, not synchronously — otherwise it
    // runs before the caller's `const { selectedId } = useStudentPicker(...)`
    // has finished initializing (temporal dead zone).
    if (onChange) {
        Promise.resolve().then(() => runOnChange(selectedId.value))
    }

    return { students: list, selectedId, selectedStudent, select }
}
