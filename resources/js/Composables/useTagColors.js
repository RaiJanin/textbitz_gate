/**
 * Available color palettes, cycled through by hash or chosen at random.
 * Each palette provides three class strings for different visual weights:
 * - `light`: subtle background/text, used for default tag chips
 * - `bold`: solid background with white text, used for emphasized tags
 * - `border`: a matching border color, used when `bordered` is requested
 *
 * @type {Array<{ light: string, bold: string, border: string }>}
 */
const palettes = [
    {
        light: 'bg-red-100 text-red-700 dark:bg-red-200/80 dark:text-red-800',
        bold: 'bg-red-500 text-white dark:bg-red-600',
        border: 'border-red-500',
    },
    {
        light: 'bg-blue-100 text-blue-700 dark:bg-blue-200/80 dark:text-blue-800',
        bold: 'bg-blue-500 text-white dark:bg-blue-600',
        border: 'border-blue-500',
    },
    {
        light: 'bg-green-100 text-green-700 dark:bg-green-200/80 dark:text-green-800',
        bold: 'bg-green-500 text-white dark:bg-green-600',
        border: 'border-green-500',
    },
    {
        light: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-200/80 dark:text-yellow-800',
        bold: 'bg-yellow-500 text-white dark:bg-yellow-600',
        border: 'border-yellow-500',
    },
    {
        light: 'bg-purple-100 text-purple-700 dark:bg-purple-200/80 dark:text-purple-800',
        bold: 'bg-purple-500 text-white dark:bg-purple-600',
        border: 'border-purple-500',
    },
]

/**
 * Produces a simple, deterministic non-cryptographic hash of a string by
 * summing its character codes. Used to consistently map a tag name to the
 * same palette index every time.
 *
 * @param {string} str - The string to hash (e.g. a tag name).
 * @returns {number} A non-negative integer hash. Not unique — collisions
 *   are expected and acceptable for palette selection.
 */
function hashString(str) {
    let hash = 0

    for (const c of str) {
        hash += c.charCodeAt(0)
    }

    return hash
}

/**
 * Builds the final Tailwind class string for a given palette and options.
 *
 * @param {{ light: string, bold: string, border: string }} palette - The
 *   palette to build classes from.
 * @param {Object} [options={}] - Display options.
 * @param {boolean} [options.bordered=false] - If true, appends the
 *   palette's border class.
 * @param {boolean} [options.bold=false] - If true, uses the palette's
 *   `bold` classes instead of `light`.
 * @returns {string} A space-separated Tailwind class string.
 */
function buildClasses(palette, options = {}) {
    const {
        bordered = false,
        bold = false,
    } = options

    return [
        bold ? palette.bold : palette.light,
        bordered && palette.border,
    ]
        .filter(Boolean)
        .join(' ')
}

/**
 * Returns Tailwind classes for a randomly selected palette. Since the
 * palette is chosen with `Math.random()`, calling this multiple times
 * for the same input will generally return different colors — use
 * `colorForTag` instead if you need a color that's stable across renders.
 *
 * @param {Object} [options={}] - Display options, see `buildClasses`.
 * @param {boolean} [options.bordered=false] - Include the border class.
 * @param {boolean} [options.bold=false] - Use bold (solid) styling instead of light.
 * @returns {string} A space-separated Tailwind class string.
 */
export function randomColor(options = {}) {
    const palette = palettes[Math.floor(Math.random() * palettes.length)]
    return buildClasses(palette, options)
}

/**
 * Returns Tailwind classes for a palette deterministically derived from
 * the given tag name, so the same tag always renders with the same color.
 *
 * @param {string} tag - The tag name to color (e.g. "urgent", "vip").
 * @param {Object} [options={}] - Display options, see `buildClasses`.
 * @param {boolean} [options.bordered=false] - Include the border class.
 * @param {boolean} [options.bold=false] - Use bold (solid) styling instead of light.
 * @returns {string} A space-separated Tailwind class string.
 */
export function colorForTag(tag, options = {}) {
    const palette = palettes[hashString(tag) % palettes.length]
    return buildClasses(palette, options)
}