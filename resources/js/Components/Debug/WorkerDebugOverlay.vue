<script setup>
import { ref, computed } from 'vue';
import { useWorkerDebug, runScheduler, processQueue, retryFailed, clearWorkerLog } from '@/Composables/useWorkerDebug';

const w = useWorkerDebug();
const expanded = ref(false);

function ago(iso) {
    if (!iso) return '—';
    const s = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
    if (s < 60) return `${s}s`;
    if (s < 3600) return `${Math.round(s / 60)}m`;
    return `${Math.round(s / 3600)}h`;
}

const schedAge = computed(() => {
    const times = w.scheduler.tasks.map((t) => t.at).filter(Boolean).sort();
    return times.length ? ago(times[times.length - 1]) : '—';
});
const queueBad = computed(() => (w.queue.failed || 0) > 0 || (w.queue.pending || 0) > 0);
</script>

<template>
    <Teleport to="body">
        <div v-if="w.enabled" class="wrk-dbg" :class="{ open: expanded }">
            <button v-if="!expanded" class="pill" @click="expanded = true">
                <span>⚙ {{ schedAge }}</span>
                <span class="sep">·</span>
                <span :class="{ warn: queueBad }">q {{ w.queue.pending ?? '?' }}/{{ w.queue.failed ?? '?' }}</span>
                <span class="sep">·</span>
                <span :class="w.connectivity.online ? 'ok' : 'warn'">{{ w.connectivity.online ? 'online' : 'offline' }}</span>
            </button>

            <div v-else class="panel">
                <div class="head">
                    <strong>Workers</strong>
                    <span class="poll">polled {{ ago(w.lastPolledAt) }} ago</span>
                    <button class="x" @click="expanded = false">–</button>
                </div>

                <div class="sub">scheduler</div>
                <div v-for="t in w.scheduler.tasks" :key="t.task" class="line">
                    <span :class="t.ok ? 'ok' : 'warn'">{{ t.ok ? '✓' : '✗' }}</span>
                    <span class="name">{{ t.task }}</span>
                    <span class="dim">{{ ago(t.at) }} ago<span v-if="t.runtime_ms"> · {{ t.runtime_ms }}ms</span><span v-if="t.skipped"> · skipped</span></span>
                </div>
                <div v-if="!w.scheduler.tasks.length" class="dim">no runs recorded — is schedule:work running?</div>

                <div class="sub">queue</div>
                <div class="kv"><span>pending</span><code :class="{ warn: (w.queue.pending || 0) > 0 }">{{ w.queue.pending ?? '?' }}</code></div>
                <div class="kv"><span>failed</span><code :class="{ warn: (w.queue.failed || 0) > 0 }">{{ w.queue.failed ?? '?' }}</code></div>
                <div class="kv"><span>processed</span><code>{{ w.queue.processed_ok }} ok / {{ w.queue.processed_fail }} fail</code></div>
                <div class="kv"><span>oldest pending</span><code>{{ w.queue.oldest_pending_at ? ago(w.queue.oldest_pending_at) + ' ago' : '—' }}</code></div>

                <div v-if="w.queue.recent.length" class="sub">recent jobs</div>
                <div v-for="(j, i) in w.queue.recent.slice(0, 6)" :key="i" class="line">
                    <span :class="j.ok ? 'ok' : 'warn'">{{ j.ok ? '✓' : '✗' }}</span>
                    <span class="name">{{ j.job }}</span>
                    <span class="dim">{{ ago(j.at) }} ago</span>
                    <span v-if="j.error" class="err">{{ j.error }}</span>
                </div>

                <div class="sub">lifecycle (mobile)</div>
                <div class="kv"><span>foregrounded</span><code>{{ w.lifecycle?.last_foreground_at ? ago(w.lifecycle.last_foreground_at) + ' ago' : '—' }}</code></div>
                <div class="kv"><span>backgrounded</span><code>{{ w.lifecycle?.last_background_at ? ago(w.lifecycle.last_background_at) + ' ago' : '—' }}</code></div>

                <div class="sub">last sync</div>
                <div class="kv"><span>at</span><code>{{ w.sync?.at ? ago(w.sync.at) + ' ago' : '—' }}</code></div>
                <div class="kv"><span>changes</span><code>{{ (w.sync?.new_taps ?? 0) }} new / {{ (w.sync?.updated_taps ?? 0) }} upd</code></div>

                <div class="btns">
                    <button :disabled="!!w.busy" @click="runScheduler()">{{ w.busy === 'scheduler' ? '…' : 'run sched' }}</button>
                    <button :disabled="!!w.busy" @click="processQueue()">{{ w.busy === 'queue' ? '…' : 'run queue' }}</button>
                </div>
                <div class="btns">
                    <button :disabled="!!w.busy" @click="retryFailed()">retry failed</button>
                    <button :disabled="!!w.busy" @click="clearWorkerLog()">clear</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.wrk-dbg {
    position: fixed;
    right: 8px;
    top: 76px;
    z-index: 2147483000;
    font: 11px/1.35 ui-monospace, SFMono-Regular, Menlo, monospace;
    color: #e5e7eb;
    opacity: 0.9;
}
.pill {
    display: flex; 
    align-items: center; 
    gap: 5px;
    background: rgba(17, 24, 39, 0.92); 
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 999px; 
    padding: 5px 10px; 
    color: inherit; 
    backdrop-filter: blur(4px);
}
.pill .sep { 
    opacity: 0.4; 
}
.panel {
    width: min(84vw, 320px); max-height: 62vh; overflow: auto;
    background: rgba(17, 24, 39, 0.96); border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 12px; padding: 10px; backdrop-filter: blur(6px);
}
.head { 
    display: flex; 
    align-items: center; 
    gap: 6px; 
    margin-bottom: 6px; 
}
.head .poll { 
    color: #6b7280; 
    margin-left: auto; 
}
.head .x { 
    background: transparent; 
    border: 0; 
    color: #9ca3af;
     font-size: 16px; 
     line-height: 1; 
     padding: 0 4px; 
}
.sub { 
    color: #6b7280; 
    text-transform: uppercase; 
    letter-spacing: 0.05em; 
    margin: 8px 0 3px; 
    font-size: 10px; 
}
.line { 
    display: flex; 
    gap: 5px; 
    align-items: baseline; 
    flex-wrap: wrap; 
}
.line .name { 
    color: #93c5fd; 
}
.line .dim, .dim { 
    color: #6b7280; 
}
.line .err { 
    color: #fca5a5; 
    width: 100%; 
    padding-left: 14px; 
}
.kv { 
    display: flex; 
    gap: 8px; 
}
.kv > span { 
    color: #9ca3af; 
    min-width: 96px; 
}
.kv code { 
    color: #d1d5db; 
}
.ok { 
    color: #34d399; 
}
.warn, code.warn { 
    color: #fbbf24; 
}
.btns { 
    display: flex; 
    gap: 6px; 
    margin-top: 6px; 
}
.btns button {
    flex: 1; 
    background: rgba(255, 255, 255, 0.08); 
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: #e5e7eb; 
    border-radius: 7px; 
    padding: 5px 0;
}
.btns button:disabled { 
    opacity: 0.5; 
}
</style>
