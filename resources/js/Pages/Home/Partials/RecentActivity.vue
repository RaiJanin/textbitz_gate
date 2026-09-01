<script setup>
import { ref, watch, computed } from 'vue';
import { ArrowDownLeft, ArrowUpRight } from 'lucide-vue-next';
import { fetchStudentHistory } from '@/data/api/fetchViaAxios';
import { friendlyDate } from '@/helpers/date';
import EmptyActivity from '@/Components/Placeholders/EmptyActivity.vue';
import SmallCardColList from '@/Components/Skeleton/SmallCardColList.vue';

const props = defineProps({
    remoteId: {
        type: [Number, null],
        default: null,
    },
    studentName: {
        type: String,
        default: '',
    },
    // Bumped by the parent whenever a realtime tap lands, to force a refresh.
    refreshKey: {
        type: [Number, String, null],
        default: null,
    },
});

const days = ref([]);
const loading = ref(false);

async function load() {
    if (!props.remoteId) return;
    loading.value = true;
    try {
        const response = await fetchStudentHistory(props.remoteId);
        days.value = response.days ?? [];
    } finally {
        loading.value = false;
    }
}

const events = computed(() =>
    days.value
        .flatMap((day) => (day.taps ?? []).map((tap) => ({ ...tap, date: day.date })))
        .sort((a, b) => `${b.date} ${b.at}`.localeCompare(`${a.date} ${a.at}`))
        .slice(0, 6),
);

const firstName = computed(() => props.studentName.split(' ')[0] || props.studentName);

watch(() => [props.remoteId, props.refreshKey], () => load().catch(() => {}), { immediate: true });
</script>

<template>
    <section class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Recent activity</h2>

        <div v-if="loading" class="space-y-2">
            <SmallCardColList v-for="n in 3" :key="n" />
        </div>

        <EmptyActivity v-else-if="!events.length" />

        <ul v-else class="space-y-3">
            <li v-for="(event, i) in events" :key="i" class="flex items-start gap-3">
                <span
                    class="mt-0.5 rounded-lg p-1.5"
                    :class="event.direction === 'in'
                        ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40'
                        : 'bg-blue-100 text-blue-600 dark:bg-blue-900/40'"
                >
                    <component :is="event.direction === 'in' ? ArrowDownLeft : ArrowUpRight" :size="16" />
                </span>
                <div class="text-sm">
                    <p class="text-gray-800 dark:text-gray-100">
                        {{ firstName }} tapped {{ event.direction === 'in' ? 'IN' : 'OUT' }}
                        at {{ event.gate ?? 'the gate' }} — {{ event.at }}
                        <span v-if="event.is_late" class="text-amber-600">· late</span>
                    </p>
                    <p class="text-xs text-gray-400">{{ friendlyDate('shortDate', event.date) }}</p>
                </div>
            </li>
        </ul>
    </section>
</template>
