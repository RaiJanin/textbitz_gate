<script setup>
import { computed } from 'vue';
import { BellRing } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    studentCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const user = page.props.auth.user;

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
    <section
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
    </section>
</template>
