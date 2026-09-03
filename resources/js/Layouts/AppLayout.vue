<script setup>
import FooterNav from '@/Components/Footer/FooterNav.vue';
import AppHead from '@/Components/Header/AppHead.vue';
import Sidebar from './Sidebar.vue';
import Toast from '@/Components/Toast.vue';
import EchoDebugOverlay from '@/Components/Debug/EchoDebugOverlay.vue';
import WorkerDebugOverlay from '@/Components/Debug/WorkerDebugOverlay.vue';
import NotificationOptInModal from '@/Components/Modal/NotificationOptInModal.vue';

defineProps({
    pageTitle: {
        type: String,
        default: ''
    },
    additionalText: {
        type: String,
        default: ''
    },
    headButtonAction: {
        type: Function,
        default: () => {}
    },
    headButtonIcon: {
        type: Object,
        default: null
    },
    headButtonText: {
        type: String,
        default: ''
    },
})
</script>

<template>
    <main class="bg-gray-100 dark:bg-gray-900 grid grid-cols-1 md:grid-cols-4 min-h-screen">
        <Sidebar />
        <EchoDebugOverlay />
        <WorkerDebugOverlay />
        <div class="col-span-3 flex flex-col h-screen overflow-hidden">
            <AppHead 
                :header-name="pageTitle" 
                :additional-text="additionalText" 
                :head-button-action="headButtonAction"
                :head-button-icon="headButtonIcon"
                :head-button-text="headButtonText"
            />

            <section class="flex-1 flex-col gap-2 overflow-y-auto px-4 max-w-4xl mt-10">
                <div class="py-12"></div>
                <slot name="content" />
                <div class="py-12"></div>
                <Toast />
            </section>

            <slot name="modal" />

            <FooterNav />
        </div>

        <NotificationOptInModal />
    </main>
</template>