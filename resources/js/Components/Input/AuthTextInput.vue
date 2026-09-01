<script setup>
defineProps({
    id: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'text'
    },
    modelValue: {
        type: [String, Number],
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    autofocus: {
        type: Boolean,
        default: false
    },
    autocomplete: {
        type: String,
        default: ''
    },
    icon: {
        type: Object,
        default: null
    },
    iconClass: {
        type: String,
        default: ''
    },
    inputmode: {
        type: String,
        default: ''
    }
})

const model = defineModel({
    type: String,
    required: true
})
</script>

<template>
    <div class="relative gap-2">
        <component v-if="icon" :is="icon" :size="24" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
        <i v-else :class="iconClass" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input
            :id="id"
            :type="type"
            v-model="model"
            :autofocus="autofocus"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :inputmode="inputmode"
            class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-[#E5E7EB] bg-white dark:bg-gray-800 text-lg text-gray-600 dark:text-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-all"
        />
    </div>
</template>