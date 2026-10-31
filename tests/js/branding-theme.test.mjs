import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const head = readFileSync(new URL('../../resources/views/partials/site-head.blade.php', import.meta.url), 'utf8');
const script = head.match(/<script>([\s\S]*?)<\/script>/)[1];

function loadTheme(entries = [], systemDark = false) {
    const storage = new Map(entries);
    const document = { documentElement: { dataset: {} } };
    runInNewContext(script, { document, window: {
        localStorage: { getItem: (key) => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value), removeItem: (key) => storage.delete(key) },
        matchMedia: () => ({ matches: systemDark }),
    } });
    return { storage, theme: document.documentElement.dataset };
}

test('Marthire preserves a saved legacy appearance preference and removes the old key', () => {
    const page = loadTheme([['nearbyworks-theme', 'dark']]);
    assert.equal(page.theme.themePreference, 'dark');
    assert.equal(page.theme.theme, 'dark');
    assert.equal(page.storage.get('marthire-theme'), 'dark');
    assert.equal(page.storage.has('nearbyworks-theme'), false);
});

test('an existing Marthire preference wins over the legacy preference', () => {
    const page = loadTheme([['marthire-theme', 'light'], ['nearbyworks-theme', 'dark']], true);
    assert.equal(page.theme.themePreference, 'light');
    assert.equal(page.theme.theme, 'light');
    assert.equal(page.storage.has('nearbyworks-theme'), false);
});

test('invalid saved preferences use the operating system theme', () => {
    const page = loadTheme([['nearbyworks-theme', 'unsupported']], true);
    assert.equal(page.theme.themePreference, 'system');
    assert.equal(page.theme.theme, 'dark');
});
