<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import { Bug } from 'lucide-vue-next';
import { onDebug } from '@/config.js';
import PersonalInfo from './Partials/PersonalInfo.vue';
import Security from './Partials/Security.vue';
import LinkedChildren from './Partials/LinkedChildren.vue';
import Preferences from './Partials/Preferences.vue';
import SchoolContact from './Partials/SchoolContact.vue';
import Logout from './Partials/Logout.vue';
import PreferencesModal from './Modals/PreferencesModal.vue';

defineProps({
    linkedStudents: { type: Array, default: () => [] },
    defaultRelationship: { type: String, default: 'Guardian' },
    relationshipOptions: { type: Array, default: () => ['Parent', 'Guardian'] },
    preferences: { type: Array, default: () => [] },
    school: { type: Object, default: null },
});

const showPreferencesModal = ref(false);

function reloadLinked() {
    setTimeout(() => router.reload({ only: ['linkedStudents'] }), 1200);
}
</script>

<template>
    <Head title="Settings" />

    <AppLayout page-title="Settings">
        <template #content>
            <div class="flex flex-col gap-5 w-full">
                <PersonalInfo />
                <Security />
                <LinkedChildren
                    :students="linkedStudents"
                    :default-relationship="defaultRelationship"
                    :relationship-options="relationshipOptions"
                    @linked="reloadLinked"
                />
                <Preferences :preferences="preferences" @open="showPreferencesModal = true" />

                <SchoolContact v-if="school" :school="school" />

                <Logout />

                <SettingsCard v-if="onDebug" label="Native Sandbox" :icon="Bug" :action="() => router.get('/native-sandbox')" />
                <SettingsCard v-if="onDebug" label="Debug Logs" :icon="Bug" :action="() => router.get('/debug/logs')" />
                <SettingsCard v-if="onDebug" label="Debug Extensions" :icon="Bug" :action="() => router.get('/debug-extensions')" />
            </div>
        </template>

        <template #modal>
            <PreferencesModal v-model="showPreferencesModal" :preferences="preferences" />
        </template>
    </AppLayout>
</template>
