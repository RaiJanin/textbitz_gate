<script setup>
defineProps({
    question: {
        type: String,
        required: true
    },
    actionText: {
        type: String,
        required: true
    }
})

defineEmits(['switchScreen'])
</script>

<template>
    <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400 w-full rounded-xl  py-4">
        {{ question }}
        <span @click="$emit('switchScreen')" class="font-semibold text-gray-800 dark:text-gray-300 cursor-pointer">
            {{ actionText }}
        </span>
    </p>
</template>