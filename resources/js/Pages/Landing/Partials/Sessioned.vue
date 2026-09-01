<script setup>
import { MessageSquareShare } from 'lucide-vue-next'
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    redirectTo: {
        type: String,
        default: '/dashboard'
    }
})

onMounted(() => {
    setTimeout(() => {
        router.visit(props.redirectTo, { replace: true })
    }, 1500)
})
</script>

<template>
    <div class="flex flex-col gap-10">
        <div class="flex flex-row items-center justify-center gap-4">
            <div class="session-icon rounded-2xl bg-blue-500 text-gray-100 flex items-center justify-center p-3">
                <MessageSquareShare :size="25" :stroke-width="2.5" />
            </div>
            <p class="session-text text-gray-800 font-bold dark:text-gray-400 text-lg">
                TextBitz
            </p>
        </div>
        <div class="relative w-[240px] h-[260px] mx-auto">
            <div class="absolute left-0 top-0 w-[140px] h-[260px] rounded-[24px] border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-3 flex flex-col gap-2">
                <div class="w-8 h-1 rounded-full bg-gray-300 dark:bg-gray-700 mx-auto mb-1"></div>
                <div class="bubble self-end max-w-[85%] rounded-2xl rounded-tr-sm bg-amber-400 text-gray-900 text-[11px] font-medium px-2.5 py-2">
                    Welcome back 👋
                </div>
                <div class="sending-text self-end text-[8px] text-gray-400 font-mono">
                    Composing app…
                </div>
            </div>

            <svg class="absolute left-[130px] top-[30px] w-[100px] h-[160px] overflow-visible" viewBox="0 0 100 160">
                <path class="fork-line text-gray-300 dark:text-gray-700" d="M0,15 C 35,15 30,30 65,30" fill="none" stroke="currentColor" stroke-width="1.5" pathLength="1" />
                <path class="fork-line fork-line-2 text-gray-300 dark:text-gray-700" d="M0,15 C 35,65 30,70 65,70" fill="none" stroke="currentColor" stroke-width="1.5" pathLength="1" />
                <path class="fork-line fork-line-3 text-gray-300 dark:text-gray-700" d="M0,15 C 35,115 30,115 65,115" fill="none" stroke="currentColor" stroke-width="1.5" pathLength="1" />
            </svg>

            <div class="absolute left-[210px] top-[40px] flex flex-col gap-[24px]">
                <div v-for="(label, i) in ['You']" :key="i" class="avatar-pop flex items-center gap-1.5">
                    <div class="w-7 h-7 rounded-full bg-blue-500 flex items-center justify-center text-[9px] font-semibold text-white">
                        {{ label }}
                    </div>
                    <div class="dot w-3.5 h-3.5 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                    </div>
                </div>
            </div>
        </div>
        <p class="session-text w-full text-center text-gray-600 dark:text-gray-400 text-lg">
            Please wait...
        </p>
    </div>
</template>

<style scoped>
.bubble {
    opacity: 0;
    animation: fadeUp 0.4s ease-out 0.1s both;
}

.sending-text {
    opacity: 0;
    animation: fadeIn 0.3s ease-out 0.5s both;
}

.fork-line {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
    animation: drawLine 0.4s ease-out 0.7s forwards;
}

.avatar-pop {
    opacity: 0;
    animation: popIn 0.35s ease-out 1.1s both;
}

@keyframes fadeInText {
    from { opacity: 0; }
    to { opacity: 1; }
}

.session-icon {
    animation: popIn 0.4s ease-out both;
}

.session-text {
    animation: fadeInText 0.4s ease-out 0.3s both;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes drawLine {
    from { stroke-dashoffset: 1; }
    to { stroke-dashoffset: 0; }
}

@keyframes popIn {
    from { opacity: 0; transform: scale(0.7); }
    to { opacity: 1; transform: scale(1); }
}
</style>