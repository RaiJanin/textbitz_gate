<script setup>
defineProps({
    icon: Object,
    iconColor: {
        default: ''
    },
    boxColor: {
        default: ''
    }
})
</script>

<template>
    <i class="p-2 flex items-center justify-center rounded-xl" :class="boxColor ? boxColor : 'bg-blue-100 dark:bg-blue-300'">
        <component :is="icon" :size="28" :class="iconColor ? iconColor : 'text-blue-600'"/>
    </i>
</template>
