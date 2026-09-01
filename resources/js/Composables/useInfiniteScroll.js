import { ref, watch } from 'vue'
import { useIsVisible } from '@/Composables/useIsVisible'

/**
 * Customized page-based infinite scroll (no third-party library).
 *
 * Built on the project's own `useIsVisible` IntersectionObserver composable:
 * bind `sentinelRef` to an element at the bottom of the list and the next page
 * loads whenever it scrolls into view.
 *
 * @param {(page: number) => Promise<{ data: any[], hasMore: boolean }>} loadPage
 * @param {{ immediate?: boolean }} [options]
 */
export function useInfiniteScroll(loadPage, { immediate = true } = {}) {
    const items = ref([])
    const page = ref(0)
    const loading = ref(false)
    const done = ref(false)
    const error = ref(null)
    const sentinelRef = ref(null)

    const { isVisible } = useIsVisible(sentinelRef, { threshold: 0.1 })

    async function loadMore() {
        if (loading.value || done.value) return

        loading.value = true
        error.value = null

        try {
            const next = page.value + 1
            const { data, hasMore } = await loadPage(next)

            items.value.push(...(data ?? []))
            page.value = next

            if (!hasMore) {
                done.value = true
            }
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false

            // Short lists: the sentinel can still be on screen after a page
            // loads — keep filling until it scrolls away or we run out.
            if (!done.value && !error.value && isVisible.value) {
                setTimeout(loadMore, 150)
            }
        }
    }

    function reset() {
        items.value = []
        page.value = 0
        done.value = false
        error.value = null

        if (immediate) {
            loadMore()
        }
    }

    watch(isVisible, (visible) => {
        if (visible) {
            loadMore()
        }
    })

    if (immediate) {
        loadMore()
    }

    return { items, page, loading, done, error, sentinelRef, loadMore, reset }
}
