<script setup>
import EmptyActivity from '@/Components/Placeholders/EmptyActivity.vue';

defineProps({
    timeline: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Today's timeline</h2>

        <p v-if="loading" class="text-sm text-gray-400">Loading…</p>

        <ul v-else-if="timeline.length" class="space-y-2">
            <li
                v-for="(tap, i) in timeline"
                :key="i"
                class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-700 pb-2 last:border-0"
            >
                <span class="font-medium">{{ tap.direction === 'in' ? 'Arrived' : 'Dismissed' }}</span>
                <span class="text-gray-500">
                    {{ tap.at }} · {{ tap.gate ?? '—' }}<span v-if="tap.is_late" class="text-amber-600"> · late</span>
                </span>
            </li>
        </ul>

        <EmptyActivity v-else />
    </section>
</template>
