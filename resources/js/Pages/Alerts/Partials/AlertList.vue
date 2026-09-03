<script setup>
import { watch } from 'vue';
import { useInfiniteScroll } from '@/Composables/useInfiniteScroll';
import { fetchStudentAlerts } from '@/data/api/fetchViaAxios';
import DetailsCardColList from '@/Components/Skeleton/DetailsCardColList.vue';
import EmptyAlerts from '@/Components/Placeholders/EmptyAlerts.vue';
import AlertCard from './AlertCard.vue';

const props = defineProps({
    remoteId: {
        type: [Number, null],
        default: null,
    },
});

const { items, loading, done, error, sentinelRef, reset } = useInfiniteScroll(
    async (page) => {
        const response = await fetchStudentAlerts(props.remoteId, { page });

        return { data: response.alerts ?? [], hasMore: !!response.has_more };
    },
    { immediate: false },
);

watch(
    () => props.remoteId,
    (id) => {
        if (id) reset();
    },
    { immediate: true },
);
</script>

<template>
    <div class="flex flex-col gap-2">
        <ul v-if="items.length" class="flex flex-col gap-2">
            <AlertCard v-for="(alert, i) in items" :key="i" :alert="alert" />
        </ul>

        <div v-if="loading" class="py-4 flex justify-center">
            <DetailsCardColList />
        </div>

        <p v-else-if="error" class="py-3 text-center text-sm text-rose-500">
            Couldn't load alerts. Try again when you're back online.
        </p>

        <EmptyAlerts v-else-if="!items.length" />

        <p v-else-if="done" class="py-3 text-center text-xs text-gray-400">
            You're all caught up.
        </p>

        <!-- Infinite-scroll sentinel -->
        <div ref="sentinelRef" class="h-px" aria-hidden="true" />
    </div>
</template>
