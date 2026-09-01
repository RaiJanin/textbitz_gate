<script setup>
import { useToast } from '@/Composables/useToast';

const { toasts, dismiss } = useToast();

const typeClasses = {
    default: 'bg-gray-900 text-white',
    success: 'bg-emerald-600 text-white',
    error: 'bg-red-600 text-white',
};
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed bottom-20 left-1/2 z-100 flex w-full max-w-sm -translate-x-1/2 flex-col gap-2 px-4"
            aria-live="polite"
            aria-atomic="true"
        >
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="flex items-center justify-between gap-3 rounded-lg px-4 py-3 shadow-lg"
                    :class="typeClasses[toast.type] || typeClasses.default"
                    role="status"
                    @click="dismiss(toast.id)"
                >
                    <span class="text-sm">{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>