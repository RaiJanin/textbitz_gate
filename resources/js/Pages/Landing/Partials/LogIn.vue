<script setup>
import { Phone } from 'lucide-vue-next';
import { useForm, Link } from '@inertiajs/vue3';
import { onDemo } from '@/config';
import TextbitzBrandmark from '@/Components/TextbitzBrandmark.vue';
import InputError from '@/Components/Breeze/InputError.vue';
import AuthInputLabel from '@/Components/Input/AuthInputLabel.vue';
import AuthTextInput from '@/Components/Input/AuthTextInput.vue';
import AuthPasswordInput from '@/Components/Input/AuthPasswordInput.vue';
import Checkbox from '@/Components/Breeze/Checkbox.vue';
import LargeSubmitButton from '@/Components/Button/LargeSubmitButton.vue';
import AuthSwitchPrompt from '@/Components/Button/AuthSwitchPrompt.vue';
import { usePhoneFormatter, normalizePhone, stripSpaces } from '@/Composables/usePHPhoneFormatter';

defineProps({
    status: {
        type: String,
        default: null
    },
    canResetPassword: {
        type: Boolean,
        default: true
    }
})

const form = useForm({
    phone_number: '',
    password: '',
    // On by default: a mobile app should not drop you back to login when the
    // webview reloads or the session cookie is evicted — the remember cookie
    // re-authenticates.
    remember: true
})

const { error: phoneFormatError, handlePhoneInput } = usePhoneFormatter(form, 'phone_number')

const emit = defineEmits(['switchScreen'])

const submit = () => {
    form.phone_number = normalizePhone(stripSpaces(form.phone_number))

    form.post(route('login'), {
        onFinish: () => form.reset('password')
    })
}
</script>

<template>
    <div class="flex flex-col w-full max-w-lg gap-8">
        <header class="flex justify-center items-center gap-3">
            <TextbitzBrandmark />
        </header>
        <section class="flex flex-col gap-1 w-full">
            <h1 class="flex items-start text-[28px] font-bold">Welcome Back</h1>
            <p class="text-gray-500">Log in to monitor your studens</p>
        </section>
        <form @submit.prevent="submit" class="flex flex-col gap-2 w-full">
            <div>
                <AuthInputLabel label-for="Phone Number"/>
                <AuthTextInput 
                    id="phone_number"
                    type="tel"
                    inputmode="number"
                    v-model="form.phone_number"
                    autofocus
                    autocomplete="tel"
                    placeholder="09xxxxxxxxx"
                    :icon="Phone"
                    @input="handlePhoneInput"
                />
                <InputError :message="form.errors.phone_number"/>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <AuthInputLabel label-for="Password"/>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs font-medium text-amber-600 hover:opacity-75">
                        Forgot password?
                    </Link>
                </div>
                <AuthPasswordInput 
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.password"/>
            </div>

            <label class="flex items-center gap-2 cursor-pointer select-none">
                <Checkbox v-model:checked="form.remember"/>
                <span class="text-sm text-[#6B7280]">Remember me</span>
            </label>

            <div v-if="onDemo" class="w-full flex flex-col justify-start rounded-lg p-2 bg-gray-400/30">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">For demo purposes</p>
                <small class="text-xs text-gray-600 dark:text-gray-400">Phone Number: 09171234567</small>
                <small class="text-xs text-gray-600 dark:text-gray-400">Password: password</small>
            </div>

            <LargeSubmitButton :disabled="form.processing">
                {{ form.processing ? 'Logging in…' : 'Log in' }}
            </LargeSubmitButton>

            <AuthSwitchPrompt 
                question="New to TextBitz?"
                action-text="Create an account"
                @switch-screen="emit('switchScreen')"
            />
        </form>
    </div>
</template>