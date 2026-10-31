import test, { afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { createLocationSelector, registerLocationSelector } from '../../resources/js/location-selector.js';

const originalFetch = globalThis.fetch;
afterEach(() => { globalThis.fetch = originalFetch; });

const option = (value) => ({ value, label: value });
const response = (data = [], manual = false) => ({ ok: true, json: async () => ({ data, meta: { manual } }) });
const flush = () => new Promise((resolve) => setImmediate(resolve));
const selector = (options = {}) => createLocationSelector({
    countries: [{ value: 'NG', label: 'Nigeria' }, { value: 'GB', label: 'United Kingdom' }],
    statesUrl: '/locations/states', citiesUrl: '/locations/cities', ...options,
});

// Deliberately ignore cancellation in the mock: sequence checks must also reject
// responses from a transport which has already completed or cannot be aborted.
function queuedFetch() {
    const requests = [];
    globalThis.fetch = (url, options) => new Promise((resolve, reject) => {
        requests.push({ url, options, resolve, reject });
    });
    return requests;
}

test('hydration keeps server values and preloaded options while refreshing both levels', async () => {
    const requests = queuedFetch();
    const form = selector({
        countryCode: 'GB', state: 'England', city: 'London',
        preloadedStates: [option('England')], preloadedCities: [option('London')],
    });
    const initialized = form.initLocations();

    assert.equal(form.state, 'England');
    assert.equal(form.city, 'London');
    assert.deepEqual(form.states, [option('England')]);
    assert.deepEqual(form.cities, [option('London')]);
    assert.equal(form.loadingStates, true);
    assert.equal(form.loadingCities, false);
    assert.equal(form.countryName(), 'United Kingdom');
    assert.equal(requests[0].url, '/locations/states?country=GB');
    assert.equal(requests[0].options.headers.Accept, 'application/json');
    assert.equal(requests[0].options.credentials, 'same-origin');

    requests[0].resolve(response([option('England'), option('Wales')]));
    await flush();
    assert.equal(form.loadingStates, false);
    assert.equal(form.loadingCities, true);
    assert.equal(requests[1].url, '/locations/cities?country=GB&state=England');
    requests[1].resolve(response([option('London'), option('Bristol')]));

    assert.equal(await initialized, true);
    assert.equal(form.state, 'England');
    assert.equal(form.city, 'London');
    assert.equal(form.loadingCities, false);
    assert.equal(form.locationError, '');
});

test('country changes clear dependent fields and ignore stale responses even when the country returns to its old value', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'NG', state: 'Lagos', lga: 'Ikeja' });
    const first = form.loadStates();
    assert.equal(form.state, '');
    assert.equal(form.city, '');
    assert.equal(form.lga, '');
    form.countryCode = 'GB';
    const second = form.loadStates();
    form.countryCode = 'NG';
    const latest = form.loadStates();

    assert.equal(requests[0].options.signal.aborted, true);
    assert.equal(requests[1].options.signal.aborted, true);
    requests[2].resolve(response([option('Abuja')]));
    await latest;
    requests[0].resolve(response([option('Lagos')]));
    requests[1].resolve(response([option('England')]));
    await Promise.all([first, second]);

    assert.deepEqual(form.states, [option('Abuja')]);
    assert.equal(form.loadingStates, false);
    assert.equal(form.locationError, '');
});

test('changing country aborts pending city work and stale errors cannot replace the new state request', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'NG', state: 'Lagos', lga: 'Ikeja' });
    const cities = form.loadCities(true);
    form.countryCode = 'GB';
    const states = form.loadStates();
    assert.equal(requests[0].options.signal.aborted, true);
    assert.equal(form.loadingCities, false);
    requests[0].reject(new Error('Late failure from the old country'));
    await cities;
    assert.equal(form.locationError, '');
    assert.equal(form.loadingStates, true);
    requests[1].resolve(response([option('England')]));
    await states;
});

test('state changes clear city and LGA and reject late city responses and errors', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'GB', state: 'England', city: 'London', lga: 'old' });
    const first = form.loadCities();
    assert.equal(form.city, '');
    assert.equal(form.lga, '');
    form.state = 'Wales';
    const second = form.loadCities();
    assert.equal(requests[0].options.signal.aborted, true);
    requests[0].reject(new Error('Old state unavailable'));
    await first;
    assert.equal(form.loadingCities, true);
    assert.equal(form.locationError, '');
    requests[1].resolve(response([option('Cardiff')]));
    await second;
    assert.deepEqual(form.cities, [option('Cardiff')]);
    assert.equal(form.loadingCities, false);
});

test('states and cities have independent loading indicators during non-destructive retries', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'GB', state: 'England', city: 'London' });
    const states = form.loadStates(true);
    const cities = form.loadCities(true);
    assert.equal(form.loadingStates, true);
    assert.equal(form.loadingCities, true);
    requests[0].resolve(response([option('England')]));
    await states;
    assert.equal(form.loadingStates, false);
    assert.equal(form.loadingCities, true);
    requests[1].resolve(response([option('London')]));
    await cities;
    assert.equal(form.city, 'London');
    assert.equal(form.loadingCities, false);
});

