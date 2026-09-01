import axios from "axios";

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['Accept'] = 'application/json'

/*
 * Background calls (Echo broadcast-auth, the sync-status poll, realtime status
 * refetches) must never bounce the app to the login screen. If one of the local
 * `/api/*` endpoints answers 401/419/403 — or hands back an HTML page because a
 * redirect was followed — swallow it here so it stays a rejected promise the
 * caller already handles, and never turns into a navigation.
 */
window.axios.interceptors.response.use(
    (response) => {
        const isApi = (response.config?.url || '').includes('/api/')
        const isHtml = (response.headers?.['content-type'] || '').includes('text/html')

        if (isApi && isHtml) {
            return Promise.reject(
                Object.assign(new Error('api_auth_lost'), { response, silent: true }),
            )
        }

        return response
    },
    (error) => {
        const url = error.config?.url || ''
        const status = error.response?.status

        if (url.includes('/api/') && [401, 403, 419].includes(status)) {
            error.silent = true
            if (import.meta.env.VITE_APP_DEBUG === 'true') {
                console.warn(`[api] ${status} on ${url} — ignored (no redirect)`)
            }
        }

        return Promise.reject(error)
    },
)

// Background API failures marked `silent` must never escalate — swallow the
// unhandled rejection so nothing (Inertia, a global handler, a devtools reload)
// reacts to it.
if (typeof window !== 'undefined') {
    window.addEventListener('unhandledrejection', (event) => {
        if (event.reason?.silent) {
            event.preventDefault()
        }
    })
}

//-------------------------------------------------------

import { applyTheme } from "./Composables/useDarkMode";
import settings from "./data/settings";

applyTheme(localStorage.getItem('darkMode') ?? settings.preferences.darkMode)

//-------------------------------------------------------

import './echo';
