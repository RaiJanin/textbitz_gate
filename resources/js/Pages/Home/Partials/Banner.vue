<script setup>
import { computed } from 'vue';
import { BellRing, Zap, ZapOff } from 'lucide-vue-next';
import { usePage, router } from '@inertiajs/vue3';
import { useServerConnectivity } from '@/Composables/useServerConnectivity';

const props = defineProps({
    studentCount: {
        type: Number,
        default: 0,
    },
    status: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const user = page.props.auth.user;
const isOnline = useServerConnectivity()

const firstName = computed(() => (user?.name ?? '').split(' ')[0] || 'there');

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';

    return 'Good evening';
});

const helper = computed(() =>
    props.studentCount > 0
        ? "Here's your children's day at the gate — see who's in, review history, and fine-tune alerts."
        : 'Link a child with the code from your school to start seeing arrivals and dismissals here.',
);
</script>

<template>
    <!-- <section
        class="relative overflow-hidden rounded-2xl p-5 text-white
               bg-gradient-to-tr from-blue-600 via-blue-600 to-amber-500/70
               dark:from-gray-800 dark:via-gray-800 dark:to-blue-500/30"
    >
        <div class="relative z-10 flex items-start gap-4">
            <span class="rounded-xl bg-white/20 p-2.5 shrink-0">
                <BellRing :size="22" :stroke-width="2.5" />
            </span>
            <div>
                <h1 class="text-2xl font-bold leading-tight">{{ greeting }}, {{ firstName }}</h1>
                <p class="mt-1 text-sm text-white/85">{{ helper }}</p>
            </div>
        </div>
        <BellRing
            :size="120"
            class="pointer-events-none absolute -right-4 -bottom-6 text-white/10"
            :stroke-width="1.5"
        />
    </section> -->
    <div class="bg-gradient-to-br from-blue-600 via-blue-500 to-amber-500/40 p-4 rounded-2xl">
        <h1 class="font-bold text-white text-3xl">{{ greeting }}, {{ firstName }}</h1>
        <div class="flex justify-between mt-2">
            <div class="flex flex-col gap-4">
                <small v-if="!status?.stale" class="flex-1 max-w-full text-white rounded-full bg-white/20 flex flex-row gap-2 items-center py-1 px-2">
                    <Zap :size="16"/>
                    Live updates
                </small>
                <small v-else class="flex-1 max-w-full text-white rounded-full bg-white/20 flex flex-row gap-2 items-center py-1 px-2">
                    <ZapOff :size="16"/>
                    Offline (showing latest synced)
                </small>
                <div class="flex flex-col">
                    <small class="text-white/90">
                        {{ helper }}
                    </small>
                </div>
                <button @click="router.get(route('app.history'))" class="flex self-start ml-2 text-sm text-blue-600 px-3 py-2 rounded-xl bg-white/80 backdrop-blur-md shadow">
                    See Daily Records
                </button>
            </div>
            <i class="flex self-end text-white/90">
                <BellRing :size="120" :stroke-width="2.5"/>
            </i>
        </div>
    </div>
</template>
