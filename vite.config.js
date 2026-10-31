import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { cpSync, mkdirSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';

const require = createRequire(import.meta.url);
const pdfPackage = dirname(require.resolve('pdfjs-dist/package.json'));
const pdfVersion = JSON.parse(readFileSync(resolve(pdfPackage, 'package.json'), 'utf8')).version;
const pdfAssetPath = `/asset/marthire/build/pdfjs/${pdfVersion}/`;

// Publish ancillary PDF assets from the exact locked package version. This runs
// after Vite clears the build directory and also supports npm run dev + Laravel.
function localPdfAssets() {
    let buildDirectory;
    const copyAssets = () => {
        const destination = resolve(buildDirectory, 'pdfjs', pdfVersion);
        mkdirSync(destination, { recursive: true });
        for (const directory of ['cmaps', 'standard_fonts', 'wasm', 'iccs']) {
            cpSync(resolve(pdfPackage, directory), resolve(destination, directory), { recursive: true });
        }
        cpSync(resolve(pdfPackage, 'LICENSE'), resolve(destination, 'LICENSE'));
    };
    return {
        name: 'marthire-local-pdf-assets',
        configResolved(config) { buildDirectory = resolve(config.root, config.build.outDir); },
        configureServer() { copyAssets(); },
        closeBundle() { copyAssets(); },
    };
}

export default defineConfig({
    define: { __CANDIDATE_PDF_ASSET_PATH__: JSON.stringify(pdfAssetPath) },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // Vite output lives in its OWN subdir so emptyOutDir can safely clear
            // only the build output on each run. The parent dir
            // (public/asset/marthire) holds hand-placed static assets (footer
            // image, social icons, country flags) that Vite must never touch.
            buildDirectory: 'asset/marthire/build',
            refresh: true,
        }),
        localPdfAssets(),
    ],
});
