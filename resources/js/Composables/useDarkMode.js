import { ref, watch } from 'vue' 
import settings from '@/data/settings'

/**
 * Reactive dark mode preference, persisted to `localStorage`.
 * Initialized from `localStorage` if a value was previously saved,
 * otherwise falls back to the app's default (`settings.preferences.darkMode`).
 *
 * Expected values: `'On'`, `'Off'`, or `'System'` (follows OS preference).
 *
 * @type {import('vue').Ref<string>}
 */
export const darkMode = ref(localStorage.getItem('darkMode') ?? settings.preferences.darkMode)

/**
 * Applies (or removes) the `dark` class on the root `<html>` element
 * based on the given mode. Does not persist anything — pure DOM side effect.
 *
 * @param {'On' | 'Off' | 'System'} mode - The theme mode to apply.
 *   - `'On'`: forces dark mode.
 *   - `'Off'`: forces light mode.
 *   - `'System'`: matches the OS's `prefers-color-scheme` at the time
 *     this function runs (does not react to later OS-level changes).
 * @returns {void}
 */
const applyTheme = (mode) => {
    const html = document.documentElement

    html.classList.remove('dark')

    if (mode === 'On') {
        html.classList.add('dark')
    } else if (mode === 'System') {
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('dark')
        }
    }
}

// Persists `darkMode` to localStorage and applies it to the DOM whenever
// it changes. Runs immediately on load so the theme is applied on init.
watch(
    darkMode,
    (mode) => {
        localStorage.setItem('darkMode', mode)
        applyTheme(mode)
    },
    { immediate: true}
)

export { applyTheme }