test('failed lookups keep prior valid values and options, expose retry errors, and do not enable manual entry', async () => {
    const requests = queuedFetch();
    const form = selector({
        countryCode: 'GB', state: 'England', city: 'London',
        states: [option('England')], cities: [option('London')],
    });
    const states = form.loadStates(true);
    requests[0].reject(new Error('Offline'));
    assert.equal(await states, false);
    assert.equal(form.state, 'England');
    assert.equal(form.city, 'London');
    assert.deepEqual(form.states, [option('England')]);
    assert.deepEqual(form.cities, [option('London')]);
    assert.equal(form.stateManual, false);
    assert.ok(form.statesError);

    const cities = form.loadCities(true);
    requests[1].resolve({ ok: false, status: 503, json: async () => { throw new Error('Should not parse failed response'); } });
    assert.equal(await cities, false);
    assert.equal(form.city, 'London');
    assert.deepEqual(form.cities, [option('London')]);
    assert.equal(form.cityManual, false);
    assert.ok(form.citiesError);
    assert.ok(form.locationError.includes(form.statesError));
    assert.ok(form.locationError.includes(form.citiesError));

    const retryStates = form.loadStates(true);
    requests[2].resolve(response([option('England')]));
    await retryStates;
    assert.equal(form.statesError, '');
    assert.ok(form.citiesError);
    assert.equal(form.city, 'London');
    const retryCities = form.loadCities(true);
    requests[3].resolve(response([option('London')]));
    await retryCities;
    assert.equal(form.locationError, '');
});

test('manual entry is enabled only by an explicit backend manual boolean, not empty results or invalid JSON', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'GB' });
    const empty = form.loadStates();
    requests[0].resolve({ ok: true, json: async () => ({ data: [] }) });
    await empty;
    assert.equal(form.stateManual, false);
    assert.equal(form.locationError, '');

    const invalid = form.loadStates(true);
    requests[1].resolve({ ok: true, json: async () => ({ data: {}, meta: { manual: true } }) });
    await invalid;
    assert.equal(form.stateManual, false);
    assert.ok(form.statesError);

    const manual = form.loadStates(true);
    requests[2].resolve(response([], true));
    await manual;
    assert.equal(form.stateManual, true);
    assert.equal(form.statesError, '');
    form.state = 'A manually entered region';
    const manualCities = form.loadCities();
    requests[3].resolve(response([], true));
    await manualCities;
    assert.equal(form.cityManual, true);

    const regularCities = form.loadCities(true);
    requests[4].resolve({ ok: true, json: async () => ({ data: [], meta: { manual: 'true' } }) });
    await regularCities;
    assert.equal(form.cityManual, false);
});

test('hydration can still recover cities if only the state lookup temporarily fails', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'GB', state: 'England', city: 'London', states: [option('England')] });
    const initialized = form.initLocations();
    requests[0].reject(new Error('State lookup offline'));
    await flush();
    assert.equal(requests.length, 2);
    requests[1].resolve(response([option('London')]));
    assert.equal(await initialized, false);
    assert.equal(form.city, 'London');
    assert.deepEqual(form.cities, [option('London')]);
    assert.ok(form.statesError);
    assert.equal(form.citiesError, '');
});

test('init does not start a redundant old-state city lookup after the user changes state', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'GB', state: 'England', city: 'London' });
    const initialized = form.initLocations();
    form.state = 'Wales';
    const cities = form.loadCities();
    requests[0].resolve(response([option('England'), option('Wales')]));
    await initialized;
    assert.equal(requests.length, 2);
    assert.equal(requests[1].options.signal.aborted, false);
    requests[1].resolve(response([option('Cardiff')]));
    await cities;
});

test('Nigeria uses the native LGA field while the profile retains its single City/LGA field', () => {
    const native = selector({ countryCode: ' ng ', state: 'Lagos', city: 'Ikeja' });
    assert.equal(native.countryCode, 'NG');
    assert.equal(native.city, '');
    assert.equal(native.lga, 'Ikeja');
    const profile = selector({ countryCode: 'NG', state: 'Lagos', lga: 'Ikeja', nigeriaUsesLga: false });
    assert.equal(profile.city, 'Ikeja');
    assert.equal(profile.lga, '');
});

test('destroy cancels both requests and prevents their eventual completions from touching component state', async () => {
    const requests = queuedFetch();
    const form = selector({ countryCode: 'GB', state: 'England', city: 'London' });
    const states = form.loadStates(true);
    const cities = form.loadCities(true);
    form.destroyLocations();
    assert.equal(requests[0].options.signal.aborted, true);
    assert.equal(requests[1].options.signal.aborted, true);
    assert.equal(form.loadingStates, false);
    assert.equal(form.loadingCities, false);
    requests[0].resolve(response([option('Stale')]));
    requests[1].reject(new Error('Late city error'));
    await Promise.all([states, cities]);
    assert.deepEqual(form.states, []);
    assert.deepEqual(form.cities, []);
    assert.equal(form.city, 'London');
    assert.equal(form.locationError, '');
    assert.equal(await form.loadStates(), false);
});

test('Alpine registration delegates lifecycle to the shared selector', async () => {
    let factory;
    registerLocationSelector({ data(name, create) { assert.equal(name, 'locationSelector'); factory = create; } });
    globalThis.fetch = async () => response([option('England')]);
    const form = factory({ countryCode: 'GB', statesUrl: '/states', citiesUrl: '/cities' });
    assert.equal(await form.init(), true);
    assert.deepEqual(form.states, [option('England')]);
    form.destroy();
    assert.equal(await form.loadCities(), false);
});
