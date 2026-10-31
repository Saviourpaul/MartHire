import test from 'node:test';
import assert from 'node:assert/strict';
import { createLatestTableRequest, tableFilterUrl, tableJson, topicsOverlap } from '../../resources/js/live-table-transport.js';

const response = (body, status = 200) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
const deferred = () => { let resolve; const promise = new Promise((done) => { resolve = done; }); return { promise, resolve }; };

test('filter changes reset independent pagers while preserving unrelated query state', () => {
    const next = new URL(tableFilterUrl('/team', [['search', 'Ada'], ['role', 'recruiter']], 'https://example.test/team?members_page=3&invitations_page=2&context=staff', ['members_page', 'invitations_page']));
    assert.equal(next.searchParams.get('context'), 'staff');
    assert.equal(next.searchParams.get('search'), 'Ada');
    assert.equal(next.searchParams.has('members_page'), false);
    assert.equal(next.searchParams.has('invitations_page'), false);
});

test('clearing filters removes prior values and preserves repeated unmodified parameters', () => {
    const next = new URL(tableFilterUrl('/users', [['search', ''], ['status', 'active']], 'https://example.test/users?search=old&page=2&tag=a&tag=b'));
    assert.equal(next.searchParams.has('search'), false);
    assert.equal(next.searchParams.has('page'), false);
    assert.deepEqual(next.searchParams.getAll('tag'), ['a', 'b']);
    assert.equal(next.searchParams.get('status'), 'active');
    assert.throws(() => tableFilterUrl('https://external.test/users', [], next.href));
});

test('table reads keep credentials, bypass stale caches and cancel preceding requests', async () => {
    const first = deferred();
    const calls = [];
    const request = createLatestTableRequest((url, options) => { calls.push(options); return url === '/first' ? first.promise : Promise.resolve(response({ regions: { users: 'new' } })); });
    const old = request.run('/first');
    const latest = await request.run('/second');
    first.resolve(response({ regions: { users: 'old' } }));
    assert.equal(await old, null);
    assert.equal(latest.regions.users, 'new');
    assert.equal(calls[0].signal.aborted, true);
    assert.equal(calls[1].credentials, 'same-origin');
    assert.equal(calls[1].cache, 'no-store');
});

test('typing invalidates a pending result before the debounced replacement starts', async () => {
    const pending = deferred();
    const request = createLatestTableRequest(() => pending.promise);
    const old = request.run('/users?search=a');
    request.invalidate();
    pending.resolve(response({ regions: { users: 'obsolete' } }));
    assert.equal(await old, null);
});

test('late body parsing cannot overwrite a more recent successful table response', async () => {
    const body = deferred();
    const request = createLatestTableRequest(async (url) => url === '/slow'
        ? { ok: true, status: 200, json: () => body.promise }
        : response({ regions: { users: 'current' } }));
    const slow = request.run('/slow');
    await Promise.resolve();
    await request.run('/fast');
    body.resolve({ regions: { users: 'old' } });
    assert.equal(await slow, null);
});

test('validation errors retain field messages and a failed request can be retried', async () => {
    const error = await tableJson(response({ message: 'Invalid input.', errors: { email: ['Already invited.'] } }, 422)).catch((error) => error);
    assert.equal(error.status, 422);
    assert.deepEqual(error.errors.email, ['Already invited.']);
    let attempts = 0;
    const request = createLatestTableRequest(async () => ++attempts === 1 ? response({ message: 'Internal SQL detail' }, 500) : response({ regions: { users: 'recovered' } }));
    await assert.rejects(request.run('/users'), /server could not complete/);
    assert.equal((await request.run('/users')).regions.users, 'recovered');
});

test('expired and forbidden sessions fail without accepting a redirected login document', async () => {
    await assert.rejects(tableJson({ redirected: true, status: 200, json: async () => ({}) }), /session may have expired/);
    await assert.rejects(tableJson(response({}, 403)), /permission/);
    await assert.rejects(tableJson(response({}, 419)), /session has expired/);
});

test('cross-tab changes refresh only matching resource topics without carrying row data', () => {
    assert.equal(topicsOverlap(['jobs', 'applications'], ['jobs']), true);
    assert.equal(topicsOverlap(['team'], ['jobs']), false);
    assert.equal(topicsOverlap(['jobs'], []), true);
});
