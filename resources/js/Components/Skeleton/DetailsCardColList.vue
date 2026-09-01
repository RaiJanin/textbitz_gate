<script setup>

</script>

<template>
    <div class="w-full backdrop-blur rounded-xl border bg-white/70 dark:bg-gray-500/40 border-gray-100 dark:border-gray-600 shadow-sm p-2 transition-all duration-300 ease-in-out">
        <header class="w-full flex justify-start mt-2">
            <div class="animate-pulse-fast [animation-delay:0ms] flex flex-row gap-2 p-2 bg-gray-300 dark:bg-gray-500 w-10 rounded-full items-center">
                <div></div>
            </div>
        </header>
        <article class="px-2 mt-1 mb-2">
            <div class="animate-pulse-fast [animation-delay:200ms] flex-1 p-2 bg-gray-300 dark:bg-gray-500 w-40 rounded-full"></div>
        </article>
        <section class="flex flex-col gap-2 p-2">
            <div class="animate-pulse-fast [animation-delay:100ms] flex-1 p-2 bg-gray-300 dark:bg-gray-500 w-80 rounded-full"></div>
            <div class="animate-pulse-fast [animation-delay:200ms] flex-1 p-2 bg-gray-300 dark:bg-gray-500 w-40 rounded-full"></div>
            <div></div>
        </section>
        <section class="flex flex-wrap gap-4 px-4 py-2 border-t-[0.25px] border-gray-200 w-full">
            <div class="animate-pulse-fast [animation-delay:300ms] p-2 rounded-full bg-gray-300 dark:bg-gray-500 w-24 "></div>
            <div class="animate-pulse-fast [animation-delay:400ms] p-2 rounded-full bg-gray-300 dark:bg-gray-500 w-20"></div>
        </section>
    </div>
</template>