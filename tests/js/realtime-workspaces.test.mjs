import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeRealtimeWorkspaces } from '../../resources/js/realtime-workspaces.js';

function realtimeHarness({
    echoAvailable = true,
    withNotificationCount = false,
    initialCount = 2,
    countResponses = [],
} = {}) {
    const updates = new Map();
    const events = new Map();
    const dispatched = [];
    const toasts = [];
    const intervals = new Map();
    const windowListeners = new Map();
    const documentListeners = new Map();
    let intervalId = 0;
    let countFetches = 0;
    let soundPlays = 0;
    const queuedCounts = [...countResponses];
    const badges = [
        {
            dataset: { notificationCountValue: String(initialCount) },
            textContent: String(initialCount),
            hidden: initialCount === 0,
            setAttribute() {},
        },
    ];
    const links = [{ dataset: { notificationLabel: 'Notifications' }, setAttribute() {} }];
    const channel = { listen(name, callback) { updates.set(name, callback); return this; } };
    const doc = {
        hidden: false,
        body: { dataset: {
            workspaceRole: echoAvailable ? 'organization' : '',
            organizationId: echoAvailable ? '23' : '',
            ...(withNotificationCount ? { notificationCountUrl: '/notifications/unread-count' } : {}),
        } },
        querySelector: (selector) => selector === '[data-notification-count]' ? badges[0] : null,
        querySelectorAll: (selector) => selector === '[data-notification-count]'
            ? badges
            : selector === '[data-notification-link]'
                ? links
                : [],
        addEventListener(name, callback) { documentListeners.set(name, callback); },
        removeEventListener(name) { documentListeners.delete(name); },
    };
    const win = {
        Echo: echoAvailable ? { private(name) { assert.equal(name, 'organizations.23'); return channel; }, leave() {} } : null,
        navigator: { onLine: true },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        addEventListener(name, callback) { events.set(name, callback); windowListeners.set(name, callback); },
        removeEventListener(name) { windowListeners.delete(name); },
        setInterval(callback) { intervals.set(++intervalId, callback); return intervalId; },
        clearInterval(id) { intervals.delete(id); },
        dispatchEvent(event) { dispatched.push(event); events.get(event.type)?.(event); },
        Marthire: { notify(message, options) { toasts.push({ message, ...options }); } },
    };
    const teardown = initializeRealtimeWorkspaces({
        window: win,
        document: doc,
        fetch: async () => {
            countFetches += 1;
            const nextCount = queuedCounts.length ? queuedCounts.shift() : initialCount;
            const count = typeof nextCount === 'function' ? await nextCount() : nextCount;
            return { ok: true, async json() { return { count }; } };
        },
        notificationSound: { play() { soundPlays += 1; } },
    });
    return {
        callback: updates.get('.workspace.updated'),
        dispatched,
        toasts,
        intervals,
        events,
        windowListeners,
        documentListeners,
        badges,
        links,
        get countFetches() { return countFetches; },
        get soundPlays() { return soundPlays; },
        teardown,
    };
}

const flushAsyncWork = () => new Promise((resolve) => setImmediate(resolve));

test('Reverb workspace updates invalidate allow-listed topics and use the shared toast', () => {
    const fixture = realtimeHarness();
    fixture.callback({
        topics: ['applications', 'private-data', 'applications', 'notifications'],
        message: 'Application update received.',
        type: 'success',
    });

    assert.equal(fixture.dispatched.length, 1);
    assert.deepEqual(fixture.dispatched[0].detail.topics, ['applications', 'notifications']);
    assert.deepEqual(fixture.toasts, [{ message: 'Application update received.', type: 'success' }]);
    fixture.teardown();
});

test('Reverb refreshes topics without showing an empty toast', () => {
    const fixture = realtimeHarness();
    fixture.callback({ topics: ['notifications'], message: null });

    assert.equal(fixture.dispatched.length, 1);
    assert.deepEqual(fixture.dispatched[0].detail.topics, ['notifications']);
    assert.equal(fixture.toasts.length, 0);
    fixture.teardown();
});

test('notification count refresh falls back to polling when Echo is unavailable', async () => {
    const fixture = realtimeHarness({
        echoAvailable: false,
        withNotificationCount: true,
        countResponses: [2, 3],
    });
    await flushAsyncWork();
    assert.equal(fixture.intervals.size, 1);
    fixture.intervals.values().next().value();
    await flushAsyncWork();

    assert.equal(fixture.countFetches, 2, 'initial count plus one degraded-mode poll');
    assert.equal(fixture.soundPlays, 1, 'the fallback poll sounds when it discovers a new unread notification');
    fixture.teardown();
    assert.equal(fixture.intervals.size, 0);
    assert.equal(fixture.windowListeners.has('nw:data-changed'), false);
});

test('sound plays once only when a Reverb update increases the unread count', async () => {
    const fixture = realtimeHarness({
        withNotificationCount: true,
        initialCount: 2,
        countResponses: [2, 3, 3, 1],
    });
    await flushAsyncWork();
    assert.equal(fixture.soundPlays, 0, 'initial count synchronization is silent');

    fixture.callback({ topics: ['notifications'] });
    await flushAsyncWork();
    assert.equal(fixture.soundPlays, 1);
    assert.equal(fixture.badges[0].dataset.notificationCountValue, '3');

    fixture.callback({ topics: ['notifications'] });
    await flushAsyncWork();
    assert.equal(fixture.soundPlays, 1, 'a duplicate update with the same count is silent');

    fixture.callback({ topics: ['notifications'] });
    await flushAsyncWork();
    assert.equal(fixture.soundPlays, 1, 'a lower unread count is silent');
    fixture.teardown();
});

test('a Reverb event arriving during a count request gets a fresh count check', async () => {
    let resolveInitialCount;
    const fixture = realtimeHarness({
        withNotificationCount: true,
        initialCount: 2,
        countResponses: [
            () => new Promise((resolve) => { resolveInitialCount = resolve; }),
            3,
        ],
    });
    await flushAsyncWork();
    assert.equal(fixture.countFetches, 1);

    fixture.callback({ topics: ['notifications'] });
    resolveInitialCount(2);
    await flushAsyncWork();

    assert.equal(fixture.countFetches, 2);
    assert.equal(fixture.badges[0].dataset.notificationCountValue, '3');
    assert.equal(fixture.soundPlays, 1);
    fixture.teardown();
});

test('the first count refresh to find a new unread notification plays the sound', async () => {
    const fixture = realtimeHarness({
        withNotificationCount: true,
        initialCount: 2,
        countResponses: [2, 3, 4, 5, 6],
    });
    await flushAsyncWork();

    fixture.events.get('nw:data-changed')({
        detail: { topics: ['notifications'], source: 'notification-menu-open' },
    });
    await flushAsyncWork();

    fixture.intervals.values().next().value();
    await flushAsyncWork();

    fixture.windowListeners.get('online')();
    await flushAsyncWork();

    fixture.documentListeners.get('visibilitychange')();
    await flushAsyncWork();

    assert.equal(fixture.soundPlays, 4, 'each observed unread-count increase plays once');
    assert.equal(fixture.badges[0].dataset.notificationCountValue, '6');
    fixture.teardown();
});
