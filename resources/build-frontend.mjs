import { fileURLToPath } from 'node:url';
import { build } from 'vite';

// Compatibility entry point: use the same isolated build directory and asset
// publishing hooks as npm run build, without overriding the Vite configuration.
await build({
    root: fileURLToPath(new URL('../', import.meta.url)),
    configLoader: 'native',
});
