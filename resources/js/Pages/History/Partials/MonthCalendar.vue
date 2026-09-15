<script setup>
import { computed } from 'vue';
import CalendarSkeleton from '@/Components/Skeleton/CalendarSkeleton.vue';

const props = defineProps({
    days: {
        type: Array,
        default: () => [],
    },
    month: {
        type: String,
        default: '',
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

const weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const today = new Date().toISOString().slice(0, 10);

const cells = computed(() => {
    if (!props.month) return [];

    const [year, monthNum] = props.month.split('-').map(Number);
    const firstWeekday = new Date(year, monthNum - 1, 1).getDay();
    const daysInMonth = new Date(year, monthNum, 0).getDate();

    const byDate = new Map(props.days.map((day) => [day.date, day]));

    const grid = Array.from({ length: firstWeekday }, () => null);

    for (let date = 1; date <= daysInMonth; date++) {
        const iso = `${year}-${String(monthNum).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
        grid.push(byDate.get(iso) ?? { date: iso, state: 'none' });
    }

    while (grid.length % 7 !== 0) grid.push(null);

    return grid;
});
</script>

<template>
    <section class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-7 gap-1.5 mb-1.5">
            <span
                v-for="(label, idx) in weekdayLabels"
                :key="label"
                class="text-center text-[10px] font-medium"
                :class="idx === 0 || idx === 6 ? 'text-gray-400 dark:text-gray-500' : 'text-gray-500 dark:text-gray-400'"
            >
                {{ label }}
            </span>
        </div>

        <div v-if="!loading" class="grid grid-cols-7 gap-1.5">
            <div
                v-for="(day, idx) in cells"
                :key="day?.date ?? `blank-${idx}`"
                class="aspect-square rounded flex items-center justify-center text-[11px]"
                :class="day ? [stateColor[day.state] ?? stateColor.none, 'text-white/90', day.date === today ? 'ring-2 ring-offset-1 ring-blue-500 dark:ring-offset-gray-800' : '']  : ''"
                :title="day ? `${day.date} — ${day.state}` : ''"
            >
                {{ day ? Number(day.date.slice(-2)) : '' }}
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
