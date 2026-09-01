<script setup>
import { ArrowLeft } from 'lucide-vue-next';

defineProps({
    returnAction: {
        type: Function,
        default: () => {}
    }
})
</script>

<template>
    <div class="fixed z-50 bottom-0 w-full bg-white dark:bg-black/80 backdrop-blur-lg border-t border-gray-200 dark:border-gray-600 px-4 pt-2 pb-6">
        <div class="flex flex-wrap justify-end">
            <div class="flex-2 max-w-full">
                <slot />
            </div>
            <button @click="returnAction" class="text-gray-600 dark:text-gray-300 active:opacity-75 flex flex-col items-center pl-6"><ArrowLeft :size="26"/><small class="text-[10px]">Back</small></button>
        </div>
    </div>
</template>