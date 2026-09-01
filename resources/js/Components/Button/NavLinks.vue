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
        class="flex items-center justify-center rounded-lg pt-4 px-2 pb-6" 
        :class="[isCurrentScreen ? 'text-blue-600 dark:text-blue-500' : 'text-gray-500 dark:text-gray-300']"
    >
        <i class="text-2xl" :class="iconClass">
            <slot></slot>
        </i>
    </Link>
</template>