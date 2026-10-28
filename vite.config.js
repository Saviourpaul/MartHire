import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // Vite output lives in its OWN subdir so emptyOutDir can safely clear
            // only the build output on each run. The parent dir
            // (public/asset/nearbyworks) holds hand-placed static assets (footer
            // image, social icons, country flags) that Vite must never touch.
            buildDirectory: 'asset/nearbyworks/build',
            refresh: true,
        }),
    ],
});
