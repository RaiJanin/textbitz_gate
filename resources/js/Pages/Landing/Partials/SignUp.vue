<script setup>
import { Mail, Phone, User } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import TextbitzBrandmark from '@/Components/TextbitzBrandmark.vue';
import AuthInputLabel from '@/Components/Input/AuthInputLabel.vue';
import InputError from '@/Components/Breeze/InputError.vue';
import AuthTextInput from '@/Components/Input/AuthTextInput.vue';
import AuthPasswordInput from '@/Components/Input/AuthPasswordInput.vue';
import LargeSubmitButton from '@/Components/Button/LargeSubmitButton.vue';
import AuthSwitchPrompt from '@/Components/Button/AuthSwitchPrompt.vue';
import { usePhoneFormatter, normalizePhone, stripSpaces } from '@/Composables/usePHPhoneFormatter';

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: ''
})

const { error: phoneFormatError, handlePhoneInput } = usePhoneFormatter(form, 'phone_number')

const emit = defineEmits(['switchScreen'])

const submit = () => {
    form.phone_number = normalizePhone(stripSpaces(form.phone_number))

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation')
    })
}
</script>

<template>
    <div class="flex flex-col w-full max-w-lg gap-8">
        <header class="flex justify-center items-center gap-3">
            <TextbitzBrandmark />
        </header>
        <section class="flex flex-col gap-1 w-full">
            <h1 class="flex items-start text-[28px] font-bold">Create an account</h1>
            <p class="text-gray-500">Start monitoring your student</p>
        </section>
        <form @submit.prevent="submit" class="flex flex-col gap-2 w-full">
            <div>
                <AuthInputLabel label-for="Full Name" />
                <AuthTextInput 
                    id="name"
                    v-model="form.name"
                    autofocus
                    autocomplete="name"
                    placeholder="<Full Name>"
                    :icon="User"
                />
                <InputError :message="form.errors.name"/>
            </div>

            <div>
                <AuthInputLabel label-for="Email"/>
                <AuthTextInput 
                    id="email"
                    type="email"
                    v-model="form.email"
                    autocomplete="username"
                    placeholder="you@email.com"
                    :icon="Mail"
                />
                <InputError :message="form.errors.email"/>
            </div>

            <div>
                <AuthInputLabel label-for="Phone Number"/>
                <AuthTextInput
                    id="phone_number"
                    type="tel"
                    inputmode="numeric"
                    :model-value="form.phone_number"
                    @input="handlePhoneInput"
                    autocomplete="tel"
                    placeholder="+63 9** *** ****"
                    :icon="Phone"
                />
                <InputError :message="phoneFormatError || form.errors.phone_number"/>
            </div>

            <div>
                <AuthInputLabel label-for="Password"/>
                <AuthPasswordInput 
                    id="password"
                    v-model="form.password"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password"/>
            </div>

            <div>
                <AuthInputLabel label-for="Confirm Password"/>
                <AuthPasswordInput 
                    id="password"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation"/>
            </div>

            <LargeSubmitButton :disabled="form.processing">
                {{ form.processing ? 'Creating account…' : 'Create account' }}
            </LargeSubmitButton>

            <AuthSwitchPrompt 
                question="Already have an account?"
                action-text="Log in"
                @switch-screen="emit('switchScreen')"
            />
        </form>
    </div>
</template>