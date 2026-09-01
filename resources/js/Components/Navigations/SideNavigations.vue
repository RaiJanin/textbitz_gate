<script setup>
import { Link } from '@inertiajs/vue3';
import { House, Settings, CalendarDays, Bell } from 'lucide-vue-next';

const items = [
    { label: 'Home', routeName: 'app.home', icon: House },
    { label: 'History', routeName: 'app.history', icon: CalendarDays },
    { label: 'Alerts', routeName: 'app.alerts', icon: Bell },
    { label: 'Settings', routeName: 'app.settings', icon: Settings },
];
</script>

<template>
    <div class="flex flex-col items-stretch gap-2 py-6 px-4 w-full">
        <Link
            v-for="item in items"
            :key="item.routeName"
            :href="route(item.routeName)"
            class="w-full px-4 py-2 flex flex-row gap-4 items-center rounded-lg"
            :class="route().current(item.routeName) ? 'text-blue-600 bg-blue-100 dark:bg-blue-900/40' : 'text-gray-600 dark:text-gray-300'"
        >
            <component :is="item.icon" :size="22" />
            {{ item.label }}
        </Link>
    </div>
</template>
