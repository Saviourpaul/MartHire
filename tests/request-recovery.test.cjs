const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { JSDOM } = require('jsdom');

const script = readFileSync('public/assets/js/request-recovery.js', 'utf8');

function fixture() {
    const dom = new JSDOM(
        '<form><input name="draft" value="Unsaved hiring notes"></form>' +
        '<div id="request-recovery" hidden class="hidden" role="alert">' +
        '<p data-recovery-message></p><a data-recovery-action></a>' +
        '<button data-recovery-dismiss>Dismiss</button></div>',
        { url: 'http://marthire.test/jobs', runScripts: 'outside-only' },
    );
    let networkCalls = 0;
    dom.window.fetch = () => { networkCalls++; };
    dom.window.eval(script);
    return { window: dom.window, document: dom.window.document, networkCalls: () => networkCalls };
}

test('lost sessions offer login without discarding draft input or replaying a request', async () => {
    const page = fixture();
    const message = 'Please sign in to continue. Your session may have expired.';
    await page.window.MarthireRecovery.handle({ status: 401 }, {
        message, recovery_url: '/login', recovery_label: 'Sign in again',
    });
    assert.equal(page.document.getElementById('request-recovery').hidden, false);
    assert.equal(page.document.querySelector('[data-recovery-message]').textContent, message);
    assert.equal(page.document.querySelector('[data-recovery-action]').href, 'http://marthire.test/login');
    assert.equal(page.document.querySelector('[data-recovery-action]').textContent, 'Sign in again');
    assert.equal(page.document.querySelector('[name="draft"]').value, 'Unsaved hiring notes');
    assert.equal(page.networkCalls(), 0);
    assert.equal(page.window.location.href, 'http://marthire.test/jobs');
    page.document.querySelector('[data-recovery-dismiss]').click();
    assert.equal(page.document.getElementById('request-recovery').hidden, true);
    page.window.close();
});

test('CSRF recovery escapes text and leaves the original response readable', async () => {
    const page = fixture();
    const message = '<img src=x onerror=unsafe()> Your form expired.';
    const response = new Response(JSON.stringify({ message, recovery_url: '/Dashboard' }), {
        status: 419, headers: { 'Content-Type': 'application/json' },
    });
    await page.window.MarthireRecovery.handle(response);
    assert.equal(page.document.querySelector('[data-recovery-message]').textContent, message);
    assert.equal(page.document.querySelector('img'), null);
    assert.equal((await response.json()).message, message);
    page.window.close();
});

test('expired links use their specific recovery action', async () => {
    const page = fixture();
    await page.window.MarthireRecovery.handle({ status: 410 }, {
        message: 'Reset link expired.', recovery_url: '/forgot-password', recovery_label: 'Request a new reset link',
    });
    assert.equal(page.document.querySelector('[data-recovery-action]').href, 'http://marthire.test/forgot-password');
    assert.equal(page.document.querySelector('[data-recovery-action]').textContent, 'Request a new reset link');
    page.window.close();
});

test('ordinary permission failures stay separate from signed link recovery', async () => {
    const page = fixture();
    assert.equal(await page.window.MarthireRecovery.handle({ status: 403 }, {
        message: 'Forbidden', recovery_url: '/login',
    }), null);
    assert.equal(page.document.getElementById('request-recovery').hidden, true);
    await page.window.MarthireRecovery.handle({ status: 403 }, {
        code: 'signed_invalid', message: 'Link invalid', recovery_url: '/',
    });
    assert.equal(page.document.getElementById('request-recovery').hidden, false);
    page.window.close();
});

test('untrusted destinations cannot redirect or become recovery links', async () => {
    for (const url of ['https://attacker.test/login', 'javascript:unsafe()', '//attacker.test/login']) {
        const page = fixture();
        assert.equal(await page.window.MarthireRecovery.handle({ status: 401 }, {
            message: 'Expired', recovery_url: url,
        }), null);
        assert.equal(page.document.getElementById('request-recovery').hidden, true);
        assert.equal(page.window.location.href, 'http://marthire.test/jobs');
        page.window.close();
    }
});

test('irrelevant and malformed responses do not disrupt request handling', async () => {
    const page = fixture();
    assert.equal(await page.window.MarthireRecovery.handle({ status: 500 }, {}), null);
    assert.equal(await page.window.MarthireRecovery.handle({ status: 401 }), null);
    assert.equal(await page.window.MarthireRecovery.handle(null), null);
    const response = new Response('null', { status: 419 });
    assert.equal(await page.window.MarthireRecovery.handle(response), null);
    page.window.close();
});
