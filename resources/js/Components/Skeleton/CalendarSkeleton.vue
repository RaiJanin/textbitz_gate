<script>

</script>

<template>
    <div class="grid grid-cols-7 gap-1.5">
        <div
            v-for="day in 31"
            :key="day"
            class="animate-pulse-fast [animation-delay:100ms] aspect-square rounded flex items-center justify-center p-4 bg-gray-300 dark:bg-gray-500"
        >
        </div>
    </div>
</template>