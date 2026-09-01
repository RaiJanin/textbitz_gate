<script setup>
import { useForm } from '@inertiajs/vue3';
import BottomModal from '@/Components/Modal/BottomModal.vue';
import SlidingSwitch from '@/Components/Button/SlidingSwitch.vue';
import { darkMode } from '@/Composables/useDarkMode';
import settings from '@/data/settings';
import crossPlatformToast from '@/helpers/crossPlatformToast';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    preferences: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

const toast = crossPlatformToast();
const themeChoices = settings.darkModeChoices;

const notificationLabels = {
    arrival: 'Arrival',
    departure: 'Departure',
    late_alert: 'Late & absence alerts',
    weekly_summary: 'Weekly summary',
};

// One Inertia form per role, seeded from the server-rendered preferences.
const forms = Object.fromEntries(
    props.preferences.map((preference) => [
        preference.role,
        useForm({
            role: preference.role,
            arrival: preference.arrival,
            departure: preference.departure,
            late_alert: preference.late_alert,
            weekly_summary: preference.weekly_summary,
        }),
    ]),
);

function pickTheme(choice) {
    // Persisted ref — its watcher saves to localStorage and re-applies the theme.
    darkMode.value = choice;
}

function setNotification(role, key, value) {
    const form = forms[role];
    form[key] = value;

    form.put(route('app.preferences.update'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success('Preference saved'),
        onError: () => {
            form[key] = !value;
            toast.error('Could not save — will retry when back online');
        },
    });
}
</script>

<template>
    <BottomModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
        <div class="flex flex-col gap-8">
            <section>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Dark mode</h3>
                <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                    <button
                        v-for="choice in themeChoices"
                        :key="choice"
                        type="button"
                        class="px-4 py-2 text-sm transition-colors"
                        :class="darkMode === choice
                            ? 'bg-blue-600 text-white'
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
                        @click="pickTheme(choice)"
                    >
                        {{ choice }}
                    </button>
                </div>
            </section>

            <section v-for="(form, role) in forms" :key="role">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 capitalize">
                    {{ role }} notifications
                </h3>
                <div class="flex flex-col gap-4">
                    <div
                        v-for="(label, key) in notificationLabels"
                        :key="key"
                        class="flex items-center justify-between"
                    >
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ label }}</span>
                        <SlidingSwitch
                            :model-value="form[key]"
                            @update:model-value="(value) => setNotification(role, key, value)"
                        />
                    </div>
                </div>
            </section>
        </div>
    </BottomModal>
</template>
