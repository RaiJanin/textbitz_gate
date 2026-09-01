<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SideNavigations from '@/Components/Navigations/SideNavigations.vue';


</script>

<template>
    <aside class="hidden sm:flex sm:flex-col left-0 top-0 h-full w-full bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border-r border-gray-200 dark:border-gray-600 z-50">
        <header class="h-40">
            
        </header>
        <div class="flex w-[40dvh] px-4">
            <SideNavigations />
        </div>
    </aside>
</template>