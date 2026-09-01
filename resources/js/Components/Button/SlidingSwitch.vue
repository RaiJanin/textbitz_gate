<script setup>
const model = defineModel({
    type: Boolean,
    default: false,
});
</script>

<template>
    <button
        @click="model = !model"
        class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors duration-300"
        :class="model ? 'bg-blue-500 dark:bg-blue-400' : 'bg-gray-300 dark:bg-gray-400'"
    >
        <span
            class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform duration-300"
            :class="model ? 'translate-x-6' : 'translate-x-1'"
        />
    </button>
</template>