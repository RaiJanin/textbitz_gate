<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="text-gray-800 dark:text-gray-200 peer bg-transparent placeholder:text-gray-400 border-none focus:outline-none focus:ring-0"
        v-model="model"
        ref="input"
    />
</template>