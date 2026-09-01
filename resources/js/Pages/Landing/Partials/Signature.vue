<script setup>
import { MessageSquareShare } from 'lucide-vue-next';
</script>

<template>
    <div class="hidden lg:flex lg:w-[46%] relative overflow-hidden bg-blue-500 items-center justify-center px-12">
        <!-- ambient texture -->
        <div class="absolute inset-0"
                style="background-image: radial-gradient(circle at 1px 1px, #8891B0 1px, transparent 0); background-size: 28px 28px;">
        </div>
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-blue-100/30 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-72 h-72 rounded-full bg-amber-500/20 blur-3xl"></div>

        <div class="relative z-10 max-w-sm w-full">
            <div class="flex items-center gap-2 mb-14">
                <div class="p-2 rounded-xl bg-blue-400 flex items-center justify-center">
                    <MessageSquareShare :size="25" class="text-gray-100" stroke-width="2.5" />
                </div>
                <span class=" text-lg font-bold tracking-tight text-[#EDEFF7]">TextBitz</span>
            </div>

            <!-- Phone mockup: one message forking out to many delivered recipients -->
            <div class="relative h-[340px]">
                <!-- phone frame -->
                <div class="absolute left-0 top-0 w-[180px] h-[340px] rounded-[28px] border-4 border-blue-300 bg-gray-200 shadow-2xl py-2 px-4 flex flex-col gap-2">
                    <div class="w-10 h-1 rounded-full bg-[#2A3355]/40 mx-auto mb-4"></div>
                    <div class="self-end max-w-[85%] rounded-2xl rounded-tr-sm bg-amber-500/70 text-gray-800 text-xs font-medium px-3 py-2 [animation:fadeUp_0.5s_ease_0.2s_forwards]">
                        Reminder: your order ships tomorrow 📦
                    </div>
                    <div class="self-end text-[9px] text-gray-600 [animation:fadeUp_0.5s_ease_0.4s_forwards]">
                        Sending to 128 contacts…
                    </div>
                </div>

                <!-- fork lines + recipient stack -->
                <svg class="absolute left-[170px] top-[40px] w-[140px] h-[220px] overflow-visible" viewBox="0 0 140 220">
                    <path d="M0,20 C 50,20 40,40 90,40" fill="none" stroke="#f5b507" stroke-width="1.5" />
                    <path d="M0,20 C 50,90 40,100 90,100" fill="none" stroke="#f5b507" stroke-width="1.5" />
                    <path d="M0,20 C 50,160 40,160 90,160" fill="none" stroke="#f5b507" stroke-width="1.5" />
                </svg>

                <div class="absolute left-[290px] top-[60px] flex flex-col gap-[38px]">
                    <div v-for="(delay, i) in ['0.7s', '0.95s', '1.2s']" :key="i"
                            class="names flex items-center gap-2 opacity-0"
                            :style="{ animationDelay: delay }">
                        <div class="w-8 h-8 rounded-full bg-white border border-white flex items-center justify-center text-[10px] font-semibold text-gray-800">
                            {{ ['JD', 'MC', 'AR'][i] }}
                        </div>
                        <div class="w-4 h-4 rounded-full bg-blue-200/25 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-gray-300 text-sm leading-relaxed">
                One message, hundreds of contacts. Built for the way small businesses in the Philippines actually send.
            </p>
        </div>
    </div>
</template>

<style scoped>
.names {
    animation:fadeIn 0.4s ease forwards;
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