<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    action: {
        type: Function,
        required: true,
    }
})
</script>

<template>
    <button @click="action" class="w-full rounded-xl bg-red-600 px-4 py-2 text-red-100 disabled:opacity-25 disabled:cursor-not-allowed transition-all duration-300">
        <slot></slot>
    </button>
</template>
