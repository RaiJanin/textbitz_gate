import { ref, watch, onBeforeUnmount } from 'vue'

const PH_MOBILE_REGEX = /^\+639\d{9}$/

/**
 * Strips all whitespace from a phone number string.
 */
export const stripSpaces = (value) => value?.replace(/\s+/g, '') ?? value

/**
 * Normalizes a PH mobile number to the canonical +639XXXXXXXXX format.
 * Accepts local (09XXXXXXXXX) or international (+639XXXXXXXXX) input.
 */
export const normalizePhone = (value) => {
    if (!value) return value

    const cleaned = stripSpaces(value)

    if (cleaned.startsWith('09')) {
        return '+63' + cleaned.slice(1)
    }

    return cleaned
}

/**
 * Formats a raw (possibly partial) phone number as the user types,
 * inserting spaces progressively without requiring the full number to be complete.
 */
export const formatPhonePartial = (value) => {
    if (!value) return value

    const digitsOnly = value.replace(/[^\d+]/g, '')

    let prefix = ''
    let rest = ''
    let groups = []

    if (digitsOnly.startsWith('+63')) {
        prefix = '+63'
        rest = digitsOnly.slice(3).slice(0, 10)
        groups = [3, 3, 4]
    } else if (digitsOnly.startsWith('0')) {
        prefix = '0'
        rest = digitsOnly.slice(1).slice(0, 10)
        groups = [3, 3, 4]
    } else {
        return value
    }

    const parts = []
    let cursor = 0
    for (const size of groups) {
        if (rest.length > cursor) {
            parts.push(rest.slice(cursor, cursor + size))
        }
        cursor += size
    }

    return prefix + (parts.length ? ' ' + parts.join(' ') : '')
}

/**
 * Formats a normalized phone number for display with spacing:
 * +639XXXXXXXXX -> +639XX XXX XXXX
 */
export const formatPhoneDisplay = (value) => {
    if (!value) return value

    const normalized = normalizePhone(value)
    const match = normalized.match(/^(\+63)(\d{3})(\d{3})(\d{4})$/)

    if (!match) return value

    const [, prefix, part1, part2, part3] = match
    return `${prefix}${part1} ${part2} ${part3}`
}

/**
 * Checks whether a phone number (in any accepted input format) is a valid PH mobile number.
 */
export const isValidPhone = (value) => {
    if (!value) return false
    return PH_MOBILE_REGEX.test(normalizePhone(stripSpaces(value)))
}

/**
 * Composable providing real-time debounced validation + formatting helpers
 * for a phone number ref (typically a form field like form.phone_num).
 *
 * @param {import('@inertiajs/vue3').InertiaForm<Record<string, any>>} form - the reactive form object from `useForm()`, e.g. `useForm({ phone_num: '' })`
 * @param {string} field - the key on `form` holding the phone number value
 * @param {number} debounceMs - debounce delay before validation runs
 */
export function usePhoneFormatter(form, field = 'phone_num', debounceMs = 100) {
    const error = ref(null)
    let debounceTimer = null

    const validateNow = (value) => {
        if (!value) {
            error.value = null
            return
        }

        error.value = isValidPhone(value)
            ? null
            : 'Enter a valid PH phone number (e.g. 09171234567 or +639171234567).'
    }

    const handlePhoneInput = (event) => {
        const input = event.target
        const oldValue = input.value
        const oldCursor = input.selectionStart

        const digitsBeforeCursor = oldValue.slice(0, oldCursor).replace(/[^\d]/g, '').length

        const formatted = formatPhonePartial(oldValue)

        input.value = formatted
        form[field] = formatted

        let digitCount = 0
        let newCursor = formatted.length

        for (let i = 0; i < formatted.length; i++) {
            if (/\d/.test(formatted[i])) digitCount++
            if (digitCount === digitsBeforeCursor) {
                newCursor = i + 1
                break
            }
        }

        requestAnimationFrame(() => {
            input.setSelectionRange(newCursor, newCursor)
        })
    }

    watch(() => form[field], (value) => {
        clearTimeout(debounceTimer)

        if (!value) {
            error.value = null
            return
        }

        debounceTimer = setTimeout(() => validateNow(value), debounceMs)
    })

    onBeforeUnmount(() => clearTimeout(debounceTimer))

    return {
        error,
        validateNow,
        normalizePhone,
        formatPhoneDisplay,
        stripSpaces,
        isValidPhone,
        handlePhoneInput
    }
}