<script setup>
import NavLinks from '@/Components/Button/NavLinks.vue';
import { House, Settings, CalendarDays, Bell } from 'lucide-vue-next';
</script>

<template>
    <footer class="sm:hidden fixed w-full bottom-0 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border-t border-gray-200 dark:border-gray-600 z-50">
        <div class="flex flex-wrap justify-between px-4">
            <NavLinks :pageLink="route('app.home')" routeName="app.home">
                <House :size="24" />
            </NavLinks>
            <NavLinks :pageLink="route('app.history')" routeName="app.history">
                <CalendarDays :size="24" />
            </NavLinks>
            <NavLinks :pageLink="route('app.alerts')" routeName="app.alerts">
                <Bell :size="24" />
            </NavLinks>
            <NavLinks :pageLink="route('app.settings')" routeName="app.settings">
                <Settings :size="24" />
            </NavLinks>
        </div>
    </footer>
</template>
