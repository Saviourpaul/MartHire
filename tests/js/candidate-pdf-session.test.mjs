import test, { afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { createPdfSession } from '../../resources/js/candidate-pdf-session.js';

const originalDpr = globalThis.devicePixelRatio;
afterEach(() => {
    if (originalDpr === undefined) delete globalThis.devicePixelRatio;
    else globalThis.devicePixelRatio = originalDpr;
});
const deferred = () => {
    let resolve, reject;
    const promise = new Promise((yes, no) => { resolve = yes; reject = no; });
    return { promise, resolve, reject };
};
const flush = async () => { for (let index = 0; index < 8; index += 1) await Promise.resolve(); };
const canvas = () => ({ width: 0, height: 0, style: {}, attributes: {}, getContext: () => ({}), setAttribute(name, value) { this.attributes[name] = value; } });
function fixture({ controlled = false } = {}) {
    const renders = [];
    let destroyed = 0;
    const page = {
        getViewport: ({ scale }) => ({ width: 612 * scale, height: 792 * scale }),
        async getTextContent() { return { items: [{ str: 'Candidate CV', hasEOL: true }, { type: 'beginMarkedContent' }, { str: 'Engineer' }] }; },
        render(options) {
            const pending = deferred();
            const task = { ...pending, options, cancelled: 0, cancel() { this.cancelled += 1; } };
            renders.push(task);
            if (!controlled) task.resolve();
            return task;
        },
    };
    const pdf = { numPages: 3, async getPage() { return page; }, async destroy() { destroyed += 1; renders.forEach((task) => task.reject(Object.assign(new Error('Cancelled'), { name: 'RenderingCancelledException' }))); } };
    return { session: createPdfSession(pdf), page, pdf, renders, get destroyed() { return destroyed; } };
}

test('PDF rendering fits the container, returns accessible page text, and caps high-DPI memory', async () => {
    globalThis.devicePixelRatio = 4;
    const { session, renders } = fixture();
    const surface = canvas();
    const result = await session.render(surface, { clientWidth: 612 }, 2);
    assert.equal(surface.style.width, '612px');
    assert.equal(surface.width, 1224);
    assert.equal(surface.height, 1584);
    assert.equal(surface.attributes['aria-label'], 'Document page 2 of 3');
    assert.equal(result.pageText, 'Candidate CV\nEngineer');
    assert.equal(renders[0].options.background, '#ffffff');
    await session.render(surface, { clientWidth: 6000 }, 1, { zoom: 3 });
    assert.ok(surface.width * surface.height <= 16 * 1024 * 1024);
    assert.ok(surface.width <= 16384 && surface.height <= 16384);
    await session.destroy();
});

test('switching PDF pages cancels and awaits the prior task before reusing its canvas', async () => {
    const { session, renders } = fixture({ controlled: true });
    const surface = canvas();
    const first = session.render(surface, { clientWidth: 600 }, 1);
    await flush();
    assert.equal(renders.length, 1);
    const previousWidth = surface.width;
    const second = session.render(surface, { clientWidth: 900 }, 2);
    await flush();
    assert.equal(renders[0].cancelled, 1);
    assert.equal(renders.length, 1);
    assert.equal(surface.width, previousWidth);
    renders[0].reject(Object.assign(new Error('Cancelled'), { name: 'RenderingCancelledException' }));
    await assert.rejects(first, { name: 'AbortError' });
    await flush();
    assert.equal(renders.length, 2);
    assert.equal(Math.round(parseFloat(surface.style.width)), 900);
    renders[1].resolve();
    assert.ok(Math.abs((await second).width - 900) < 0.001);
    await session.destroy();
});

test('an obsolete page lookup never renders or resizes the canvas', async () => {
    const { pdf, page, renders } = fixture();
    const pendingPage = deferred();
    let calls = 0;
    pdf.getPage = () => ++calls === 1 ? pendingPage.promise : Promise.resolve(page);
    const session = createPdfSession(pdf);
    const surface = canvas();
    const first = session.render(surface, { clientWidth: 600 }, 1);
    await flush();
    const second = session.render(surface, { clientWidth: 900 }, 2);
    pendingPage.resolve(page);
    await assert.rejects(first, { name: 'AbortError' });
    await second;
    assert.equal(renders.length, 1);
    assert.equal(Math.round(parseFloat(surface.style.width)), 900);
    await session.destroy();
});

test('destroy releases pending work once and prevents subsequent rendering', async () => {
    const setup = fixture({ controlled: true });
    const pending = setup.session.render(canvas(), { clientWidth: 600 }, 1);
    await flush();
    const firstDestroy = setup.session.destroy();
    const secondDestroy = setup.session.destroy();
    assert.equal(firstDestroy, secondDestroy);
    await firstDestroy;
    await assert.rejects(pending, { name: 'AbortError' });
    assert.equal(setup.destroyed, 1);
    assert.equal(setup.renders[0].cancelled, 1);
    await assert.rejects(setup.session.render(canvas(), { clientWidth: 600 }, 1), { name: 'AbortError' });
});

test('invalid pages and rendering failures return useful errors without exposing parser details', async () => {
    const { session, page } = fixture();
    await assert.rejects(session.render(canvas(), { clientWidth: 600 }, 4), /Choose a valid document page/);
    page.render = () => { throw new Error('Internal parser data and document details'); };
    await assert.rejects(session.render(canvas(), { clientWidth: 600 }, 1), /This PDF page could not be displayed/);
    await session.destroy();
});

test('invalid page geometry is rejected before allocating a canvas', async () => {
    const { session, page, renders } = fixture();
    const surface = canvas();
    page.getViewport = () => ({ width: Infinity, height: 0 });
    await assert.rejects(session.render(surface, { clientWidth: 600 }, 1), /This PDF page could not be displayed/);
    assert.equal(surface.width, 0);
    assert.equal(renders.length, 0);
    await session.destroy();
});
