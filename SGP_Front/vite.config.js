import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const apiTarget = env.VITE_API_URL || 'http://127.0.0.1:8000';

    return {
        plugins: [
            tailwindcss(),
            vue(),
        ],
        server: {
            host: '127.0.0.1',
            port: 5173,
            strictPort: true,
            proxy: {
                '/api': {
                    target: apiTarget,
                    changeOrigin: true,
                },
                '/IMG': {
                    target: apiTarget,
                    changeOrigin: true,
                },
                '/storage': {
                    target: apiTarget,
                    changeOrigin: true,
                },
            },
        },
    };
});
