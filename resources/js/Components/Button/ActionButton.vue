<script setup>
const props = defineProps({
    colorClass: {
        type: String,
        default: 'neutral',
        validator: (value) => ['blue', 'red', 'neutral'].includes(value),
    }
})

const colorClasses = {
    blue : 'text-blue-600 dark:text-blue-400',
    red: 'text-red-600 dark:text-red-400',
    neutral: 'text-gray-600 dark:text-gray-300'
}
</script>

<template>
    <button 
        class="text-sm font-bold active:opacity-75"
        :class="[colorClasses[colorClass]]"
    >
        <slot />
    </button>
</template>