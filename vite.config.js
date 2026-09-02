import {
    defineConfig,
    loadEnv
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

const FALLBACK_HMR_HOST = 'localhost';

/**
 * Resolve the HMR websocket host from APP_URL.
 *
 * The browser connects back to this host for hot reload, so it must match
 * whatever host the app is actually served on. Deriving it from APP_URL keeps
 * .env as the single source of truth: change APP_URL, restart, and both
 * Laravel's generated URLs and HMR follow.
 *
 * APP_URL includes a scheme and often a port; HMR wants the bare hostname.
 */
function resolveHmrHost(appUrl) {
    if (!appUrl) {
        return FALLBACK_HMR_HOST;
    }

    try {
        const { hostname } = new URL(appUrl);

        /**
         * A scheme-less value such as "localhost:8000" parses without throwing
         * ("localhost:" is read as the protocol) but yields an empty hostname,
         * which would silently break the HMR websocket.
         */
        if (hostname !== '') {
            return hostname;
        }
    } catch {
        // Fall through to the warning below.
    }

    console.warn(
        `[vite] Could not read a hostname from APP_URL: ${JSON.stringify(appUrl)}. ` +
        `Falling back to HMR host "${FALLBACK_HMR_HOST}". ` +
        `APP_URL should include a scheme, e.g. http://localhost:8000`
    );

    return FALLBACK_HMR_HOST;
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), 'APP_');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            hmr: {
                host: resolveHmrHost(env.APP_URL),
            },
            cors: true,
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
