<script setup>
defineProps({
    labelFor: {
        type: String,
        default: ''
    }
})
</script>

<template>
    <label class="text-sm">
        {{ labelFor }}
    </label>
</template>