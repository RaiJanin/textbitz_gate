<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, onBeforeUnmount, computed } from 'vue';
import { useLocalNotifications } from '@/Composables/useLocalNotifications';

const notif = useLocalNotifications().state;

const props = defineProps({
    platform: { type: Object, default: () => ({ isAndroid: false, isIos: false }) },
    nativeLoaded: { type: Boolean, default: false },
    hasBridgeFn: { type: Boolean, default: false },
});

const importState = reactive({ path: null, ok: false, error: null, exports: [] });

async function loadNative() {
    try {
        return { mod: await import('#nativephp'), path: '#nativephp' };
    } catch (e) {
        importState.error = String(e?.message ?? e);
        return { mod: null, path: null };
    }
}
const results = reactive({});
const events = ref([]);
const phpChecks = ref([]);

let native = null;
let offHandlers = [];

const isMobile = computed(() => props.platform.isAndroid || props.platform.isIos);

function verdict(r) {
    if (!r) return '';
    if (r.status === 'running') return '…';
    if (r.status === 'fail') return '❌';
    if (r.value === null || r.value === undefined || r.value === false) return '⚠️';
    return '✅';
}

async function run(name, fn) {
    results[name] = { status: 'running' };
    const t0 = performance.now();
    try {
        const value = await fn();
        results[name] = { status: 'ok', value, ms: Math.round(performance.now() - t0) };
    } catch (e) {
        results[name] = { status: 'fail', error: String(e?.message ?? e), ms: Math.round(performance.now() - t0) };
    }
}

function need() {
    if (!native) throw new Error('#nativephp not loaded');
    return native;
}

// ---- individual native calls -------------------------------------------------
const tests = {
    'system.isMobile()': () => need().system.isMobile(),
    'system.isAndroid()': () => need().system.isAndroid(),
    'system.isIos()': () => need().system.isIos(),
    'device.getInfo()': () => need().device.getInfo(),
    'device.getBatteryInfo()': () => need().device.getBatteryInfo(),
    'device.getId()': () => need().device.getId(),
    'pushNotifications.checkPermission()  [FCM]': () => need().pushNotifications?.checkPermission?.() ?? 'plugin absent',
    'pushNotifications.getToken()  [FCM]': () => need().pushNotifications?.getToken?.() ?? 'plugin absent',
    'Notification.permission  [fallback]': () => (typeof Notification !== 'undefined' ? Notification.permission : 'unsupported'),
    'navigator.serviceWorker ready  [fallback]': async () =>
        'serviceWorker' in navigator ? !!(await navigator.serviceWorker.getRegistration('/notification-sw.js')) : 'unsupported',
};

const sideEffectTests = {
    "dialog.toast('…')": () => need().dialog.toast('Native sandbox toast ✔'),
    'dialog.alert(…, buttons)': () =>
        need().dialog.alert('Native sandbox', 'Tap a button — the choice shows in the event log.', ['Got it', 'Cancel'], 'sandbox-alert'),
    'device.vibrate()': () => need().device.vibrate(),
    "bridgeCall('System.OpenAppSettings')  [denied-perm fallback]": () =>
        need().bridgeCall('System.OpenAppSettings', {}),
    'openAppNotificationSettings()  [full fallback chain]': async () => {
        const { openAppNotificationSettings } = await import('@/Composables/usePushPriming');
        return openAppNotificationSettings();
    },
    'Notification.requestPermission()  ⚠ OS prompt': async () => {
        if (typeof Notification === 'undefined') return 'unsupported';
        return Notification.requestPermission();
    },
    'show a local notification': async () => {
        const { notify } = await import('@/Composables/useLocalNotifications');
        return notify({ title: 'TextBitz Gate', body: 'Local notification test ✔', tag: 'sandbox' });
    },
};

async function runAllSafe() {
    for (const [name, fn] of Object.entries(tests)) {
        // eslint-disable-next-line no-await-in-loop
        await run(name, fn);
    }
}

async function runPhpProbe() {
    try {
        const { data } = await window.axios.get('/debug/native/probe');
        phpChecks.value = data.checks ?? [];
    } catch (e) {
        phpChecks.value = [{ name: 'request failed', ok: false, error: String(e) }];
    }
}

