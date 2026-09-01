<script setup>
defineProps({
    alert: {
        type: Object,
        required: true,
    },
});

const icon = {
    late: '⏰',
    absent: '⚠️',
    weekly_summary: '📊',
};
</script>

<template>
    <li class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 flex gap-3">
        <span class="text-xl">{{ icon[alert.type] ?? '•' }}</span>
        <div>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ alert.title }}</p>
            <p class="text-sm text-gray-500">{{ alert.body }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ alert.date }}</p>
        </div>
    </li>
</template>
