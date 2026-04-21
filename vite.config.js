import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/solar.js',
            ],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        allowedHosts: 'all',
        hmr: {
            clientPort: 443,
            host: '6f07c584-f812-427c-956a-6a8b9c143c22-00-1m0oknmr6yn2.sisko.replit.dev',
        },
    },
})