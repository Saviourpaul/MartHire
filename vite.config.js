import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
<<<<<<< Updated upstream
            input: ['resources/css/app.css', 'resources/js/app.js'],
=======
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/application-wizard.js',
                'resources/js/location-selector.js',
                
            ],
>>>>>>> Stashed changes
            refresh: true,
        }),
    ],
});
