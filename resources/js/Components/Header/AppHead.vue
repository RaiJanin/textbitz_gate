<script setup>
import { MessageSquareShare } from 'lucide-vue-next';

defineProps({
    headerName: {
        type: String
    },
    additionalText: {
        type: String,
        default: ''
    },
    headButtonAction: {
        type: Function,
        default: () => {}
    },
    headButtonIcon: {
        type: Object,
        dafault: null
    },
    headButtonText: {
        type: String,
        deafault: ''
    }
})
</script>

<template>
    <header class="fixed w-full flex items-center justify-between backdrop-blur-lg bg-gradient-to-tr from-blue-600/90 dark:from-gray-800/90 from-0% via-blue-600/90 dark:via-gray-800/90 via-50% to-amber-600/20 dark:to-blue-500/20 to-100% p-4 pt-12 z-50 sm:mt-0 overflow-hidden">
        <div 
            class="absolute inset-0 bg-no-repeat bg-[position:right_30px_top_0px] pointer-events-none"
            style="
                background-image: url('/images/textbitz_icon_transparent.png'); 
                background-size: 120px;
                mask-image: linear-gradient(to right, rgba(255,255,255,0.50) 20%, transparent 100%);
                -webkit-mask-image: linear-gradient(to right, rgba(255,255,255,0.50) 50%, transparent 100%);
            "
        ></div>
        <div class="relative z-10 flex items-center flex-wrap gap-2">
            <div class="rounded-xl text-gray-100 bg-white/20 dark:bg-blue-500/80 flex item-center justify-center p-[10px]">
                <MessageSquareShare :size="18" :stroke-width="3"/>
            </div>
            <div class="flex flex-col">
                <h1 class="text-xl font-bold text-gray-100">{{ headerName }}</h1>
                <p v-if="additionalText !== ''" class="text-xs text-gray-200">{{ additionalText }}</p>
            </div>
        </div>
        <button v-if="headButtonIcon" @click="headButtonAction" class="relative z-10 rounded-xl p-2 bg-gray-200/90">
            <component :is="headButtonIcon" :size="24" class="text-blue-500"/>
            <p v-if="headButtonText !== ''">{{ headButtonText }}</p>
        </button>
    </header>
</template>