import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * KoriePay — Vite dev server.
 *
 * In the Arena preview the browser can never reach `localhost`, so when a
 * sandbox id is present we:
 *   - bind to 0.0.0.0 (the platform proxies this port to its own public URL),
 *   - pin HMR to the PUBLIC preview host (https://5173-{sandbox}.e2b.app) so
 *     the injected @vite/client + HMR socket resolve to the proxied origin
 *     (port/protocol fall back to the page's own https://…:443),
 *   - allow any host header (the preview host is unknown ahead of time).
 */
const sandboxId = process.env.E2B_SANDBOX_ID || null;
const publicHost = sandboxId ? `5173-${sandboxId}.e2b.app` : 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        allowedHosts: true,
        cors: true,
        ...(sandboxId ? { hmr: { host: publicHost } } : {}),
    },
});
