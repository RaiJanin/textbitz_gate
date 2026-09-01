<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyStudents from '@/Components/Placeholders/EmptyStudents.vue';
import { useStudentPicker } from '@/Composables/useStudentPicker';
import StudentSelect from './Partials/StudentSelect.vue';
import AlertList from './Partials/AlertList.vue';

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
});

const { selectedId, select } = useStudentPicker(() => props.students);
</script>

<template>
    <Head title="Alerts" />

    <AppLayout page-title="Alerts" additional-text="Late arrivals, absences, summaries">
        <template #content>
            <div class="flex flex-col gap-4 min-h-[80dvh]">
                <StudentSelect :students="students" :selected-id="selectedId" @select="select" />

                <EmptyStudents v-if="!students.length" />

                <AlertList v-else :remote-id="selectedId" />
            </div>
        </template>
    </AppLayout>
</template>
