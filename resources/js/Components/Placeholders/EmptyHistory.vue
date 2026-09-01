<script setup>
import { CalendarDays } from 'lucide-vue-next';
</script>

<template>
    <div class="flex flex-col items-center gap-3 py-10 text-center">
        <CalendarDays :size="40" class="text-blue-300" />
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
            No attendance records for this month yet.
        </p>
    </div>
</template>
