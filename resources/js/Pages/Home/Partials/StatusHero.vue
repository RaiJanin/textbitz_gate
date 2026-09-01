<script setup>
import { computed } from 'vue';

const props = defineProps({
    student: {
        type: Object,
        default: null,
    },
    status: {
        type: Object,
        default: null,
    },
});

const presenceLabel = computed(() => ({
    at_school: 'At School',
    left: 'Left for the day',
    not_arrived: 'Not yet arrived',
}[props.status?.presence] ?? '—'));
</script>

<template>
    <section class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-sm text-gray-500">{{ student?.full_name }}</p>
        <p class="text-3xl font-semibold mt-1 text-gray-900 dark:text-gray-100">
            {{ presenceLabel }}
        </p>
        <p v-if="status?.is_late" class="mt-1 text-amber-600 text-sm font-medium">Late arrival</p>

        <div class="mt-4 flex gap-6 text-sm text-gray-600 dark:text-gray-300">
            <div>
                <span class="block text-xs uppercase text-gray-400">Arrived</span>
                {{ status?.first_in ?? '—' }}
            </div>
            <div>
                <span class="block text-xs uppercase text-gray-400">Left</span>
                {{ status?.last_out ?? '—' }}
            </div>
        </div>

        <p v-if="status?.stale" class="mt-3 text-xs text-gray-400">
            Showing last synced data (offline).
        </p>
    </section>
</template>
