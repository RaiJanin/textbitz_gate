<script setup>
import { computed } from 'vue';
import EmptyHistory from '@/Components/Placeholders/EmptyHistory.vue';
import SmallRowList from '@/Components/Skeleton/SmallRowList.vue';

const props = defineProps({
    days: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const records = computed(() => props.days.filter((day) => day.taps?.length || day.state === 'absent'));
</script>

<template>
    <section class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
        <h2 class="font-semibold mb-3 text-gray-900 dark:text-gray-100">Daily records</h2>

        <div v-if="loading" class="flex justify-center">
            <SmallRowList />
        </div>

        <EmptyHistory v-else-if="!records.length" />

        <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <li v-for="day in records" :key="day.date" class="py-2 flex justify-between text-sm">
                <span>{{ day.date }}</span>
                <span class="text-gray-500">
                    <template v-if="day.state === 'absent'">Absent</template>
                    <template v-else>IN {{ day.first_in ?? '—' }} · OUT {{ day.last_out ?? '—' }}</template>
                </span>
            </li>
        </ul>
    </section>
</template>
