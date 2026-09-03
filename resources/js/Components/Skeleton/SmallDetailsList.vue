<script setup>
defineProps({
    rows: {
        type: Number,
        default: 1
    }
})
</script>

<template>
    <div v-for="row in rows" :key="row"
     class="flex flex-col gap-1 pl-8">
        <span class="animate-pulse-fast p-2 rounded-lg w-[95%] bg-gray-300 dark:bg-gray-500"></span>
        <span class="animate-pulse-fast [animation-delay:100ms] p-2 rounded-lg w-12 bg-gray-300 dark:bg-gray-500"></span>
    </div>
</template>