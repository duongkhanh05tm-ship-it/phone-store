import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: process.env.DEV_SERVER_HOST || '127.0.0.1',
        port: Number(process.env.DEV_SERVER_PORT || 5173),
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
