<script setup>
import { ref, onMounted } from 'vue'
import { Bell } from 'lucide-vue-next'
import BottomModal from '@/Components/Modal/BottomModal.vue'
import {
    shouldOfferNotificationOptIn,
    runNotificationOptIn,
    dismissNotificationOptIn,
} from '@/Composables/usePushPriming'

const open = ref(false)
const working = ref(false)

onMounted(async () => {
    if (await shouldOfferNotificationOptIn()) {
        // Let the first screen settle before interrupting.
        setTimeout(() => {
            open.value = true
        }, 700)
    }
})

async function enable() {
    working.value = true
    try {
        await runNotificationOptIn() // <-- the native push priming happens here
    } finally {
        working.value = false
        open.value = false
    }
}

function notNow() {
    dismissNotificationOptIn()
    open.value = false
}
</script>

<template>
    <BottomModal v-model="open">
        <div class="flex flex-col items-center text-center gap-4 pb-2">
            <div class="rounded-2xl bg-blue-50 dark:bg-blue-900/30 p-4">
                <Bell class="w-7 h-7 text-blue-600 dark:text-blue-300" />
            </div>

            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Get attendance alerts
            </h2>

            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                Turn on notifications so you know the moment your child taps in or
                out, arrives late, or is marked absent.
            </p>

            <button
                type="button"
                :disabled="working"
                class="mt-2 w-full rounded-xl bg-blue-600 py-3 text-white text-sm font-semibold transition-opacity disabled:opacity-60"
                @click="enable"
            >
                {{ working ? 'Opening…' : 'Enable notifications' }}
            </button>

            <button type="button" class="py-1 text-sm text-gray-400" @click="notNow">
                Not now
            </button>
        </div>
    </BottomModal>
</template>
