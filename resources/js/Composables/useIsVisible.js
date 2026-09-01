import { ref, onMounted, onUnmounted } from 'vue';

export function useIsVisible(elementRef, options = {}) {
    const isVisible = ref(true);
    let observer = null;

    onMounted(() => {
        observer = new IntersectionObserver(
            ([entry]) => {
                isVisible.value = entry.isIntersecting;
            },
            {
                threshold: 0.1,
                ...options,
            }
        );

        if (elementRef.value) {
            observer.observe(elementRef.value);
        }
    });

    onUnmounted(() => {
        if (observer) observer.disconnect();
    });

    return { isVisible };
}