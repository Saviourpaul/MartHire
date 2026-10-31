import test, { beforeEach, afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { createCandidateReview } from '../../resources/js/candidate-review.js';

const originals = { fetch: globalThis.fetch, window: globalThis.window, create: URL.createObjectURL, revoke: URL.revokeObjectURL };
let revoked;
let created;
beforeEach(() => {
    revoked = [];
    created = [];
    URL.createObjectURL = (blob) => { created.push(blob); return `blob:test-${created.length}`; };
    URL.revokeObjectURL = (url) => revoked.push(url);
});
afterEach(() => {
    globalThis.fetch = originals.fetch;
    if (originals.window === undefined) delete globalThis.window;
    else globalThis.window = originals.window;
    URL.createObjectURL = originals.create;
    URL.revokeObjectURL = originals.revoke;
});
const doc = (id, extra = {}) => ({ id: String(id), name: `document-${id}.pdf`, available: true, previewable: true, preview_url: `/private/${id}/preview`, download_url: `/private/${id}/download`, ...extra });
const response = (mime = 'application/pdf', body = '%PDF-1.7') => new Response(new Blob([body], { type: mime }), { headers: { 'Content-Type': mime } });
const defer = () => { let resolve; const promise = new Promise((done) => { resolve = done; }); return { promise, resolve }; };
function workspace(options = {}, dependencies = {}) {
    const app = createCandidateReview(options, { loadPdf: async () => ({ numPages: 3, render: async () => ({ pageText: 'Sample CV text' }), destroy: async () => {} }), ...dependencies });
    app.$nextTick = (callback) => Promise.resolve(callback?.());
    app.$refs = Object.fromEntries(['overview', 'interviews', 'activity'].map((tab) => [`reviewTab${tab}`, { focused: false, focus() { this.focused = true; } }]));
    return app;
}

test('private PDFs and images load in the workspace with authenticated no-store requests', async () => {
    const requests = [];
    globalThis.fetch = async (url, options) => { requests.push({ url, options }); return response(url.includes('/2/') ? 'image/png' : 'application/pdf'); };
    const app = workspace({ documents: [doc(1), doc(2)] });
    await app.init();
    assert.equal(app.documentState, 'ready');
    assert.equal(app.documentMime, 'application/pdf');
    assert.equal(app.documentUrl, '');
    assert.equal(app.pdfPages, 3);
    assert.equal(app.pdfText, 'Sample CV text');
    assert.equal(requests[0].options.credentials, 'same-origin');
    assert.equal(requests[0].options.cache, 'no-store');
    await app.selectDocument('2');
    assert.equal(app.documentMime, 'image/png');
    assert.equal(app.documentUrl, 'blob:test-1');
    assert.deepEqual(revoked, []);
    app.destroy();
    assert.deepEqual(revoked, ['blob:test-1']);
});

test('missing and unsupported files give explicit states without fetching a preview', async () => {
    globalThis.fetch = async () => { throw new Error('must not fetch'); };
    const app = workspace({ documents: [doc(1, { available: false }), doc(2, { previewable: false })] });
    await app.init();
    assert.equal(app.documentState, 'unavailable');
    await app.selectDocument('2');
    assert.equal(app.documentState, 'unsupported');
    assert.equal(created.length, 0);
    app.destroy();
});

test('permission, session, missing-file and type errors never produce a preview URL', async () => {
    for (const [status, expected] of [[401, /session/], [403, /permission/], [404, /no longer/], [415, /download/], [429, /Too many/]]) {
        globalThis.fetch = async () => new Response('', { status });
        const app = workspace({ documents: [doc(1)] });
        await app.init();
        assert.equal(app.documentState, 'error');
        assert.match(app.documentError, expected);
        assert.equal(app.documentUrl, '');
        app.destroy();
    }
    assert.equal(created.length, 0);
});

test('unexpected HTML and empty bodies are rejected before creating blob URLs', async () => {
    for (const result of [response('text/html', '<script>untrusted()</script>'), response('application/pdf', '')]) {
        globalThis.fetch = async () => result;
        const app = workspace({ documents: [doc(1)] });
        await app.init();
        assert.equal(app.documentState, 'error');
        assert.equal(app.documentUrl, '');
    }
    assert.equal(created.length, 0);
});

test('login redirects report an expired session and a retry can recover', async () => {
    globalThis.fetch = async () => ({ redirected: true, ok: true });
    const app = workspace({ documents: [doc(1)] });
    await app.init();
    assert.match(app.documentError, /session has expired/);
    globalThis.fetch = async () => response();
    await app.loadDocument();
    assert.equal(app.documentState, 'ready');
    assert.equal(app.documentError, '');
    app.destroy();
});

test('changing documents aborts the previous request and ignores its late response', async () => {
    const first = defer();
    let firstSignal;
    globalThis.fetch = (url, options) => {
        if (url.includes('/1/')) { firstSignal = options.signal; return first.promise; }
        return Promise.resolve(response('image/jpeg'));
    };
    const app = workspace({ documents: [doc(1), doc(2)] });
    const loading = app.init();
    await app.selectDocument('2');
    assert.equal(firstSignal.aborted, true);
    first.resolve(response());
    await loading;
    assert.equal(app.documentId, '2');
    assert.equal(app.documentMime, 'image/jpeg');
    assert.equal(created.length, 1);
    app.destroy();
});

test('destroying during blob loading prevents stale rendering or object URL leaks', async () => {
    const body = defer();
    globalThis.fetch = async () => ({ ok: true, headers: new Headers({ 'Content-Type': 'application/pdf' }), blob: () => body.promise });
    const app = workspace({ documents: [doc(1)] });
    const loading = app.init();
    await Promise.resolve();
    app.destroy();
    body.resolve(new Blob(['%PDF-1.7']));
    await loading;
    assert.equal(created.length, 0);
    assert.equal(app.documentUrl, '');
});

test('PDF page navigation and zoom render one page at a time and release the document', async () => {
    const calls = [];
    let destroyed = 0;
    globalThis.fetch = async () => response();
    const app = workspace({ documents: [doc(1)] }, { loadPdf: async () => ({
        numPages: 2,
        render: async (canvas, container, page, options) => { calls.push([page, options.zoom]); return { pageText: `Text for page ${page}` }; },
        destroy: async () => { destroyed += 1; },
    }) });
    await app.init();
    await app.changePdfPage(1);
    assert.equal(app.pdfPage, 2);
    assert.equal(app.pdfText, 'Text for page 2');
    await app.changePdfPage(1);
    assert.equal(app.pdfPage, 2);
    await app.zoomPdf(.25);
    await app.zoomPdf(0);
    assert.deepEqual(calls, [[1, 1], [2, 1], [2, 1.25], [2, 1]]);
    app.destroy();
    assert.equal(destroyed, 1);
});

test('a stale PDF loader is destroyed instead of replacing a newly selected image', async () => {
    const loading = defer();
    const started = defer();
    let destroyed = 0;
    globalThis.fetch = async (url) => response(url.includes('/2/') ? 'image/png' : 'application/pdf');
    const app = workspace({ documents: [doc(1), doc(2)] }, { loadPdf: () => { started.resolve(); return loading.promise; } });
    const first = app.init();
    await started.promise;
    await app.selectDocument('2');
    loading.resolve({ destroy: async () => { destroyed += 1; } });
    await first;
    assert.equal(destroyed, 1);
    assert.equal(app.documentMime, 'image/png');
    assert.equal(app.documentState, 'ready');
    app.destroy();
});

test('password-protected or corrupt PDF errors retain the download fallback', async () => {
    globalThis.fetch = async () => response();
    const app = workspace({ documents: [doc(1)] }, { loadPdf: async () => { throw new Error('internal parser details'); } });
    await app.init();
    assert.equal(app.documentState, 'error');
    assert.match(app.documentError, /password-protected or damaged/);
    assert.doesNotMatch(app.documentError, /internal parser/);
    assert.equal(app.selectedDocument.download_url, '/private/1/download');
});

test('a replacement PDF waits for the old renderer to release its canvas', async () => {
    const disposal = defer();
    const loaded = defer();
    let loads = 0;
    let newRenders = 0;
    globalThis.fetch = async () => response();
    const app = workspace({ documents: [doc(1), doc(2)] }, { loadPdf: async () => {
        loads += 1;
        if (loads === 1) return { numPages: 1, render: async () => ({ pageText: 'First' }), destroy: () => disposal.promise };
        loaded.resolve();
        return { numPages: 1, render: async () => { newRenders += 1; return { pageText: 'Second' }; }, destroy: async () => {} };
    } });
    await app.init();
    const next = app.selectDocument('2');
    await loaded.promise;
    await Promise.resolve();
    assert.equal(newRenders, 0);
    disposal.resolve();
    await next;
    assert.equal(newRenders, 1);
    assert.equal(app.pdfText, 'Second');
    app.destroy();
});

test('keyboard tabs wrap, focus their tab and leave document selection unchanged', () => {
    const app = workspace({ documents: [doc(1)] });
    app.moveReviewTab(-1);
    assert.equal(app.reviewTab, 'activity');
    assert.equal(app.$refs.reviewTabactivity.focused, true);
    app.moveReviewTab(1);
    assert.equal(app.reviewTab, 'overview');
    app.selectReviewTab('unknown');
    assert.equal(app.reviewTab, 'overview');
    assert.equal(app.documentId, '1');
});

test('schedule shortcut opens the inline form and focuses its disclosure', () => {
    const app = workspace();
    let focused = false;
    app.$refs.scheduleInterview = { open: false, querySelector: () => ({ focus() { focused = true; } }) };
    app.openSchedule();
    assert.equal(app.reviewTab, 'interviews');
    assert.equal(app.$refs.scheduleInterview.open, true);
    assert.equal(focused, true);
});

test('a synchronized review restores its active tab and selected document from safe DOM metadata', async () => {
    globalThis.fetch = async () => response('image/png');
    const app = workspace({ documents: [doc(1), doc(2)] });
    app.$el = { dataset: { restoreReviewTab: 'interviews', restoreDocument: '2' } };
    await app.init();
    assert.equal(app.reviewTab, 'interviews');
    assert.equal(app.documentId, '2');
    app.destroy();
});

