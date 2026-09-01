<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    closeThreshold: {
        type: Number,
        default: 120,
    },
})

const emit = defineEmits(['update:modelValue'])

const visible = ref(false)
const dragging = ref(false)

const startY = ref(0)
const currentY = ref(0)
const translateY = ref(0)


const backdropOpacity = computed(() => {
    const progress = Math.min(translateY.value / window.innerHeight, 1)
    return 0.4 * (1 - progress)
})

const sheetStyle = computed(() => ({
    transform: `translate3d(0, ${translateY.value}px, 0)`,
    transition: dragging.value
        ? 'none'
        : 'transform 300ms cubic-bezier(.22,1,.36,1)',
    willChange: 'transform',
}))

const backdropStyle = computed(() => ({
    opacity: backdropOpacity.value,
    transition: dragging.value ? 'none' : 'opacity 300ms ease',
}))

function open() {
    visible.value = true

    document.body.style.overflow = 'hidden'

    translateY.value = window.innerHeight

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            translateY.value = 0
        })
    })
}

function finishClose() {
    visible.value = false
    translateY.value = 0

    document.body.style.overflow = ''

    emit('update:modelValue', false)
}

function close() {
    dragging.value = false

    translateY.value = window.innerHeight

    setTimeout(finishClose, 300)
}

watch(
    () => props.modelValue,
    (value) => {
        if (value) {
            open()
        } else if (visible.value) {
            close()
        }
    },
    { immediate: true }
)

function pointerDown(e) {
    dragging.value = true

    startY.value = e.clientY
    currentY.value = e.clientY

    window.addEventListener('pointermove', pointerMove)
    window.addEventListener('pointerup', pointerUp)
    window.addEventListener('pointercancel', pointerUp)
}

function pointerMove(e) {
    if (!dragging.value) return

    currentY.value = e.clientY

    const delta = currentY.value - startY.value

    if (delta > 0) {
        translateY.value = delta
    }
}

function pointerUp() {
    if (!dragging.value) return

    dragging.value = false

    window.removeEventListener('pointermove', pointerMove)
    window.removeEventListener('pointerup', pointerUp)
    window.removeEventListener('pointercancel', pointerUp)

    if (translateY.value > props.closeThreshold) {
        close()
    } else {
        translateY.value = 0
    }
}

onUnmounted(() => {
    document.body.style.overflow = ''

    window.removeEventListener('pointermove', pointerMove)
    window.removeEventListener('pointerup', pointerUp)
    window.removeEventListener('pointercancel', pointerUp)
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="fixed inset-0 z-50"
        >
            <div
                class="absolute inset-0 bg-black"
                :style="backdropStyle"
                @click="close"
            />

            <section
                :style="sheetStyle"
                class="absolute bottom-0 left-0 w-full rounded-t-3xl bg-white dark:bg-gray-900/85 backdrop-blur-xl shadow-2xl"
            >
                <div
                    class="flex justify-center py-3 touch-none cursor-grab active:cursor-grabbing"
                    @pointerdown="pointerDown"
                >
                    <div class="h-1.5 w-14 rounded-full bg-gray-300"/>
                </div>

                <div class="max-h-[80vh] overflow-y-auto p-5 pb-20">
                    <slot />
                </div>
            </section>
        </div>
    </Teleport>
</template>