<script setup>
defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    selectedId: {
        type: [Number, null],
        default: null,
    },
});

defineEmits(['select']);
</script>

<template>
    <div class="flex gap-2 overflow-x-auto pb-1">
        <button
            v-for="student in students"
            :key="student.remote_id"
            class="px-3 py-1.5 rounded-full text-sm whitespace-nowrap border"
            :class="student.remote_id === selectedId
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600'"
            @click="$emit('select', student.remote_id)"
        >
            {{ student.full_name }}
        </button>
    </div>
</template>
