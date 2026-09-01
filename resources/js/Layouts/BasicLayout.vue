<script setup>

</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            
            <!-- Page Heading -->
            <header
                class="bg-white shadow dark:bg-gray-800 pt-10"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
