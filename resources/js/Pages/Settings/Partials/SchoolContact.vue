<script setup>
import { Building2, Phone, AtSign } from 'lucide-vue-next';
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import BoxIcon from '@/Components/Icon/BoxIcon.vue';
import LabeledName from '@/Components/Details/LabeledName.vue';

defineProps({
    school: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <SettingsCard label="School contact" :icon="Building2">
        <div class="flex flex-row items-center gap-4 px-1 border-b border-gray-200 dark:border-gray-500 pb-4">
            <BoxIcon :icon="Building2" />
            <LabeledName label="School" :context="school.name" />
        </div>
        <div class="flex flex-row items-center gap-4 px-1 border-b border-gray-200 dark:border-gray-500 pb-4">
            <BoxIcon :icon="Phone" />
            <LabeledName label="Phone" :context="school.contact_phone ?? '—'" />
        </div>
        <div class="flex flex-row items-center gap-4 px-1">
            <BoxIcon :icon="AtSign" />
            <LabeledName label="Email" :context="school.contact_email ?? '—'" />
        </div>
    </SettingsCard>
</template>
