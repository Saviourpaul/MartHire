import test from 'node:test';
import assert from 'node:assert/strict';
import { createDeferredPhoneInitializer } from '../../resources/js/deferred-phone.js';

function fixture({ loadPhoneSupport = async () => {}, onError = () => {} } = {}) {
    const attributes = new Set(['data-phone-field', 'data-phone-deferred', 'x-ignore']);
    const input = { value: '(555) 123-4567' };
    const country = { value: 'US' };
    const field = {
        isConnected: true,
        hasAttribute: (name) => attributes.has(name),
        removeAttribute: (name) => attributes.delete(name),
        querySelector: (selector) => selector === '[data-phone-input]' ? input : country,
    };
    const root = { querySelectorAll: () => attributes.has('data-phone-deferred') ? [field] : [] };
    const calls = [];
    const phone = { phoneCountry: 'NG', formatPhone(value, inferCountry) { calls.push(['formatPhone', value, inferCountry]); } };
    const Alpine = {
        destroyTree: (element) => calls.push(['destroyTree', element]),
        initTree: (element) => calls.push(['initTree', element]),
        $data: () => phone,
    };
    return {
        attributes, calls, field, input, country, phone,
        initialize: createDeferredPhoneInitializer({ Alpine, loadPhoneSupport, onError }).initialize,
    };
}

test('deferred phone support loads only when the editor is opened and preserves native field values', async () => {
    let loads = 0;
    const state = fixture({ loadPhoneSupport: async () => { loads++; } });
    assert.equal(loads, 0);

    assert.equal(await state.initialize({ querySelectorAll: () => [state.field] }), true);
    assert.equal(loads, 1);
    assert.equal(state.attributes.has('data-phone-deferred'), false);
    assert.equal(state.attributes.has('x-ignore'), false);
    assert.equal(state.phone.phoneCountry, 'US');
    assert.deepEqual(state.calls.map(([name]) => name), ['destroyTree', 'initTree', 'formatPhone']);
    assert.deepEqual(state.calls[2].slice(1), ['(555) 123-4567', false]);
});

test('a failed lazy load keeps the native fallback available and reports the failure', async () => {
    const failure = new Error('phone module unavailable');
    let reported;
    const state = fixture({ loadPhoneSupport: async () => { throw failure; }, onError: (error) => { reported = error; } });

    assert.equal(await state.initialize({ querySelectorAll: () => [state.field] }), false);
    assert.equal(reported, failure);
    assert.equal(state.attributes.has('data-phone-deferred'), true);
    assert.equal(state.attributes.has('x-ignore'), true);
    assert.deepEqual(state.calls, []);
});
