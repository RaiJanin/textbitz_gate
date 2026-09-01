<script setup>
import { ref } from 'vue';
import { Lock, EyeOff, Eye } from 'lucide-vue-next';

const props = defineProps({
    id: {
        type: String,
        default: 'password'
    },
    autocomplete: {
        type: String,
        default: 'password'
    },
    placeholder: {
        type: String,
        default: '••••••••'
    },
    icon: {
        type: Object,
        default:() => Lock
    }
})

const model = defineModel({
    type: String,
    required: true
})

const showPassword = ref(false)
</script>

<template>
    <div class="relative">
        <component :is="icon" :size="24" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF]" />
        <input
            :id="id"
            :type="showPassword ? 'text' : 'password'"
            v-model="model"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-[#E5E7EB] bg-white dark:bg-gray-800 text-lg text-gray-600 dark:text-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] hover:text-[#6B7280]"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
        >
            <EyeOff v-if="showPassword" :size="22" />
            <Eye v-else :size="22" />
        </button>
    </div>
</template>