<script setup>
import { BellOff } from 'lucide-vue-next';
</script>

<template>
    <div class="flex flex-col items-center gap-3 py-10 text-center">
        <BellOff :size="40" class="text-blue-300" />
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
            Nothing to flag right now — no late arrivals, absences or summaries.
        </p>
    </div>
</template>
