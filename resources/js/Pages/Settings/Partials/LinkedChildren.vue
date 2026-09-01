<script setup>
import { useForm } from '@inertiajs/vue3';
import { UsersRound } from 'lucide-vue-next';
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import crossPlatformToast from '@/helpers/crossPlatformToast';

defineProps({
    students: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['linked']);

const toast = crossPlatformToast();

const form = useForm({
    code: '',
});

function submit() {
    if (!form.code.trim()) return;

    form
        .transform((data) => ({ code: data.code.trim() }))
        .post(route('app.link.request'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                toast.success('Link request submitted');
                emit('linked');
            },
            onError: () => toast.error('Could not submit that link code'),
        });
}
</script>

<template>
    <SettingsCard label="Linked children" :icon="UsersRound">
        <ul class="divide-y divide-gray-100 dark:divide-gray-600">
            <li v-for="student in students" :key="student.remote_id" class="py-2 flex justify-between text-sm">
                <span class="text-gray-800 dark:text-gray-100">{{ student.full_name }}</span>
                <span class="text-gray-400">
                    {{ student.relationship ?? 'Guardian' }} · {{ student.grade }}-{{ student.section }}
                </span>
            </li>
            <li v-if="!students.length" class="py-2 text-sm text-gray-400">None linked yet.</li>
        </ul>

        <form class="flex gap-2" @submit.prevent="submit">
            <input
                v-model="form.code"
                placeholder="School-issued link code"
                class="flex-1 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600 text-sm"
            />
            <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm disabled:opacity-50"
            >
                Link
            </button>
        </form>
        <p v-if="form.errors.code" class="text-xs text-rose-500">{{ form.errors.code }}</p>
    </SettingsCard>
</template>
