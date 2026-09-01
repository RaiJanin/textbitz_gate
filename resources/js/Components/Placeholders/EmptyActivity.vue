<script setup>
import { Activity } from 'lucide-vue-next';
</script>

<template>
    <div class="flex flex-col items-center gap-3 py-10 text-center">
        <Activity :size="40" class="text-blue-300" />
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
            No gate activity yet. You'll see arrivals and dismissals here as they happen.
        </p>
    </div>
</template>
