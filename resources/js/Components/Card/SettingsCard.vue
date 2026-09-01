<script setup>
import { ArrowRight } from 'lucide-vue-next';

defineProps({
    label: String,
    icon: Object,
    action: Function
})
</script>

<template>
    <div class="bg-white/70 dark:bg-gray-500/40 backdrop-blur rounded-xl border border-gray-100 dark:border-gray-600 shadow-sm p-4">
        <div class="flex flex-wrap">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-300 mb-2 flex items-center gap-2">
                <component :is="icon" :size="16" class="text-blue-600 dark:text-blue-400"/>
                {{ label }}
            </h3>
            <button v-if="action" @click="action" class="flex-1 flex justify-end">
                <ArrowRight :size="24" class="text-gray-500 dark:text-gray-300" :stroke-width="2.5"/>
            </button>
        </div>
        <section class="flex flex-col gap-4">
            <slot />
        </section>
    </div>
</template>