<script setup>
import NavLinks from '@/Components/Button/NavLinks.vue';
import { House, Settings, CalendarDays, Bell } from 'lucide-vue-next';
</script>

<template>
    <footer class="sm:hidden fixed w-full flex justify-items-center bottom-4 z-50">
        <section class="w-full rounded-2xl mx-4 bg-white/90 dark:bg-gray-800/90 shadow-md backdrop-blur-xl border border-gray-200 dark:border-gray-600 z-50">
            <div class="flex flex-wrap justify-between px-4 pt-2 pb-4">
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
        </section>
    </footer>
</template>
