<script setup>
defineProps({
    rows: {
        type: Number,
        default: 1
    }
})
</script>

<template>
    <ul class="space-y-2 w-full">
        <li
            v-for="row in rows" :key="row"
            class="flex justify-between text-sm border-b border-gray-100 dark:border-gray-700 pb-2 last:border-0"
        >
            <span class="animate-pulse-fast p-2 rounded-lg w-20 bg-gray-300 dark:bg-gray-500"></span>
            <span class="animate-pulse-fast [animation-delay:100ms] p-2 rounded-lg w-32 bg-gray-300 dark:bg-gray-500"></span>
        </li>
    </ul>
</template>