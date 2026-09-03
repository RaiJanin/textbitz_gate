<script setup>
import { Clock, TriangleAlert, ChartColumn, Circle } from 'lucide-vue-next';

defineProps({
    alert: {
        type: Object,
        required: true,
    },
});

const icon = {
    late: Clock,
    absent: TriangleAlert,
    weekly_summary: ChartColumn,
};

const iconColor = {
    late: "text-amber-500 dark:text-amber-400",
    absent: "text-red-500 dark:text-red-400",
    weekly_summary: "text-green-500 dark:text-green-400"
}
</script>

<template>
    <li class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 flex gap-3">
        <component :is="icon[alert.type] ?? Circle" 
            class="size-5 shrink-0" 
            :class="iconColor[alert.type] ?? 'text-gray-500 dark:text-gray-400'"
        />
        <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ alert.title }}</p>
            <p class="text-sm text-gray-500">{{ alert.body }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ alert.date }}</p>
        </div>
    </li>
</template>
