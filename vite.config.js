import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/addon.js',
                'resources/css/addon.css'
            ],
            publicDirectory: 'resources/dist',
        }),
        vue(),
    ],
    resolve: {
        alias: {
            'statamic': path.resolve(__dirname, '../../vendor/statamic/cms/resources/js'),
        },
    },
});