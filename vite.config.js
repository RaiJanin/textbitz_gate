import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { nativephpHotFile, nativephpMobile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        nativephpMobile(),
    ],
});
