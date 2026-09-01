<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyStudents from '@/Components/Placeholders/EmptyStudents.vue';
import { useStudentPicker } from '@/Composables/useStudentPicker';
import { getTapState } from '@/services/useTapChannelManager';
import { fetchStudentStatus } from '@/data/api/fetchViaAxios';
import Banner from './Partials/Banner.vue';
import ChildSwitcher from './Partials/ChildSwitcher.vue';
import StatusHero from './Partials/StatusHero.vue';
import Timeline from './Partials/Timeline.vue';
import RecentActivity from './Partials/RecentActivity.vue';

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
});

const status = ref(null);
const loading = ref(false);
const tapState = getTapState();

const { selectedId, selectedStudent, select } = useStudentPicker(
    () => props.students,
    loadStatus,
);

async function loadStatus(remoteId = selectedId.value) {
    if (!remoteId) return;
    status.value = null;
    loading.value = true;
    try {
        status.value = await fetchStudentStatus(remoteId);
    } finally {
        loading.value = false;
    }
}

// A realtime tap updates the channel store; re-fetch when it does.
const liveTick = computed(() => tapState.byStudent[selectedId.value]?.lastEventAt);
watch(liveTick, (at) => {
    if (at) loadStatus().catch(() => {});
});
</script>

<template>
    <Head title="Home" />

    <AppLayout page-title="TextBitz Gate" additional-text="Attendance at a glance">
        <template #content>
            <div class="flex flex-col gap-4 min-h-[80dvh]">
                <Banner :student-count="students.length" />

                <ChildSwitcher
                    v-if="students.length > 1"
                    :students="students"
                    :selected-id="selectedId"
                    @select="select"
                />

                <EmptyStudents v-if="!students.length" />

                <template v-else>
                    <StatusHero :student="selectedStudent" :status="status" />
                    <Timeline :timeline="status?.timeline ?? []" :loading="loading" />
                    <RecentActivity
                        :remote-id="selectedId"
                        :student-name="selectedStudent?.full_name ?? ''"
                        :refresh-key="liveTick"
                    />
                </template>
            </div>
        </template>
    </AppLayout>
</template>
