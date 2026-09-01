<script setup>
import { router } from '@inertiajs/vue3';
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import { ShieldUser } from 'lucide-vue-next';
</script>

<template>
    <SettingsCard label="Security" :icon="ShieldUser" :action="() => router.get(route('app.profile.edit'))" />
</template>
