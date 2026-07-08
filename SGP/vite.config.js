import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

const host = process.env.VITE_HOST || '127.0.0.1';
const hmrHost = process.env.VITE_HMR_HOST || host;
// Docker usa o hostname nginx; local aponta para o artisan serve
const imgProxyTarget = process.env.VITE_IMG_PROXY || 'http://127.0.0.1:8000';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    server: {
        host,
        port: 5173,
        strictPort: true,
        hmr: {
            host: hmrHost,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/IMG': {
                target: imgProxyTarget,
                changeOrigin: true,
            },
        },
    },
});
