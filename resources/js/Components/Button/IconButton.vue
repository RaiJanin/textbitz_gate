<script setup>
defineProps({
    colorClass: {
        default: 'text-blue-500 dark:text-blue-300'
    },
    icon: {
        type: Object,
        default: null
    },
})
</script>

<template>
    <button :class="colorClass" class="active:opacity-75">
        <component v-if="icon" :is="icon" :size="22" />
        <div v-else>
            <slot />
        </div>
    </button>
</template>