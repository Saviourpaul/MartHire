import assert from 'node:assert/strict';
import { test } from 'node:test';

// Lightweight DOM doubles keep selector race/retry tests dependency-free.
globalThis.CustomEvent = class extends Event {
    constructor(name, options = {}) { super(name, options); this.detail = options.detail; }
};
globalThis.Option = class {
    constructor(label, value, defaultSelected = false, selected = false) {
        this.textContent = label; this.value = value; this.selected = selected;
    }
};
globalThis.HTMLSelectElement = class extends EventTarget {
    constructor(selected = '') { super(); this.dataset = { selected }; this.options = []; this.disabled = false; this.required = false; }
    get value() { return this.options.find((option) => option.selected)?.value || ''; }
    set value(value) { this.options.forEach((option) => { option.selected = option.value === value; }); }
    replaceChildren(...options) { this.options = options; }
    append(option) { this.options.push(option); }
    setCustomValidity(message) { this.validationMessage = message; }
};
globalThis.document = { readyState: 'complete', querySelectorAll: () => [] };
globalThis.window = { location: { origin: 'https://marthire.test' } };
const { initLocationSelector } = await import('../../resources/js/location-selector.js');
const tick = () => new Promise(setImmediate);
const options = (...values) => values.map((value) => ({ value, label: value }));

function fixture(selected = {}) {
    const fields = ['country_code', 'state', 'city'];
    const selects = Object.fromEntries(fields.map((field) => [field, new HTMLSelectElement(selected[field])]));
    const statuses = Object.fromEntries(fields.map((field) => [field, { textContent: '' }]));
    const retries = Object.fromEntries(fields.map((field) => [field, Object.assign(new EventTarget(), { hidden: true })]));
    const requiredMarks = Object.fromEntries(fields.map((field) => [field, { hidden: false }]));
    const form = Object.assign(new EventTarget(), {
        dataset: { locationRequired: 'true', locationsCountries: '/api/locations/countries', locationsStates: '/api/locations/states', locationsCities: '/api/locations/cities' },
        querySelector(selector) {
            for (const field of fields) {
                if (selector === `[data-location-${field}]`) return selects[field];
                if (selector === `[data-location-status="${field}"]`) return statuses[field];
                if (selector === `[data-location-retry="${field}"]`) return retries[field];
                if (selector === `[data-location-required-mark="${field}"]`) return requiredMarks[field];
            }
            return null;
        },
    });
    const calls = [];
    globalThis.fetch = (url, config) => new Promise((resolve) => calls.push({ url, config, resolve }));
    const reply = async (index, data, status = 200) => {
        calls[index].resolve({ ok: status === 200, json: async () => ({ data, meta: { available: status === 200, source: 'snapshot' } }) });
        await tick();
    };
    const change = (field, value) => { selects[field].value = value; selects[field].dispatchEvent(new Event('change')); };
    const instance = initLocationSelector(form);
    return { form, selects, statuses, retries, requiredMarks, calls, reply, change, instance };
}

test('restores scoped prefill and makes stateless requests', async () => {
    const f = fixture({ country_code: 'NG', state: 'Lagos', city: 'Ikeja' });
    await f.reply(0, options('NG', 'US'));
    await f.reply(1, options('Lagos', 'Abia'));
    await f.reply(2, options('Ikeja', 'Apapa'));
    await f.instance.ready;
    assert.equal(f.selects.city.value, 'Ikeja');
    assert.equal(f.calls[2].url.searchParams.get('state'), 'Lagos');
    assert.equal(f.calls[2].url.searchParams.get('country'), 'NG');
    assert.equal(f.calls[2].config.credentials, 'omit');
    assert.equal(initLocationSelector(f.form), f.instance);
});

test('clears children and ignores late country responses even when abort is ignored', async () => {
    const f = fixture();
    await f.reply(0, options('NG', 'US'));
    f.change('country_code', 'US');
    f.change('country_code', 'NG');
    assert.equal(f.calls[1].config.signal.aborted, true);
    await f.reply(2, options('Lagos'));
    await f.reply(1, options('California'));
    assert.deepEqual(f.selects.state.options.map((option) => option.value), ['', 'Lagos']);
    assert.equal(f.selects.city.value, '');
});

test('ignores late city results after a new state is selected', async () => {
    const f = fixture({ country_code: 'NG' });
    await f.reply(0, options('NG'));
    await f.reply(1, options('Lagos', 'Abia'));
    f.change('state', 'Lagos');
    f.change('state', 'Abia');
    await f.reply(3, options('Aba'));
    await f.reply(2, options('Ikeja'));
    assert.deepEqual(f.selects.city.options.map((option) => option.value), ['', 'Aba']);
});

test('allows genuine empty tiers with explanatory feedback', async () => {
    const f = fixture({ country_code: 'VA' });
    await f.reply(0, options('VA'));
    await f.reply(1, []);
    await f.instance.ready;
    assert.equal(f.selects.state.disabled, true);
    assert.equal(f.selects.state.required, false);
    assert.equal(f.requiredMarks.state.hidden, true);
    assert.equal(f.selects.city.required, false);
    assert.equal(f.requiredMarks.city.hidden, true);
    assert.match(f.statuses.state.textContent, /may continue without/);
});

test('allows a genuine empty city list while still requiring its country and state', async () => {
    const f = fixture({ country_code: 'GB', state: 'Aberdeen' });
    await f.reply(0, options('GB'));
    await f.reply(1, options('Aberdeen'));
    await f.reply(2, []);
    await f.instance.ready;
    assert.equal(f.selects.country_code.required, true);
    assert.equal(f.selects.state.required, true);
    assert.equal(f.selects.city.disabled, true);
    assert.equal(f.selects.city.required, false);
    assert.equal(f.requiredMarks.city.hidden, true);
    const event = new Event('submit', { cancelable: true });
    f.form.dispatchEvent(event);
    assert.equal(event.defaultPrevented, false);
});

test('shows failure, blocks submit, and retries successfully', async () => {
    const f = fixture();
    await f.reply(0, [], 503);
    assert.equal(f.retries.country_code.hidden, false);
    assert.equal(f.selects.country_code.dataset.unavailable, 'true');
    const event = new Event('submit', { cancelable: true });
    f.form.dispatchEvent(event);
    assert.equal(event.defaultPrevented, true);
    f.retries.country_code.dispatchEvent(new Event('click'));
    await f.reply(1, options('NG'));
    assert.equal(f.selects.country_code.dataset.unavailable, 'false');
    assert.equal(f.selects.country_code.validationMessage, '');
});

test('cancel/reset restores original selections instead of newer pending choices', async () => {
    const f = fixture();
    await f.reply(0, options('NG', 'US'));
    f.change('country_code', 'US');
    f.form.dispatchEvent(new CustomEvent('location:reset', { detail: { country_code: 'NG', state: 'Lagos', city: 'Ikeja' } }));
    await f.reply(2, options('NG', 'US'));
    await f.reply(3, options('Lagos'));
    await f.reply(4, options('Ikeja'));
    await f.reply(1, options('California'));
    assert.equal(f.selects.country_code.value, 'NG');
    assert.equal(f.selects.state.value, 'Lagos');
    assert.equal(f.selects.city.value, 'Ikeja');
});
