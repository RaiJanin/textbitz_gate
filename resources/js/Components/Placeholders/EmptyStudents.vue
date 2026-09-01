<script setup>
import { Link } from '@inertiajs/vue3';
import Placeholder from '@/Components/Placeholder.vue';
import { UsersRound } from 'lucide-vue-next';
</script>

<template>
    <div class="flex flex-col items-center gap-8 py-16">
        <Placeholder
            :icon="UsersRound"
            :icon-size="120"
            title="No children linked yet"
            message="Add a child using the link code your school gave you at enrollment, then their gate taps show up here."
        />
        <Link
            :href="route('app.settings')"
            class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium"
        >
            Add a child
        </Link>
    </div>
</template>
