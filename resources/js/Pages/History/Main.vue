<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyStudents from '@/Components/Placeholders/EmptyStudents.vue';
import { useStudentPicker } from '@/Composables/useStudentPicker';
import { fetchStudentHistory } from '@/data/api/fetchViaAxios';
import StudentMonthControls from './Partials/StudentMonthControls.vue';
import MonthCalendar from './Partials/MonthCalendar.vue';
import DailyRecords from './Partials/DailyRecords.vue';

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
});

const month = ref(new Date().toISOString().slice(0, 7));
const days = ref([]);
const loading = ref(false);

const { selectedId, select } = useStudentPicker(() => props.students, load);

async function load(remoteId = selectedId.value) {
    if (!remoteId) return;
    loading.value = true;
    try {
        const response = await fetchStudentHistory(remoteId, { month: month.value });
        days.value = response.days ?? [];
    } finally {
        loading.value = false;
    }
}

function changeMonth(value) {
    month.value = value;
    load();
}
</script>

<template>
    <Head title="History" />

    <AppLayout page-title="History" additional-text="Monthly attendance">
        <template #content>
            <div class="flex flex-col gap-4 min-h-[80dvh]">
                <StudentMonthControls
                    :students="students"
                    :selected-id="selectedId"
                    :month="month"
                    @select="select"
                    @update:month="changeMonth"
                />

                <EmptyStudents v-if="!students.length" />

                <template v-else>
                    <MonthCalendar :days="days" :loading="loading"/>
                    <DailyRecords :days="days" :loading="loading" />
                </template>
            </div>
        </template>
    </AppLayout>
</template>
