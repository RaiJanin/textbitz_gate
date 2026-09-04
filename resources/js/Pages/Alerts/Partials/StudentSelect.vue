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
    <select
        v-if="students.length > 1"
        :value="selectedId"
        class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-sm text-gray-800 dark:text-gray-200 w-fit"
        @change="$emit('select', Number($event.target.value))"
    >
        <option v-for="student in students" :key="student.remote_id" :value="student.remote_id">
            {{ student.full_name }}
        </option>
    </select>
</template>
