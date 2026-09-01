<script setup>
defineProps({
    context: String,
    label: String,
})
</script>

<template>
    <div class="flex flex-col">
        <small class="text-gray-500 dark:text-gray-300">{{ label }}</small>
        <p class="text-gray-800 dark:text-gray-100">{{ context }}</p>
    </div>
</template>