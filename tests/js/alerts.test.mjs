import test from 'node:test';
import assert from 'node:assert/strict';
import { createFormConfirmationHandler, initializeAlerts } from '../../resources/js/alerts.js';

function confirmationForm(confirm, attributes = {}) {
    const inputs = [];
    const requests = [];
    let handler;
    const form = {
        tagName: 'FORM', isConnected: true, valid: true,
        getAttribute: (key) => attributes[key] ?? null,
        querySelectorAll: () => inputs.filter((input) => !input.removed),
        append: (input) => inputs.push(input),
        requestSubmit(submitter) {
            if (!this.valid) return;
            const retry = submitEvent(form, submitter);
            handler(retry);
            if (!retry.defaultPrevented) requests.push({ submitter, fields: inputs.filter((input) => !input.removed).map(({ name, value }) => [name, value]) });
        },
    };
    handler = createFormConfirmationHandler(confirm, { createInput: () => ({ setAttribute() {}, remove() { this.removed = true; } }) });
    return { form, handler, requests, inputs };
}
const submitEvent = (target, submitter) => ({ target, submitter, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } });
const button = (attributes = {}) => ({ name: 'stage', value: 'selected', getAttribute: (key) => attributes[key] ?? null });
const deferred = () => { let resolve; const promise = new Promise((done) => { resolve = done; }); return { promise, resolve }; };

test('confirmed forms preserve submitter, accepted field, and run native validation again', async () => {
    const { form, handler, requests } = confirmationForm(async () => true);
    const selected = button({ 'data-confirm': 'Select?', 'data-confirm-field': 'selection_confirmed' });
    await handler(submitEvent(form, selected));
    assert.equal(requests.length, 1);
    assert.equal(requests[0].submitter, selected);
    assert.deepEqual(requests[0].fields, [['selection_confirmed', '1']]);
    await handler(submitEvent(form, selected));
    assert.equal(requests.length, 1, 'a second submit is blocked until a page restoration');
    handler.reset();
    await handler(submitEvent(form, selected));
    assert.equal(requests.length, 2);
    assert.deepEqual(requests[1].fields, [['selection_confirmed', '1']]);
});

test('cancelling never submits or creates an accepted field', async () => {
    const { form, handler, requests, inputs } = confirmationForm(async () => false, { 'data-confirm': 'Complete?', 'data-confirm-field': 'completion_confirmed' });
    await handler(submitEvent(form, button()));
    assert.equal(requests.length, 0);
    assert.equal(inputs.length, 0);
});

test('pending confirmations suppress double clicks and keep the original action', async () => {
    const decision = deferred();
    let prompts = 0;
    const { form, handler, requests } = confirmationForm(() => { prompts += 1; return decision.promise; }, { 'data-confirm': 'Proceed?' });
    const selected = button();
    const first = handler(submitEvent(form, selected));
    const second = submitEvent(form, button());
    await handler(second);
    assert.equal(second.defaultPrevented, true);
    assert.equal(prompts, 1);
    decision.resolve(true);
    await first;
    assert.equal(requests[0].submitter, selected);
});

test('a validation failure after confirmation allows a new confirmed attempt', async () => {
    let prompts = 0;
    const { form, handler, requests } = confirmationForm(async () => { prompts += 1; return true; }, { 'data-confirm': 'Proceed?' });
    form.valid = false;
    await handler(submitEvent(form, button()));
    assert.equal(requests.length, 0);
    form.valid = true;
    await handler(submitEvent(form, button()));
    assert.equal(prompts, 2);
    assert.equal(requests.length, 1);
});

test('ordinary submissions are unaffected and confirmation fields cannot replace CSRF controls', async () => {
    const fixture = confirmationForm(async () => { throw new Error('unexpected modal'); });
    const ordinary = submitEvent(fixture.form, button());
    await fixture.handler(ordinary);
    assert.equal(ordinary.defaultPrevented, false);
    const protectedFixture = confirmationForm(async () => true, { 'data-confirm': 'Proceed?', 'data-confirm-field': '_token' });
    await protectedFixture.handler(submitEvent(protectedFixture.form, button()));
    assert.deepEqual(protectedFixture.requests[0].fields, []);
});

