<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { BellRing } from 'lucide-vue-next';
import BottomModal from '@/Components/Modal/BottomModal.vue';
import SlidingSwitch from '@/Components/Button/SlidingSwitch.vue';
import { darkMode } from '@/Composables/useDarkMode';
import { notificationOptInState, runNotificationOptIn } from '@/Composables/usePushPriming';
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


const deviceNotify = ref('unasked'); 
const enabling = ref(false);

async function refreshDeviceNotify() {
    deviceNotify.value = await notificationOptInState();
}

watch(() => props.modelValue, (visible) => {
    if (visible) refreshDeviceNotify();
}, { immediate: true });

async function enableDeviceNotify() {
    enabling.value = true;
    try {
        const result = await runNotificationOptIn();
        await refreshDeviceNotify();
        if (result !== 'granted') {
            toast.show('If nothing appeared, enable notifications for TextBitz Gate in your phone settings.');
        }
    } finally {
        enabling.value = false;
    }
}

const notificationLabels = {
    arrival: 'Arrival',
    departure: 'Departure',
    late_alert: 'Late & absence alerts',
    weekly_summary: 'Weekly summary',
};

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
            <section
                v-if="['unasked', 'dismissed', 'denied'].includes(deviceNotify)"
                class="flex items-start gap-3 rounded-xl bg-blue-50 dark:bg-blue-900/30 p-4"
            >
                <BellRing class="w-5 h-5 shrink-0 text-blue-600 dark:text-blue-300 mt-0.5" />
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ deviceNotify === 'denied' ? 'Notifications are turned off' : 'Notifications are not enabled' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ deviceNotify === 'denied'
                            ? 'Enable them for TextBitz Gate in your phone settings to get alerts.'
                            : 'Turn them on to be alerted when your child taps in or out.' }}
                    </p>
                    <button
                        v-if="deviceNotify !== 'denied'"
                        type="button"
                        :disabled="enabling"
                        class="mt-2 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                        @click="enableDeviceNotify"
                    >
                        {{ enabling ? 'Opening…' : 'Enable notifications' }}
                    </button>
                </div>
            </section>

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
