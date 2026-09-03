<script setup>
import { ref, computed, Transition } from 'vue';
import { Head } from '@inertiajs/vue3';
import LogIn from './Partials/LogIn.vue';
import SignUp from './Partials/SignUp.vue';
import Signature from './Partials/Signature.vue';
import Sessioned from './Partials/Sessioned.vue';

const props = defineProps({
    status: {
        type: String,
        default: null
    },
    canResetPassword: {
        type: Boolean,
        default: true
    },
    showSignUp: {
        type: Boolean,
        default: false
    }
})

const toggleAuth = ref(props.showSignUp)
const direction = ref('forward')
const transitionName = computed(() => direction.value === 'forward' ? 'slide-left' : 'slide-right')

const switchScreen = () => {
    direction.value = toggleAuth.value ? 'back' : 'forward'
    toggleAuth.value = !toggleAuth.value
}
</script>

<template>
    <Head title="Welcome" />
    <div class="w-full flex bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-300 overflow-hidden min-h-screen">
        <Signature />
        <div class="flex-1 flex items-center justify-center px-6 overflow-hidden">
            <img
                id="background"
                class="fixed -left-80 -top-60 max-w-[600px]"
                src="/images/background/background-blue.svg"
            />
            <img
                id="background"
                class="fixed -right-80 -bottom-60 max-w-[600px]"
                src="/images/background/background-blue.svg"
            />
            <Sessioned v-if="$page.props.auth.user"/>
            <Transition v-else :name="transitionName" mode="out-in" class="z-10">
                <LogIn 
                    v-if="!toggleAuth"
                    :status="status"
                    :can-reset-password="canResetPassword"
                    @switch-screen="switchScreen"
                    key="login"
                />
                <SignUp 
                    v-else
                    @switch-screen="switchScreen"
                    key="register"
                />
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
    inset: 0;
}

.slide-left-enter-from {
    transform: translateX(40px);
    opacity: 0;
}
.slide-left-leave-to {
    transform: translateX(-40px);
    opacity: 0;
}
.slide-right-enter-from {
    transform: translateX(-40px);
    opacity: 0;
}
.slide-right-leave-to {
    transform: translateX(40px);
    opacity: 0;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
 
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>