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
    month: {
        type: String,
        default: '',
    },
});

defineEmits(['select', 'update:month']);
</script>

<template>
    <div class="flex flex-wrap gap-2 items-center">
        <select
            :value="selectedId"
            class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-gray-800 dark:text-gray-200 text-sm"
            @change="$emit('select', Number($event.target.value))"
        >
            <option v-for="student in students" :key="student.remote_id" :value="student.remote_id">
                {{ student.full_name }}
            </option>
        </select>

        <input
            type="month"
            :value="month"
            class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600 text-gray-800 dark:text-gray-200 text-sm"
            @change="$emit('update:month', $event.target.value)"
        />
    </div>
</template>
