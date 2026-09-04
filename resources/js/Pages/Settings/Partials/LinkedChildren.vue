<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { UsersRound } from 'lucide-vue-next';
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import crossPlatformToast from '@/helpers/crossPlatformToast';

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    defaultRelationship: {
        type: String,
        default: 'Guardian',
    },
    relationshipOptions: {
        type: Array,
        default: () => ['Parent', 'Guardian'],
    },
});

const emit = defineEmits(['linked']);

const toast = crossPlatformToast();

const form = useForm({
    code: '',
    relationship: props.defaultRelationship,
});

const submit = () => {
    if (!form.code.trim()) return;

    form
        .transform((data) => ({ code: data.code.trim(), relationship: data.relationship }))
        .post(route('app.link.request'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('code');
                toast.success('Link request submitted');
                emit('linked');
            },
            onError: () => toast.error('Could not submit that link code'),
        });
}

const changeRelationship = (student, relationship) => {
    if (relationship === student.relationship) return;

    router.put(
        route('app.students.relationship', { remoteId: student.remote_id }),
        { relationship },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => toast.success('Relationship updated'),
            onError: () => toast.error('Could not update relationship'),
        },
    );
}
</script>

<template>
    <SettingsCard label="Linked children" :icon="UsersRound">
        <ul class="divide-y divide-gray-100 dark:divide-gray-600">
            <li
                v-for="student in students"
                :key="student.remote_id"
                class="py-2 flex items-center justify-between gap-3 text-sm"
            >
                <span class="text-gray-800 dark:text-gray-100 truncate">
                    {{ student.full_name }}
                    <span class="text-gray-400">· {{ student.grade }}-{{ student.section }}</span>
                </span>

                <select
                    :value="student.relationship ?? defaultRelationship"
                    class="shrink-0 rounded-lg text-gray-800 dark:text-gray-200 border-gray-300 dark:bg-gray-900 dark:border-gray-600 text-xs py-1"
                    @change="changeRelationship(student, $event.target.value)"
                >
                    <option v-for="opt in relationshipOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
            </li>
            <li v-if="!students.length" class="py-2 text-sm text-gray-400">None linked yet.</li>
        </ul>

        <form class="flex flex-col gap-2 pt-2" @submit.prevent="submit">
            <div class="flex flex-wrap gap-2">
                <input
                    v-model="form.code"
                    placeholder="School-issued link code"
                    class="flex-1 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600 text-gray-400 text-sm"
                />
                <select
                    v-model="form.relationship"
                    class="rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600 text-gray-800 dark:text-gray-200 text-sm"
                >
                    <option v-for="opt in relationshipOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <div class="flex flex-1 justify-start w-full">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-8 py-2 rounded-lg bg-blue-600 text-white text-sm disabled:opacity-50"
                    >
                        Link
                    </button>
                </div>
            </div>
            <p v-if="form.errors.code" class="text-xs text-rose-500">{{ form.errors.code }}</p>
            <p v-if="form.errors.relationship" class="text-xs text-rose-500">{{ form.errors.relationship }}</p>
        </form>
    </SettingsCard>
</template>
