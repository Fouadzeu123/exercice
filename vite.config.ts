import { fileURLToPath, URL } from 'node:url';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // wayfinder({       // <---- commentez ou supprimez ces lignes
        //     formVariants: true,
        // }),
    ],
    optimizeDeps: {
        include: [
            '@heroicons/vue',
            '@heroicons/vue/24/outline',
            '@inertiajs/vue3',
            'axios',
            'vue',
        ],
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue', '.mts', '.cts'],
        mainFields: ['browser', 'module', 'jsnext:main', 'jsnext', 'main'],
    },
});