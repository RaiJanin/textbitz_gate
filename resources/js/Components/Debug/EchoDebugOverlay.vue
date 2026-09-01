<script setup>
import { ref, computed } from 'vue';
import { useEchoDebug, toggleEchoDebug, reconnectEcho, clearEchoDebugEvents } from '@/Composables/useEchoDebug';

const debug = useEchoDebug();
const expanded = ref(false);

const dotClass = computed(() => ({
    connected: 'bg-emerald-400',
    connecting: 'bg-amber-400',
    unavailable: 'bg-amber-400',
    initializing: 'bg-gray-400',
    initialized: 'bg-gray-400',
    disconnected: 'bg-rose-400',
    failed: 'bg-rose-400',
}[debug.connection] ?? 'bg-gray-400'));

function fmt(t) {
    return new Date(t).toLocaleTimeString('en-US', { hour12: false });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="debug.enabled" class="echo-dbg" :class="{ open: expanded }">
            <!-- collapsed pill -->
            <button v-if="!expanded" class="pill" @click="expanded = true">
                <span class="dot" :class="dotClass" />
                <span>{{ debug.connection }}</span>
                <span class="sep">·</span>
                <span>⚡{{ debug.events.length }}</span>
                <span v-if="debug.reconnects" class="sep">·</span>
                <span v-if="debug.reconnects">↻{{ debug.reconnects }}</span>
                <span v-if="debug.authRedirects" class="warn">· 401×{{ debug.authRedirects }}</span>
            </button>

            <!-- expanded panel -->
            <div v-else class="panel">
                <div class="row head">
                    <button class="dot" :class="dotClass" @click="expanded = false"/>
                    <strong>Echo · {{ debug.connection }}</strong>
                    <button class="x" @click="expanded = false">–</button>
                </div>

                <div class="kv"><span>target</span><code>{{ debug.target || '—' }}</code></div>
                <div class="kv"><span>socket</span><code>{{ debug.socketId || '—' }}</code></div>
                <div class="kv"><span>reconnects</span><code>{{ debug.reconnects }}</code></div>
                <div class="kv"><span>auth redirects</span><code :class="{ warn: debug.authRedirects }">{{ debug.authRedirects ?? '?' }}</code></div>
                <div class="kv"><span>last sync</span><code>{{ debug.lastSyncAt ? fmt(debug.lastSyncAt) : '—' }}</code></div>
                <div v-if="debug.lastError" class="kv err"><span>error</span><code>{{ debug.lastError }}</code></div>

                <div class="sub">channels ({{ debug.channels.length }})</div>
                <div class="chans">
                    <div v-for="c in debug.channels" :key="c.name" class="chan">
                        <span :class="c.subscribed ? 'ok' : 'pend'">{{ c.subscribed ? '✓' : '…' }}</span>
                        {{ c.name }}
                    </div>
                    <div v-if="!debug.channels.length" class="muted">no channels</div>
                </div>

                <div class="sub">events</div>
                <div class="log">
                    <div v-for="(e, i) in debug.events" :key="i" class="ev">
                        <span class="t">{{ fmt(e.t) }}</span>
                        <span class="ch">{{ e.channel }}</span>
                        <span class="en">{{ e.event }}</span>
                        <span v-if="e.preview" class="pv">{{ e.preview }}</span>
                    </div>
                    <div v-if="!debug.events.length" class="muted">nothing yet</div>
                </div>

                <div class="btns">
                    <button @click="reconnectEcho()">reconnect</button>
                    <button @click="clearEchoDebugEvents()">clear</button>
                    <button @click="toggleEchoDebug(); expanded = false">hide</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.echo-dbg {
    position: fixed;
    left: 8px;
    top: 76px;
    z-index: 2147483000;
    font: 11px/1.35 ui-monospace, SFMono-Regular, Menlo, monospace;
    color: #e5e7eb;
    opacity: 0.9;
}
.dot { 
    width: 8px; 
    height: 8px; 
    border-radius: 999px; 
    display: inline-block; 
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
.pill .warn, code.warn, .warn { 
    color: #fca5a5; 
}
.panel {
    width: min(84vw, 340px);
    max-height: 60vh;
    overflow: auto;
    background: rgba(17, 24, 39, 0.96); 
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 12px; 
    padding: 10px; 
    backdrop-filter: blur(6px);
}
.row.head { 
    display: flex; 
    justify-content: start;
    align-items: center; 
    gap: 6px; 
    margin-bottom: 8px; 
}
.row.head .x { 
    margin-left: auto; 
    background: transparent; 
    border: 0; 
    color: #9ca3af; 
    font-size: 16px; 
    line-height: 1; 
    padding: 0 4px; 
}
.kv { 
    display: flex; 
    gap: 8px; 
}
.kv > span { 
    color: #9ca3af; 
    min-width: 92px; 
}
.kv code { 
    color: #d1d5db; 
    word-break: break-all; 
}
.kv.err code { 
    color: #fca5a5; 
}
.sub { 
    color: #6b7280;
    text-transform: uppercase; 
    letter-spacing: 0.05em; 
    margin: 8px 0 3px; 
    font-size: 10px; 
}
.chans .chan { 
    display: flex; 
    gap: 5px; 
}
.chans .ok { 
    color: #34d399; 
}
.chans .pend { 
    color: #fbbf24; 
}
.log { 
    display: flex; 
    flex-direction: column; 
    gap: 2px; 
}
.ev { 
    display: flex; 
    gap: 5px; 
    white-space: nowrap; 
    overflow: hidden; 
}
.ev .t { 
    color: #6b7280; 
}
.ev .ch { 
    color: #93c5fd; 
}
.ev .en { 
    color: #fde68a; 
}
.ev .pv { 
    color: #9ca3af; 
    overflow: hidden; 
    text-overflow: ellipsis; 
}
.muted { 
    color: #6b7280; 
}
.btns { 
    display: flex;
    gap: 6px; 
    margin-top: 8px; 
}
.btns button {
    flex: 1; 
    background: rgba(255, 255, 255, 0.08); 
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: #e5e7eb; 
    border-radius: 7px; 
    padding: 5px 0;
}
</style>
