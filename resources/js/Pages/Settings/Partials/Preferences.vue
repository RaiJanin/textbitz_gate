<script setup>
import { computed } from 'vue';
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import BoxIcon from '@/Components/Icon/BoxIcon.vue';
import LabeledName from '@/Components/Details/LabeledName.vue';
import { Palette, Moon, Bell } from 'lucide-vue-next';
import { darkMode } from '@/Composables/useDarkMode';

const props = defineProps({
    preferences: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['open']);

const notificationSummary = computed(() => {
    const guardian = props.preferences.find((preference) => preference.role === 'guardian');

    if (!guardian) {
        return 'Off';
    }

    const enabled = ['arrival', 'departure', 'late_alert', 'weekly_summary']
        .filter((key) => guardian[key]).length;

    if (enabled === 0) return 'Off';
    if (enabled === 4) return 'All on';

    return `${enabled} of 4 on`;
});
</script>

<template>
    <SettingsCard label="Preferences" :icon="Palette" :action="() => emit('open')">
        <div class="flex flex-row items-center gap-4 px-1 border-b border-gray-200 dark:border-gray-500 pb-4">
            <BoxIcon :icon="Moon" />
            <LabeledName label="Dark Mode" :context="darkMode" />
        </div>
        <div class="flex flex-row items-center gap-4 px-1">
            <BoxIcon :icon="Bell" />
            <LabeledName label="Notifications" :context="notificationSummary" />
        </div>
    </SettingsCard>
</template>
