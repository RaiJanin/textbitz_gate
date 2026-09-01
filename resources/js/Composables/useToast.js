import { ref } from 'vue';

// Module-level state so every component sharing this composable
// reads/writes the same toast queue.
const toasts = ref([]);
let nextId = 0;

export function useToast() {
    /**
     * Show a toast message.
     * @param {string} message
     * @param {object} [options]
     * @param {'default'|'success'|'error'} [options.type='default']
     * @param {number} [options.duration=3000] - ms before auto-dismiss. 0 = stays until manually closed.
     */
    function show(message, options = {}) {
        const { type = 'default', duration = 3000 } = options;
        const id = nextId++;

        toasts.value.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => dismiss(id), duration);
        }

        return id;
    }

    function success(message, options = {}) {
        return show(message, { ...options, type: 'success' });
    }

    function error(message, options = {}) {
        return show(message, { ...options, type: 'error' });
    }

    function dismiss(id) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    function clear() {
        toasts.value = [];
    }

    return { toasts, show, success, error, dismiss, clear };
}