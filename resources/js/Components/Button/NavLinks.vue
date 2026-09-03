<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pageLink: { 
        type: String, 
        required: true 
    },
    iconClass: { 
        type: String, 
        default: '' 
    },
    routeName: { 
        type: String, 
        required: true
    }
})

const isCurrentScreen = computed(() => route().current(props.routeName))
</script>

<template>
    <Link :href="pageLink"
        class="flex items-center justify-center rounded-xl py-2 px-4" 
        :class="[isCurrentScreen ? 'text-blue-600 dark:text-blue-500 bg-blue-200/50' : 'text-gray-500 dark:text-gray-300']"
    >
        <i class="text-2xl" :class="iconClass">
            <slot></slot>
        </i>
    </Link>
</template>