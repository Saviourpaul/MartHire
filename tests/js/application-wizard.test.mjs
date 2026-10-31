import test, { afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { createApplicationForm } from '../../resources/js/application-form.js';

const originalGlobals = Object.fromEntries(['fetch', 'FormData', 'window', 'document', 'CSS'].map((name) => [name, globalThis[name]]));
afterEach(() => {
    for (const [name, value] of Object.entries(originalGlobals)) {
        if (value === undefined) delete globalThis[name];
        else globalThis[name] = value;
    }
});

const focusTarget = () => ({ focused: 0, focus() { this.focused += 1; }, scrollIntoView() {} });
const file = (name = 'cv.pdf', size = 1024, type = 'application/pdf') => ({ name, size, type });

// These fixtures implement only the browser APIs used by the public methods.
// Panels and controls remain the same objects throughout navigation.
function control({ type = 'text', value = '', files = [], checked = false, label = '', ...attributes } = {}) {
    let currentValue = type === 'file' && files.length ? `C:\\fakepath\\${files[0].name}` : value;
    return {
        ...focusTarget(), type, tagName: type === 'select' ? 'SELECT' : 'INPUT',
        name: '', id: '', disabled: false, required: false, minLength: -1,
        files, checked, validationMessage: '', ...attributes,
        get value() { return currentValue; },
        set value(next) { currentValue = next; if (this.type === 'file' && next === '') this.files = []; },
        get selectedOptions() { return [{ textContent: label || this.value }]; },
        checkValidity() { return this.valid !== false; },
        closest(selector) {
            if (selector === '[data-field]') return this.field;
            if (selector === 'label') return { textContent: label || this.value };
            return null;
        },
    };
}

function field(name, { type = 'text', required = false, mustAccept = false, location = false, errorKey,
    accept = '', max = '', controls, value = '', selectedFile = null, ...attributes } = {}) {
    const inputs = controls ?? [control({ type, value, files: selectedFile ? [selectedFile] : [] })];
    const flags = new Set([
        ...(required ? ['data-required'] : []), ...(mustAccept ? ['data-must-accept'] : []),
        ...(location ? ['data-location-field'] : []),
    ]);
    const result = {
        dataset: { field: name, type, label: name, errorKey, accept, max: String(max), ...attributes },
        controls: inputs,
        hasAttribute(name) { return flags.has(name); },
        querySelectorAll(selector) { return selector === 'input, select, textarea' ? this.controls : []; },
        querySelector(selector) {
            if (selector === 'input:checked') return this.controls.find((input) => input.checked) ?? null;
            return null;
        },
        closest(selector) { return selector === '[data-wizard-step]' ? this.panel : null; },
    };
    inputs.forEach((input, index) => {
        input.field = result;
        input.name ||= name;
        input.id ||= `${name.replace(/[^a-z0-9]/gi, '-')}-${index}`;
    });
    return result;
}

function fixture(groups, options = {}) {
    const panels = groups.map((fields, index) => {
        const panel = {
            dataset: { stepTitle: `Section ${index + 1}`, stepDescription: `Description ${index + 1}` },
            fields, heading: focusTarget(),
            querySelectorAll(selector) { return selector === '[data-field]' ? this.fields : []; },
            querySelector(selector) {
                if (selector === '[data-wizard-heading]') return this.heading;
                if (selector === '[data-location-field]') return this.fields.find((item) => item.hasAttribute(selector.slice(1, -1))) ?? null;
                return null;
            },
        };
        fields.forEach((item) => { item.panel = panel; });
        return panel;
    });
    const fields = panels.flatMap((panel) => panel.fields);
    const form = {
        action: 'https://marthire.test/workable-jobs/ROLE/apply', panels, fields,
        querySelectorAll(selector) {
            if (selector === '[data-wizard-step]') return this.panels;
            if (selector === '[data-field]') return this.fields;
            return [];
        },
        querySelector(selector) {
            const match = selector.match(/^\[data-field="(.*)"\] input\[type="file"\]$/);
            return match ? this.fields.find((item) => item.dataset.field === match[1])?.controls.find((input) => input.type === 'file') ?? null : null;
        },
    };
    const app = createApplicationForm(options);
    app.$refs = { form, summary: focusTarget(), success: focusTarget(), submissionError: focusTarget() };
    app.$nextTick = (callback) => callback();
    globalThis.window = { location: { href: 'https://marthire.test/apply', assign() {} }, matchMedia: () => ({ matches: true }) };
    globalThis.document = { getElementById: (id) => fields.flatMap((item) => item.controls).find((input) => input.id === id) ?? null };
    globalThis.CSS = { escape: (value) => value };
    app.init();
    return { app, panels, fields, form };
}

const event = () => ({ prevented: false, preventDefault() { this.prevented = true; } });
const confirmation = (checked = true) => field('confirm', {
    type: 'boolean', required: true, mustAccept: true,
    controls: [control({ type: 'checkbox', value: '1', checked })],
});

function nativeLocationFixture(countryCode = 'NG') {
    const country = field('country_code', { type: 'dropdown', required: true, controls: [control({ type: 'select' })] });
    const state = field('state', { type: 'dropdown', required: true, location: true, controls: [control({ type: 'select' })] });
    const city = field('city', { type: 'dropdown', required: true, location: true, controls: [control({ type: 'select' })] });
    const lga = field('local_government_area', { type: 'dropdown', required: true, location: true, controls: [control({ type: 'select' })] });
    // Start without a country so test setup does not fire hydration requests.
    const result = fixture([[country, state, city, lga], [confirmation()]], { mode: 'native', preview: false });
    const setLocation = (code) => {
        const nigeria = code === 'NG';
        result.app.countryCode = country.controls[0].value = code;
        result.app.state = state.controls[0].value = nigeria ? 'Lagos' : 'England';
        result.app.city = city.controls[0].value = nigeria ? '' : 'London';
        result.app.lga = lga.controls[0].value = nigeria ? 'Ikeja' : '';
        city.controls[0].disabled = nigeria;
        lga.controls[0].disabled = !nigeria;
    };
    setLocation(countryCode);
    return { ...result, country, state, city, lga, setLocation };
}

test('form wizard discovers steps and typing or clearing errors never advances progress', () => {
    const name = field('firstname', { required: true });
    const { app } = fixture([[name], [], [confirmation()]]);
    assert.equal(app.steps.length, 3);
    assert.equal(app.steps[0].title, 'Section 1');
    app.errors.firstname = 'Enter your name.';
    app.summary = app.listErrors();
    name.controls[0].value = 'Ada';
    app.handleInput({ target: name.controls[0] });
    app.clear('firstname');
    assert.equal(app.step, 0);
    assert.equal(app.progress, 0);
    assert.equal(app.error('firstname'), '');
    assert.deepEqual(app.summary, []);
});

test('invalid current step blocks Next and focuses its error summary', () => {
    const name = field('firstname', { required: true });
    const { app } = fixture([[name], [], [confirmation()]]);
    assert.equal(app.next(), false);
    assert.equal(app.step, 0);
    assert.equal(app.progress, 0);
    assert.equal(app.error('firstname'), 'This is required.');
    assert.equal(app.summary[0].step, 0);
    assert.equal(app.$refs.summary.focused, 1);
});

test('phone validation and review use the real number instead of picker controls', () => {
    const phoneInput = control({ type: 'tel', value: '(202) 555-0123', valid: false, validationMessage: 'Invalid phone.',
        dataset: { e164: '+12025550123' }, hasAttribute: (name) => name === 'data-phone-input' });
    const search = control({ type: 'search', value: 'United States', hasAttribute: (name) => name === 'data-phone-search' });
    const phone = field('phone', { type: 'tel', required: true, controls: [control({ type: 'select', value: 'US' }), phoneInput, search] });
    const { app } = fixture([[phone], [confirmation()]]);

    assert.equal(app.controlFor(phone), phoneInput);
    assert.equal(app.next(), false);
    assert.equal(app.error('phone'), 'Invalid phone.');
    app.handleInput({ target: search });
    assert.equal(app.error('phone'), 'Invalid phone.');
    phoneInput.valid = true;
    app.handleInput({ target: phoneInput });
    assert.equal(app.next(), true);
    assert.deepEqual(app.review[0].entries, [{ label: 'phone', value: '+12025550123' }]);
});

test('phone country validation errors map to the phone field and focus its number', () => {
    const input = control({ type: 'tel', value: '2025550123', hasAttribute: (name) => name === 'data-phone-input' });
    const phone = field('phone', { type: 'tel', controls: [input] });
    const { app } = fixture([[phone], [confirmation()]]);
    app.restoreErrors({ phone_country: ['Choose a valid phone country.'] });

    assert.equal(app.error('phone'), 'Choose a valid phone country.');
    assert.equal(app.summary[0].target, input.id);
    assert.equal(app.unmappedErrors.length, 0);
    assert.equal(app.step, 0);
});

test('successful Next advances progress, while Back preserves file controls and selected values', () => {
    const attachment = file();
    const name = field('firstname', { required: true, value: 'Ada' });
    const resume = field('resume', { type: 'file', required: true, selectedFile: attachment, accept: 'pdf' });
    const { app, form } = fixture([[name], [resume], [confirmation()]]);
    const input = resume.controls[0];
    const inputValue = input.value;
    app.pick({ target: input });
    assert.equal(app.next(), true);
    assert.equal(app.step, 1);
    assert.equal(app.progress, 50);
    assert.equal(app.next(), true);
    assert.equal(app.step, 2);
    assert.equal(app.progress, 100);
    assert.equal(app.review[1].entries[0].value, attachment.name);
    app.back();
    assert.equal(app.step, 1);
    assert.equal(app.progress, 50);
    assert.equal(form.fields[1].controls[0], input);
    assert.equal(input.files[0], attachment);
    assert.equal(input.value, inputValue);
    assert.equal(name.controls[0].value, 'Ada');
});

test('Enter submission before the final step performs Next without making a network request', async () => {
    const { app } = fixture([[field('firstname', { value: 'Ada', required: true })], [], [confirmation()]], { preview: false });
    let requests = 0;
    globalThis.fetch = async () => { requests += 1; throw new Error('No submission expected'); };
    globalThis.FormData = class { constructor() { throw new Error('No request body expected'); } };
    const submit = event();
    await app.submit(submit);
    assert.equal(submit.prevented, true);
    assert.equal(app.step, 1);
    assert.equal(app.progress, 50);
    assert.equal(requests, 0);
    assert.equal(app.submitting, false);
});

test('final submission validates every step and opens the first invalid earlier step', async () => {
    const name = field('firstname', { required: true, value: 'Ada' });
    const { app } = fixture([[name], [], [confirmation(false)]], { preview: false });
    let requests = 0;
    globalThis.fetch = async () => { requests += 1; };
    app.moveTo(2, false);
    name.controls[0].value = '';
    await app.submit(event());
    assert.equal(requests, 0);
    assert.equal(app.step, 0);
    assert.equal(app.error('firstname'), 'This is required.');
    assert.ok(app.error('confirm'));
    assert.equal(app.summary[0].target, name.controls[0].id);
    assert.equal(app.done, false);
});

test('current-step validation preserves errors in other steps', () => {
    const name = field('firstname', { required: true, value: 'Ada' });
    const resume = field('resume', { type: 'file', required: true });
    const { app } = fixture([[name], [resume], [confirmation()]]);
    app.restoreErrors({ resume: ['The upload was rejected.'] });
    app.moveTo(0, false);
    app.errors.firstname = 'An obsolete local message';
    assert.equal(app.validate(0), true);
    assert.equal(app.error('firstname'), '');
    assert.equal(app.error('resume'), 'The upload was rejected.');
    assert.equal(app.summary[0].step, 1);
    assert.equal(app.next(), true);
    assert.equal(app.step, 1);
    assert.equal(app.next(), false);
});

test('canonical Workable name, wildcard choice and file errors map to the right controls and steps', () => {
    const name = field('firstname', { value: 'Ada', errorKey: 'first_name' });
    const portfolio = field('answer_files[portfolio]', { type: 'file', errorKey: 'question_portfolio' });
    const choices = field('answers[q_multi][]', {
        type: 'multiple_choice', errorKey: 'question_q_multi',
        controls: [control({ type: 'checkbox', value: 'choice-1', checked: true })],
    });
    const freeText = field('answers[q_free]', { value: 'My motivation' });
    const { app } = fixture([[name], [portfolio], [choices, freeText], [confirmation()]]);
    app.moveTo(3, false);
    app.restoreErrors({
        'question_q_multi.0': ['Choose a permitted answer.'],
        question_portfolio: ['Attach a readable portfolio.'],
        first_name: ['Use your full first name.'],
        question_q_free: ['Provide more detail.'],
        unrecognized_field: ['The employer also requires another answer.'],
    });
    assert.equal(app.error('firstname'), 'Use your full first name.');
    assert.equal(app.error('answers[q_multi][]'), 'Choose a permitted answer.');
    assert.equal(app.error('answer_files[portfolio]'), 'Attach a readable portfolio.');
    assert.equal(app.error('answers[q_free]'), 'Provide more detail.');
    assert.equal(app.step, 0);
    assert.equal(app.unmappedErrors[0].name, 'unrecognized_field');
    app.goTo(app.summary.find((item) => item.name === 'answer_files[portfolio]'));
    assert.equal(app.step, 1);
    assert.equal(portfolio.controls[0].focused, 1);
});

test('JSON 422 responses retain input values and selected files while reopening the first rejected step', async () => {
    const attachment = file();
    const name = field('firstname', { required: true, value: 'Ada', errorKey: 'first_name' });
    const resume = field('resume', { required: true, type: 'file', selectedFile: attachment, accept: 'pdf' });
    const answer = field('answers[motivation]', { value: 'I enjoy building reliable software.' });
    const { app, form } = fixture([[name], [resume, answer], [confirmation()]], { preview: false });
    const input = resume.controls[0];
    const originalFiles = input.files;
    const originalValue = input.value;
    app.pick({ target: input });
    app.moveTo(2, false);
    let request;
    globalThis.FormData = class {
        constructor(element) {
            assert.equal(element, form);
            this.resume = element.fields.find((item) => item.dataset.field === 'resume').controls[0].files[0];
        }
    };
    globalThis.fetch = async (url, options) => {
        request = { url, ...options };
        return {
            ok: false, status: 422, headers: { get: () => 'application/json; charset=utf-8' },
            json: async () => ({ errors: { first_name: ['This name needs review.'], resume: ['Please provide a more recent résumé.'] } }),
        };
    };
    await app.submit(event());
    assert.equal(request.method, 'POST');
    assert.equal(request.body.resume, attachment);
    assert.equal(request.credentials, 'same-origin');
    assert.equal(app.step, 0);
    assert.equal(app.submitting, false);
    assert.equal(app.done, false);
    assert.equal(name.controls[0].value, 'Ada');
    assert.equal(answer.controls[0].value, 'I enjoy building reliable software.');
    assert.equal(resume.controls[0], input);
    assert.equal(input.files, originalFiles);
    assert.equal(input.files[0], attachment);
    assert.equal(input.value, originalValue);
    assert.equal(app.files.resume.name, attachment.name);
    assert.equal(app.error('resume'), 'Please provide a more recent résumé.');
});

test('scalar fields enforce required values and browser constraints without failing optional unknown fields', () => {
    const unknown = field('unsupported_optional', { type: 'unrecognized', controls: [] });
    const name = field('firstname', { required: true });
    const email = field('email', { type: 'email', value: 'invalid-address' });
    const number = field('years', { type: 'numeric', value: 'NaN' });
    const date = field('birthday', { type: 'date', value: '2026-10-01', controls: [control({ type: 'date', value: '2026-10-01', valid: false, validationMessage: 'Choose an earlier date.' })] });
    const { app } = fixture([[unknown, name, email, number, date], [confirmation()]]);
    assert.equal(app.problemWith(unknown), '');
    assert.equal(app.problemWith(name), 'This is required.');
    assert.match(app.problemWith(email), /valid email/);
    assert.equal(app.problemWith(number), 'Enter a number.');
    assert.equal(app.problemWith(date), 'Choose an earlier date.');
    name.controls[0].value = 'Ada';
    name.controls[0].minLength = 5;
    assert.equal(app.problemWith(name), 'Enter at least 5 characters.');
});

test('ordinary required choices accept No, while accepted booleans and consent require Yes', () => {
    const yes = control({ type: 'radio', value: '1', checked: false, label: 'Yes' });
    const no = control({ type: 'radio', value: '0', checked: true, label: 'No' });
    const answer = field('answers[eligible]', { type: 'boolean', required: true, controls: [yes, no] });
    const accepted = field('answers[accepted]', { type: 'boolean', required: true, mustAccept: true, controls: [control({ type: 'radio', value: '0', checked: true })] });
    const consent = confirmation(false);
    const { app } = fixture([[answer, accepted], [consent]]);
    assert.equal(app.problemWith(answer), '');
    assert.equal(app.problemWith(accepted), 'Please confirm this statement to continue.');
    assert.equal(app.problemWith(consent), 'Please choose an answer.');
    accepted.controls[0].value = '1';
    consent.controls[0].checked = true;
    assert.equal(app.problemWith(accepted), '');
    assert.equal(app.problemWith(consent), '');
    yes.checked = false;
    no.checked = false;
    assert.equal(app.problemWith(answer), 'Please choose an answer.');
});

test('invalid file type and size errors stay useful after validation until a valid replacement is picked', () => {
    const resume = field('resume', { type: 'file', required: true, accept: '.pdf,.docx', max: 1048576 });
    const { app } = fixture([[resume], [confirmation()]]);
    const input = resume.controls[0];
    input.files = [file('program.exe', 100, 'application/octet-stream')];
    input.value = 'C:\\fakepath\\program.exe';
    app.pick({ target: input });
    assert.match(app.error('resume'), /file type isn't supported/);
    assert.equal(input.files.length, 0);
    assert.equal(app.validate(0), false);
    assert.match(app.error('resume'), /file type isn't supported/);
    input.files = [file('large.pdf', 2 * 1048576)];
    input.value = 'C:\\fakepath\\large.pdf';
    app.pick({ target: input });
    assert.match(app.error('resume'), /2\.0 MB.*1\.0 MB/);
    assert.equal(app.validate(0), false);
    assert.match(app.error('resume'), /2\.0 MB/);
    input.files = [file('valid.pdf', 512000)];
    input.value = 'C:\\fakepath\\valid.pdf';
    app.pick({ target: input });
    assert.equal(app.validate(0), true);
    assert.equal(app.error('resume'), '');
    assert.equal(app.files.resume.name, 'valid.pdf');
    assert.equal(app.progress, 0);
});

test('file accept lists support MIME types, wildcards, extensions and conventional comma whitespace', () => {
    const upload = field('photo', { type: 'file', accept: 'application/pdf, image/*, .docx' });
    const { app } = fixture([[upload], [confirmation()]]);
    assert.equal(app.fileProblem(upload, file('without-extension', 100, 'application/pdf')), '');
    assert.equal(app.fileProblem(upload, file('portrait.png', 100, 'image/png')), '');
    assert.equal(app.fileProblem(upload, file('cv.docx', 100, '')), '');
    assert.match(app.fileProblem(upload, file('video.mp4', 100, 'video/mp4')), /isn't supported/);
});

test('native country changes after JSON 422 clear dependent errors in both Nigeria and international directions', async () => {
    for (const [from, to, rejectedField] of [['NG', 'GB', 'local_government_area'], ['GB', 'NG', 'city']]) {
        const { app, country, setLocation } = nativeLocationFixture(from);
        globalThis.FormData = class {};
        globalThis.fetch = async () => ({
            ok: false, status: 422, headers: { get: () => 'application/json' },
            json: async () => ({ errors: {
                state: ['Choose a state in the selected country.'],
                [rejectedField]: ['Choose a valid location within that state.'],
            } }),
        });
        app.moveTo(1, false);
        await app.submit(event());
        assert.ok(app.serverErrors.state);
        assert.ok(app.serverErrors[rejectedField]);
        assert.equal(app.step, 0);

        // Apply the dependent control bindings as Alpine does after changing country.
        setLocation(to);
        app.handleInput({ target: country.controls[0] });
        for (const name of ['country_code', 'state', 'city', 'local_government_area']) {
            assert.equal(app.errors[name], undefined, `${from} → ${to}: stale ${name} error`);
            assert.equal(app.serverErrors[name], undefined, `${from} → ${to}: stale ${name} server error`);
        }
        assert.equal(app.validate(0), true);
        assert.equal(app.next(), true);
        assert.equal(app.step, 1);
    }
});

test('native state changes clear errors belonging to the prior city and LGA', () => {
    const { app, state } = nativeLocationFixture('NG');
    app.restoreErrors({
        state: ['A stale state error.'], city: ['A stale city error.'],
        local_government_area: ['A stale LGA error.'],
    });
    app.state = state.controls[0].value = 'Abuja';
    app.handleInput({ target: state.controls[0] });
    for (const name of ['state', 'city', 'local_government_area']) {
        assert.equal(app.errors[name], undefined);
        assert.equal(app.serverErrors[name], undefined);
    }
    assert.deepEqual(app.summary, []);
});

test('validation discards late server errors for disabled city and LGA branches', () => {
    for (const [country, inactiveField] of [['NG', 'city'], ['GB', 'local_government_area']]) {
        const { app } = nativeLocationFixture(country);
        // A response from the previously selected country may arrive after its
        // branch is disabled; validating the active step must not keep it blocked.
        app.restoreErrors({ [inactiveField]: ['This branch is no longer submitted.'] });
        assert.ok(app.serverErrors[inactiveField]);
        assert.equal(app.validate(0), true);
        assert.equal(app.errors[inactiveField], undefined);
        assert.equal(app.serverErrors[inactiveField], undefined);
        assert.equal(app.next(), true);
    }
});

test('error navigation focuses the enabled manual input after hydration replaces the original select', async () => {
    let finishStates;
    globalThis.fetch = (url) => url.startsWith('/states')
        ? new Promise((resolve) => { finishStates = resolve; })
        : Promise.resolve({ ok: true, json: async () => ({ data: [], meta: { manual: true } }) });
    const select = control({ type: 'select', value: 'England', id: 'state-select' });
    const manual = control({ type: 'text', value: 'England', disabled: true, id: 'state-manual' });
    const state = field('state', { location: true, controls: [select, manual] });
    const { app } = fixture([[], [state], [confirmation()]], {
        mode: 'native', errors: { state: ['Check your state.'] },
        location: { countryCode: 'GB', state: 'England', statesUrl: '/states', citiesUrl: '/cities' },
    });
    const item = { ...app.summary[0] };
    assert.equal(item.target, 'state-select');
    finishStates({ ok: true, json: async () => ({ data: [], meta: { manual: true } }) });
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(app.stateManual, true);

    // Reflect x-bind:disabled after the backend authorizes manual state entry.
    select.disabled = true;
    manual.disabled = false;
    app.moveTo(2, false);
    app.goTo(item);
    assert.equal(app.step, 1);
    assert.equal(manual.focused, 1);
    assert.equal(select.focused, 0);
});
