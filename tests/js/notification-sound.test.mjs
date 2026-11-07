import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeNotificationSound } from '../../resources/js/notification-sound.js';

const preferenceKey = 'marthire.notification-sound.enabled';

function soundHarness({
    storedPreference = null,
    serverEnabled = false,
    configured = true,
    failSave = false,
    failPlayback = false,
} = {}) {
    const values = new Map();
    if (storedPreference !== null) values.set(preferenceKey, storedPreference);

    const listeners = new Map();
    const attributes = new Map([['aria-checked', 'false']]);
    let playCalls = 0;
    let pauseCalls = 0;
    let sourceUrl = null;
    const requests = [];
    const notifications = [];
    const status = { textContent: '' };
    const control = {
        disabled: false,
        dataset: {},
        addEventListener(name, callback) { listeners.set(name, callback); },
        removeEventListener(name) { listeners.delete(name); },
        setAttribute(name, value) { attributes.set(name, value); },
        getAttribute(name) { return attributes.get(name) ?? null; },
        removeAttribute(name) { attributes.delete(name); },
    };
    const body = {
        dataset: {
            notificationSoundUrl: '/asset/marthire/sounds/notification.mp3',
            notificationSoundUpdateUrl: '/settings/notifications/sound',
            notificationSoundEnabled: serverEnabled ? 'true' : 'false',
            notificationSoundPreferenceConfigured: configured ? 'true' : 'false',
        },
    };
    const win = {
        localStorage: {
            getItem(key) { return values.get(key) ?? null; },
            setItem(key, value) { values.set(key, value); },
        },
        fetch: async (url, options) => {
            requests.push({ url, options });
            if (failSave) return { ok: false, async json() { return { message: 'Save failed.' }; } };
            return {
                ok: true,
                async json() { return { enabled: JSON.parse(options.body).enabled, message: 'Saved.' }; },
            };
        },
        Marthire: { notify(message, options) { notifications.push({ message, options }); } },
        addEventListener(name, callback) { listeners.set(`window:${name}`, callback); },
        removeEventListener(name) { listeners.delete(`window:${name}`); },
    };
    const doc = {
        body,
        querySelector(selector) {
            if (selector === '[data-notification-sound-toggle]') return control;
            if (selector === '[data-notification-sound-status]') return status;
            if (selector === 'meta[name="csrf-token"]') return { content: 'csrf-test-token' };
            return null;
        },
    };
    const sound = initializeNotificationSound({
        window: win,
        document: doc,
        createAudio(url) {
            sourceUrl = url;
            return {
                currentTime: 0,
                volume: 1,
                preload: 'auto',
                async play() {
                    playCalls += 1;
                    if (failPlayback) throw new Error('Playback blocked');
                },
                pause() { pauseCalls += 1; },
            };
        },
    });

    return {
        sound,
        control,
        attributes,
        listeners,
        values,
        body,
        requests,
        notifications,
        status,
        get playCalls() { return playCalls; },
        get pauseCalls() { return pauseCalls; },
        get sourceUrl() { return sourceUrl; },
    };
}

test('sound is off by default and the settings switch saves and previews on click', async () => {
    const fixture = soundHarness();

    assert.equal(fixture.control.getAttribute('aria-checked'), 'false');
    assert.equal(await fixture.sound.play(), false);
    assert.equal(fixture.playCalls, 0);

    const pendingSave = fixture.listeners.get('click')();
    assert.equal(fixture.control.getAttribute('aria-checked'), 'true');
    assert.equal(fixture.playCalls, 1, 'preview starts synchronously from the user gesture');
    await pendingSave;

    assert.equal(fixture.requests.length, 1);
    assert.equal(fixture.requests[0].url, '/settings/notifications/sound');
    assert.equal(fixture.requests[0].options.method, 'PATCH');
    assert.equal(fixture.requests[0].options.credentials, 'same-origin');
    assert.equal(fixture.requests[0].options.headers['X-CSRF-TOKEN'], 'csrf-test-token');
    assert.deepEqual(JSON.parse(fixture.requests[0].options.body), { enabled: true });
    assert.equal(fixture.values.get(preferenceKey), 'true');
    assert.equal(fixture.body.dataset.notificationSoundEnabled, 'true');
    assert.equal(fixture.sound.isEnabled(), true);
    assert.equal(fixture.status.textContent, 'Notification sound is on.');
    assert.equal(fixture.notifications.at(-1).options.type, 'success');
    assert.equal(fixture.sourceUrl, '/asset/marthire/sounds/notification.mp3');
    assert.equal(await fixture.sound.play(), true);
    assert.equal(fixture.playCalls, 2);
    fixture.sound.destroy();
});

test('the server preference is restored and same-browser changes sync the switch', () => {
    const fixture = soundHarness({ serverEnabled: true });

    assert.equal(fixture.control.getAttribute('aria-checked'), 'true');
    assert.equal(fixture.sound.isEnabled(), true);
    assert.equal(fixture.values.get(preferenceKey), 'true');

    void fixture.sound.play();
    fixture.listeners.get('window:storage')({ key: preferenceKey, newValue: 'false' });
    assert.equal(fixture.control.getAttribute('aria-checked'), 'false');
    assert.equal(fixture.sound.isEnabled(), false);
    assert.equal(fixture.pauseCalls, 1);
    fixture.sound.destroy();
});

test('the prior browser preference migrates to the account without an unsolicited preview', async () => {
    const fixture = soundHarness({ storedPreference: 'true', configured: false });
    await new Promise((resolve) => setImmediate(resolve));

    assert.equal(fixture.requests.length, 1);
    assert.deepEqual(JSON.parse(fixture.requests[0].options.body), { enabled: true });
    assert.equal(fixture.sound.isEnabled(), true);
    assert.equal(fixture.body.dataset.notificationSoundPreferenceConfigured, 'true');
    assert.equal(fixture.control.disabled, false);
    assert.equal(fixture.control.getAttribute('aria-busy'), null);
    assert.equal(fixture.playCalls, 0);
    fixture.sound.destroy();
});

test('a failed save reverts the switch and reports the error through the shared toast', async () => {
    const fixture = soundHarness({ failSave: true });

    await fixture.listeners.get('click')();

    assert.equal(fixture.control.getAttribute('aria-checked'), 'false');
    assert.equal(fixture.sound.isEnabled(), false);
    assert.equal(fixture.status.textContent, 'Your notification sound preference could not be saved.');
    assert.equal(fixture.notifications.at(-1).options.type, 'error');
    assert.equal(fixture.pauseCalls, 1, 'a preview is stopped when the enable request fails');
    fixture.sound.destroy();
});

test('blocked playback is swallowed and does not interrupt preference or notification handling', async () => {
    const fixture = soundHarness({ failPlayback: true });

    await fixture.listeners.get('click')();

    assert.equal(fixture.sound.isEnabled(), true);
    assert.equal(await fixture.sound.play(), false);
    assert.equal(fixture.playCalls, 2, 'preview and notification playback were attempted');
    assert.equal(fixture.notifications.at(-1).options.type, 'success');
    fixture.sound.destroy();
});
