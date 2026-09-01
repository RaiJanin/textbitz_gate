<script setup>
defineProps({
    icon: {
        type: Object,
        default: null
    },
    iconSize: {
        type: Number,
        default: 200
    },
    title: {
        default: ''
    },
    message: {
        default: ''
    }
})
</script>

<template>
    <div class="flex flex-col items-center gap-20">
        <component v-if="icon" :is="icon" :size="iconSize" class="text-blue-300" />
        <i v-else class="fa-regular fa-face-frown text-blue-300 text-9xl"></i>
        <div class="flex flex-col gap-3 items-center">
            <h1 class="text-gray-700 dark:text-gray-300 text-2xl font-bold">{{ title !== '' ? title : 'Opps'}}</h1>
            <p class="text-center text-gray-500 dark:text-gray-400 max-w-42">{{ message !== '' ? message : 'Something went wrong' }}</p>
        </div>
    </div>
</template>