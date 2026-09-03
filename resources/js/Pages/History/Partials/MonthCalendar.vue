<script setup>
import CalendarSkeleton from '@/Components/Skeleton/CalendarSkeleton.vue';

defineProps({
    days: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: true
    }
});

const stateColor = {
    on_time: 'bg-emerald-500',
    late: 'bg-amber-500',
    absent: 'bg-rose-500',
    none: 'bg-gray-200 dark:bg-gray-700',
};
</script>

<template>
    <section class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
        <div v-if="!loading" class="grid grid-cols-7 gap-1.5">
            <div
                v-for="day in days"
                :key="day.date"
                class="aspect-square rounded flex items-center justify-center text-[11px] text-white/90"
                :class="stateColor[day.state] ?? stateColor.none"
                :title="`${day.date} — ${day.state}`"
            >
                {{ Number(day.date.slice(-2)) }}
            </div>
        </div>

        <CalendarSkeleton v-else />

        <div class="flex gap-4 mt-3 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-500" />On time</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-500" />Late</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-500" />Absent</span>
        </div>
    </section>
</template>
