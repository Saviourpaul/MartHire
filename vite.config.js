import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        // The build dir (public/asset/nearbyworks) also holds hand-placed static
        // assets (footer image, social icons, country flags). Do NOT empty it on
        // build, or Vite would delete them.
        emptyOutDir: false,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            buildDirectory: 'asset/nearbyworks',
            refresh: true,
        }),
    ],
});