function logEvent(name, payload) {
    events.value.unshift({ t: new Date().toLocaleTimeString('en-US', { hour12: false }), name, payload: JSON.stringify(payload).slice(0, 200) });
    if (events.value.length > 30) events.value.length = 30;
}

onMounted(async () => {
    const { mod, path } = await loadNative();
    if (mod) {
        native = mod;
        importState.path = path;
        importState.ok = true;
        importState.exports = Object.keys(mod).sort();
    }

    if (native?.on && native?.Events) {
        const wire = (evt) => {
            const handler = (p) => logEvent(evt, p);
            native.on(evt, handler);
            offHandlers.push([evt, handler]);
        };
        wire(native.Events?.PushNotification?.TokenGenerated ?? 'Native\\Mobile\\Events\\PushNotification\\TokenGenerated');
        wire(native.Events?.Alert?.ButtonPressed ?? 'Native\\Mobile\\Events\\Alert\\ButtonPressed');
    }

    runPhpProbe();
});

onBeforeUnmount(() => {
    offHandlers.forEach(([evt, h]) => native?.off?.(evt, h));
});
</script>

<template>
    <Head title="Native Sandbox" />

    <div class="min-h-screen bg-gray-950 text-gray-200 p-4 font-mono text-[13px] leading-relaxed">
        <h1 class="text-base font-bold mb-1">Native bridge sandbox</h1>
        <p class="text-gray-500 mb-4">Exercises the NativePHP calls this app implements and shows what comes back.</p>

        <!-- environment -->
        <section class="rounded-lg border border-gray-800 bg-gray-900/60 p-3 mb-3">
            <h2 class="text-gray-400 uppercase text-[10px] tracking-wider mb-2">environment</h2>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                <span class="text-gray-500">platform</span>
                <span>{{ platform.isAndroid ? 'android' : platform.isIos ? 'ios' : 'web / desktop' }}</span>
                <span class="text-gray-500">nativephp ext (PHP)</span>
                <span :class="nativeLoaded ? 'text-emerald-400' : 'text-amber-400'">{{ nativeLoaded ? 'loaded' : 'not loaded' }}</span>
                <span class="text-gray-500">nativephp_call fn</span>
                <span :class="hasBridgeFn ? 'text-emerald-400' : 'text-amber-400'">{{ hasBridgeFn ? 'present' : 'absent' }}</span>
                <span class="text-gray-500">#nativephp import</span>
                <span :class="importState.ok ? 'text-emerald-400' : 'text-rose-400'">
                    {{ importState.ok ? importState.path : (importState.error || 'failed') }}
                </span>
            </div>
            <p v-if="importState.ok" class="text-gray-600 mt-2 break-all">exports: {{ importState.exports.join(', ') }}</p>
            <p v-if="!isMobile" class="text-amber-400/80 mt-2">
                Not on a device — most native calls return null / false. Run this from <code>native:run android</code>.
            </p>
        </section>

        <!-- notifications (Firebase-free) -->
        <section class="rounded-lg border border-gray-800 bg-gray-900/60 p-3 mb-3">
            <h2 class="text-gray-400 uppercase text-[10px] tracking-wider mb-2">notifications (Firebase-free)</h2>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                <span class="text-gray-500">Web Notifications API</span>
                <span :class="notif.webApi ? 'text-emerald-400' : 'text-amber-400'">{{ notif.webApi ? 'available' : 'unsupported' }}</span>
                <span class="text-gray-500">permission</span>
                <span>{{ notif.permission }}</span>
                <span class="text-gray-500">service worker</span>
                <span :class="notif.swReady ? 'text-emerald-400' : 'text-amber-400'">{{ notif.swReady ? 'registered' : 'no' }}</span>
                <span class="text-gray-500">active mode</span>
                <span :class="notif.mode === 'web' ? 'text-emerald-400' : 'text-amber-300'">{{ notif.mode }}</span>
            </div>
            <p v-if="notif.mode !== 'web'" class="text-amber-400/80 mt-2 leading-snug">
                This WebView has no Notification API. Alerts degrade to a native toast + haptics
                (high-priority ones use a native dialog). Background/killed-app delivery needs FCM
                or a native local-notification plugin — neither is installed (by design: Firebase-free).
            </p>
        </section>

        <!-- JS native calls -->
        <section class="rounded-lg border border-gray-800 bg-gray-900/60 p-3 mb-3">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-gray-400 uppercase text-[10px] tracking-wider">JS native calls</h2>
                <button class="px-2 py-1 rounded bg-blue-600 text-white text-xs" @click="runAllSafe">run all (safe)</button>
            </div>
            <div v-for="(fn, name) in tests" :key="name" class="flex items-start gap-2 py-1 border-b border-gray-800/60 last:border-0">
                <button class="shrink-0 px-2 py-0.5 rounded bg-gray-800 border border-gray-700 text-xs" @click="run(name, fn)">run</button>
                <span class="shrink-0 w-5 text-center">{{ verdict(results[name]) }}</span>
                <div class="min-w-0 flex-1">
                    <div class="text-gray-300">{{ name }}</div>
                    <div v-if="results[name]?.status === 'ok'" class="text-emerald-300/90 break-all">
                        {{ JSON.stringify(results[name].value) }} <span class="text-gray-600">· {{ results[name].ms }}ms</span>
                    </div>
                    <div v-else-if="results[name]?.status === 'fail'" class="text-rose-400 break-all">{{ results[name].error }}</div>
                </div>
            </div>
        </section>

        <!-- side-effect calls -->
        <section class="rounded-lg border border-gray-800 bg-gray-900/60 p-3 mb-3">
            <h2 class="text-gray-400 uppercase text-[10px] tracking-wider mb-2">side-effect calls</h2>
            <div v-for="(fn, name) in sideEffectTests" :key="name" class="flex items-start gap-2 py-1 border-b border-gray-800/60 last:border-0">
                <button class="shrink-0 px-2 py-0.5 rounded bg-gray-800 border border-gray-700 text-xs" @click="run(name, fn)">run</button>
                <span class="shrink-0 w-5 text-center">{{ verdict(results[name]) }}</span>
                <div class="min-w-0 flex-1">
                    <div class="text-gray-300">{{ name }}</div>
                    <div v-if="results[name]?.status === 'fail'" class="text-rose-400 break-all">{{ results[name].error }}</div>
                    <div v-else-if="results[name]?.status === 'ok'" class="text-emerald-300/90">ok · {{ results[name].ms }}ms</div>
                </div>
            </div>
        </section>

        <!-- native event log -->
        <section class="rounded-lg border border-gray-800 bg-gray-900/60 p-3 mb-3">
            <h2 class="text-gray-400 uppercase text-[10px] tracking-wider mb-2">native events (On)</h2>
            <div v-if="!events.length" class="text-gray-600">listening for TokenGenerated + Alert.ButtonPressed…</div>
            <div v-for="(e, i) in events" :key="i" class="flex gap-2">
                <span class="text-gray-600">{{ e.t }}</span>
                <span class="text-amber-300 break-all">{{ e.name }}</span>
                <span class="text-gray-400 break-all">{{ e.payload }}</span>
            </div>
        </section>

        <!-- PHP probe -->
        <section class="rounded-lg border border-gray-800 bg-gray-900/60 p-3">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-gray-400 uppercase text-[10px] tracking-wider">PHP facade probe</h2>
                <button class="px-2 py-1 rounded bg-blue-600 text-white text-xs" @click="runPhpProbe">re-run</button>
            </div>
            <div v-for="c in phpChecks" :key="c.name" class="flex items-start gap-2 py-1 border-b border-gray-800/60 last:border-0">
                <span class="shrink-0 w-5 text-center">{{ c.ok ? (c.value === null || c.value === false ? '⚠️' : '✅') : '❌' }}</span>
                <div class="min-w-0 flex-1">
                    <div class="text-gray-300">{{ c.name }} <span class="text-gray-600">· {{ c.ms }}ms</span></div>
                    <div v-if="c.error" class="text-rose-400 break-all">{{ c.error }}</div>
                    <div v-else class="text-emerald-300/90 break-all">{{ JSON.stringify(c.value) }}</div>
                </div>
            </div>
        </section>
    </div>
</template>
