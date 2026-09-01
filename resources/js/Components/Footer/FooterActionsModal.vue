<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const isShown = ref(false)

const open = () => {
    isShown.value = true
    document.body.style.overflow = 'hidden'
    emit('update:modelValue', true)
}

const close = () => {
    isShown.value = false
    document.body.style.overflow = ''
    emit('update:modelValue', false)
}

watch(
    () => props.modelValue,
    (value) => {
        if(value) {
            open()
        } else {
            isShown.value = false
            document.body.style.overflow = ''
        }
    },
    { immediate: true }
)

onUnmounted(() => {
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <Transition name="slide-up">
            <div v-if="isShown" class="fixed z-50 bottom-0 w-full bg-white dark:bg-black/80 backdrop-blur-lg border-t border-gray-200 dark:border-gray-600 px-4 pt-2 pb-6">
                <div class="flex flex-wrap justify-end">
                    <div class="flex-2 max-w-full">
                        <slot />
                    </div>
                    <button @click="close" class="text-gray-600 dark:text-gray-300 active:opacity-75 flex flex-col items-center pl-6"><X :size="26"/><small class="text-[10px]">Cancel</small></button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

.slide-up-enter-to,
.slide-up-leave-from {
    transform: translateY(0);
    opacity: 1;
}
</style>