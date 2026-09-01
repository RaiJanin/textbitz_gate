<script setup>
import SettingsCard from '@/Components/Card/SettingsCard.vue';
import BoxIcon from '@/Components/Icon/BoxIcon.vue';
import { User, AtSign } from 'lucide-vue-next';
import LabeledName from '@/Components/Details/LabeledName.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage()
const user = page.props.auth.user
</script>

<template>
    <SettingsCard label="Personal Info" :icon="User">
        <div class="flex flex-row items-center gap-4 px-4 border-b border-gray-200 dark:border-gray-500 pb-4">
            <BoxIcon :icon="User"/>
            <LabeledName 
                label="Full Name"
                :context="user.name"
            />
        </div>
        <div class="flex flex-row items-center gap-4 px-4">
            <BoxIcon :icon="AtSign"/>
            <LabeledName 
                label="Email"
                :context="user.email"
            />
        </div>
    </SettingsCard>
</template>