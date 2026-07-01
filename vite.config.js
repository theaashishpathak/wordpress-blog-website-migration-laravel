import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: false,
        }),
        tailwindcss(),
    ],
    server: {
        // Bind to IPv4 127.0.0.1 explicitly. Without this, Vite defaults to
        // dual-stack and writes "http://[::1]:5173" into public/hot, which many
        // Windows browsers/firewalls fail to resolve — resulting in unstyled
        // pages because every CSS/JS request 404s from the browser side.
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        cors: true,
        hmr: false,     // 👈 Vite HMR fully disabled
        watch: false,   // 👈 file watching off
    },
});
