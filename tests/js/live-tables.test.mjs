import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeLiveTables } from '../../resources/js/live-tables.js';

const deferred = () => { let resolve; const promise = new Promise((done) => { resolve = done; }); return { promise, resolve }; };
const response = (body, status = 200) => new Response(JSON.stringify(body), { status });
const turn = () => new Promise((resolve) => setImmediate(resolve));

// Minimal DOM contract fixture; the real browser checks cover fragment rendering,
// Alpine initialization, focus restoration and cross-tab synchronization.
function page(fetcher) {
    const target = () => ({ listeners: new Map(), addEventListener(name, fn) { this.listeners.set(name, fn); }, removeEventListener() {}, dispatchEvent(event) { return this.listeners.get(event.type)?.(event); } });
    const doc = target();
    const win = Object.assign(target(), { location: { href: 'https://example.test/team', origin: 'https://example.test' }, navigator: { onLine: true },
        getComputedStyle: () => ({ visibility: 'visible' }), setInterval: () => 1, clearInterval() {},
        Marthire: { notify() {}, confirm: async () => true },
        FormData: class { constructor(form) { this.form = form; } append() {} },
        DOMParser: class { parseFromString() { throw new Error('The edited workspace must not be replaced.'); } },
    });
    const statusText = { textContent: '' };
    const bar = { classList: { toggle() {} }, querySelector: () => statusText };
    const modal = { isConnected: true, open: false, getClientRects() { return this.open ? [1] : []; }, closest() { return this; }, getAttribute: () => 'manage-title' };
    const forms = [];
    const root = { isConnected: true, dataset: { liveTable: 'team', liveTopics: 'team', liveInterval: '0' },
        querySelector: () => bar, querySelectorAll: () => [modal], setAttribute() {}, contains: (node) => forms.includes(node),
    };
    doc.activeElement = null;
    doc.querySelector = () => null;
    doc.querySelectorAll = (selector) => selector === '[data-live-table]' ? [root] : [];
    const form = (action) => {
        const input = { disabled: false, value: 'Committed edit', isConnected: true };
        const item = { action: `https://example.test/${action}`, method: 'post', elements: [input], isConnected: true, resets: 0,
            closest: (selector) => selector === '[data-live-table]' ? root : null,
            hasAttribute: () => false, querySelectorAll: () => [], reset() { this.resets++; input.value = 'Old edit'; },
        };
        forms.push(item); return item;
    };
    const app = initializeLiveTables({ document: doc, window: win, fetch: fetcher });
    const submit = (item) => doc.listeners.get('submit')({ target: item, defaultPrevented: false, preventDefault() {}, submitter: null });
    const refresh = () => doc.listeners.get('click')({ target: { closest: (selector) => selector === '[data-live-refresh]' ? { closest: () => root } : null } });
    return { app, form, submit, refresh, modal, statusText };
}

test('a foreground refresh cannot erase a dialog opened while its response was pending', async () => {
    const pending = deferred();
    const fixture = page(() => pending.promise);
    await fixture.refresh();
    fixture.modal.open = true;
    pending.resolve(response({ regions: { team: '<div>new rows</div>' } }));
    await turn();
    assert.equal(fixture.statusText.textContent, 'Updates paused while you are editing.');
    fixture.app.destroy();
});

test('an older save finishing its refresh cannot release a newer in-flight mutation', async () => {
    const firstRead = deferred();
    const secondSave = deferred();
    let writes = 0;
    const fixture = page((url, options) => {
        if (options.headers['X-Live-Tables']) return firstRead.promise;
        writes++;
        return writes === 1 ? Promise.resolve(response({ message: 'Saved', topics: ['team'] })) : secondSave.promise;
    });
    const first = fixture.submit(fixture.form('first'));
    await turn();
    const second = fixture.submit(fixture.form('second'));
    await turn();
    firstRead.resolve(response({}, 500));
    await first;
    await turn();
    await fixture.submit(fixture.form('third'));
    assert.equal(writes, 2, 'the second save still owns the mutation guard');
    secondSave.resolve(response({}, 500));
    await second;
    await turn();
    fixture.app.destroy();
});

test('a successful edit retains its committed values when the following refresh fails', async () => {
    const fixture = page(async (url, options) => options.headers['X-Live-Tables']
        ? response({}, 500) : response({ message: 'Saved', topics: ['team'] }));
    const form = fixture.form('edit');
    await fixture.submit(form);
    await turn();
    assert.equal(form.resets, 0);
    assert.equal(form.elements[0].value, 'Committed edit');
    assert.equal(form.elements[0].disabled, false);
    assert.match(fixture.statusText.textContent, /server could not complete/);
    fixture.app.destroy();
});