// Small DOM contract fixture; browser checks cover the native dialog's focus/inert behavior.
function alertPage() {
    const doc = new EventTarget();
    const timers = new Map();
    const win = Object.assign(new EventTarget(), { performance: { now: () => 1000 }, setTimeout: (fn) => { const id = timers.size + 1; timers.set(id, fn); return id; }, clearTimeout: (id) => timers.delete(id) });
    class Element extends EventTarget {
        constructor(tag = 'div') { super(); this.tagName = tag.toUpperCase(); this.children = []; this.dataset = {}; this.hidden = false; this.disabled = false; this.isConnected = true; this.tabIndex = 0; this.attributes = {}; this.classList = { add() {}, toggle() {} }; }
        append(child) { child.parent = this; this.children.push(child); }
        setAttribute(name, value) { this.attributes[name] = value; if (name === 'data-dynamic-toast') this.dataset.dynamicToast = ''; }
        focus() { doc.activeElement = this; }
        remove() { this.isConnected = false; if (this.parent) this.parent.children = this.parent.children.filter((child) => child !== this); }
        contains(node) { return node === this || this.children.some((child) => child.contains(node)); }
        matches() { return false; }
        cloneNode() { return new Element(this.tagName); }
        querySelectorAll(selector) { return selector === '[data-dynamic-toast]' ? this.children.filter((child) => 'dynamicToast' in child.dataset) : []; }
    }
    doc.createElement = (tag) => new Element(tag);
    const dialog = new Element('dialog');
    dialog.showModal = () => { dialog.open = true; };
    dialog.close = () => { dialog.open = false; };
    const nodes = Object.fromEntries(['title', 'message', 'confirm', 'cancel', 'dismiss'].map((key) => [key, new Element(key === 'title' ? 'h2' : key === 'message' ? 'p' : 'button')]));
    Object.values(nodes).forEach((node) => dialog.append(node));
    const icons = ['success', 'error', 'warning', 'info'].map((type) => Object.assign(new Element('span'), { dataset: { alertIcon: type } }));
    dialog.querySelector = (selector) => nodes[selector.replace('[data-alert-', '').replace(']', '')];
    dialog.querySelectorAll = (selector) => selector === '[data-alert-icon]' ? icons : [nodes.dismiss, nodes.cancel, nodes.confirm];
    const list = new Element('ol');
    const trigger = new Element('button');
    doc.activeElement = trigger;
    doc.querySelector = (selector) => selector === '[data-alert-dialog]' ? dialog : selector === '[data-toast-list]' ? list : new Element('svg');
    return { doc, win, dialog, list, nodes, trigger, timers };
}

test('shared confirmations resolve cancellation and restore the triggering focus', async () => {
    const page = alertPage();
    const api = initializeAlerts({ document: page.doc, window: page.win });
    const decision = api.confirm('Select this candidate?', { title: 'Select candidate' });
    assert.equal(page.dialog.open, true);
    assert.equal(page.doc.activeElement, page.nodes.cancel);
    const cancel = new Event('cancel', { cancelable: true });
    page.dialog.dispatchEvent(cancel);
    assert.equal(await decision, false);
    assert.equal(cancel.defaultPrevented, true);
    assert.equal(page.dialog.open, false);
    assert.equal(page.doc.activeElement, page.trigger);
});

test('queued alerts keep state-specific copy and restore focus after the final dialog', async () => {
    const page = alertPage();
    const api = initializeAlerts({ document: page.doc, window: page.win });
    const first = api.confirm('First');
    const second = api.alert('<script>plain text</script>', { type: 'error', title: 'Problem' });
    page.nodes.confirm.dispatchEvent(new Event('click'));
    assert.equal(await first, true);
    assert.equal(page.dialog.dataset.alertType, 'error');
    assert.equal(page.nodes.message.textContent, '<script>plain text</script>');
    assert.equal(page.nodes.cancel.hidden, true);
    page.nodes.confirm.dispatchEvent(new Event('click'));
    await second;
    assert.equal(page.doc.activeElement, page.trigger);
});

test('all toast severities use escaped text with persistent warning/error feedback', () => {
    const page = alertPage();
    const api = initializeAlerts({ document: page.doc, window: page.win });
    for (const type of ['success', 'error', 'warning', 'info']) api.notify('<img onerror=unsafe()>', { type });
    assert.equal(page.list.children.length, 4);
    assert.equal(page.timers.size, 2);
    page.list.children.forEach((toast) => {
        assert.equal(toast.children.find((child) => child.tagName === 'P').textContent, '<img onerror=unsafe()>');
        assert.equal(toast.attributes.role, ['error', 'warning'].includes(toast.dataset.toastType) ? 'alert' : 'status');
    });
});
