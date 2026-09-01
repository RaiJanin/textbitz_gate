<script setup>
import { Transition } from 'vue';

defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    positionClass: {
        type: String,
        default: 'flex flex-wrap items-center'
    },
    justifyWhere: {
        default: 'justify-end'
    },
    width: {
        default: 'w-[90%]'
    },
    bottomPos: {
        default: 'bottom-[15%]'
    }
})
</script>

<template>
    <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
    >
        <div v-show="isOpen" 
            class="fixed mt-2 bg-white dark:bg-gray-800 dark:border-gray-600 rounded-xl shadow-lg border border-gray-200 transition-all duration-400 ease-in-out"
            :class="[positionClass, justifyWhere, width, bottomPos]"
        >
            <slot />
        </div>
    </Transition>
</template>