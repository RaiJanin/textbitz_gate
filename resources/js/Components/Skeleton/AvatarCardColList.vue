<script setup>

</script>

<template>
    <div class="w-full backdrop-blur rounded-xl bg-white/70 dark:bg-gray-500/40 border border-gray-100 dark:border-gray-600 shadow-sm p-4">
        <div class="flex flex-wrap gap-3 max-w-full">
            <div class="animate-pulse-fast [animation-delay:0ms] bg-gray-300 dark:bg-gray-500 rounded-full text-xl flex items-center justify-center p-1 w-12 h-12 font-semibold">
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <div class="animate-pulse-fast [animation-delay:100ms] text-lg font-semibold bg-gray-300 dark:bg-gray-500 w-40 p-3 rounded-xl"></div>
                <div class="animate-pulse-fast [animation-delay:200ms] bg-gray-300 dark:bg-gray-500 w-32 rounded-lg p-2"></div>
                <div class="animate-pulse-fast [animation-delay:300ms] flex flex-wrap gap-2 mt-1">
                    <span v-for="i in 3" :key="i" class="w-10 rounded-full p-2 bg-gray-300 dark:bg-gray-500">
                    </span>
                </div>
            </div>
            <div class="animate-pulse-fast [animation-delay:400ms] bg-gray-300 dark:bg-gray-500 mt-2 w-6 h-1 p-1 rounded-full">
            </div>
        </div>
    </div>
</template>