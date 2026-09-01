<script setup>
import { ref } from 'vue';
import RedLinkButton from '@/Components/Button/RedLinkButton.vue';
import { LogOut } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Breeze/Modal.vue';
import DangerButton from '@/Components/Breeze/DangerButton.vue';
import SecondaryButton from '@/Components/Breeze/SecondaryButton.vue';

const showConfirmModal = ref(false)
const form = useForm({})

const openModal = () => {
    showConfirmModal.value = true
}

const closeModal = () => {
    showConfirmModal.value = false
}

const logout = () => {
    form.post(route('logout')), {
        preserveScroll: true,
    }
}
</script>

<template>
    <RedLinkButton :action="() => openModal()">
        <p class="p-2 text-lg font-semibold justify-center flex flex-wrap gap-4">
            <LogOut :size="28"/> Log Out
        </p>
    </RedLinkButton>
    <Modal :show="showConfirmModal" @close="closeModal">
        <div class="p-6">
            <h2
                class="text-lg font-medium text-gray-900 dark:text-gray-100"
            >
                Are you sure you want to logout?
            </h2>
            <div class="mt-6 flex gap-2">
                <DangerButton
                    @click="logout"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Logout
                </DangerButton>
                <SecondaryButton @click="closeModal">
                    Cancel
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>