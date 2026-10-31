import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { getCountries } from 'libphonenumber-js/max';
import { createInternationalPhone } from '../../resources/js/international-phone.js';

const countries = [
    ['US', 'United States', '+1'], ['CA', 'Canada', '+1'], ['GB', 'United Kingdom', '+44'],
    ['NG', 'Nigeria', '+234'], ['IN', 'India', '+91'], ['IT', 'Italy', '+39'],
    ['FR', 'France', '+33'], ['AU', 'Australia', '+61'], ['CI', 'Côte d’Ivoire', '+225'],
].map(([code, name, dial_code]) => ({ code, name, dial_code, flag_url: `/flags/${code.toLowerCase()}.svg` }));

test('all supported client phone regions have local flag assets', () => {
    const missing = getCountries().filter((code) => !existsSync(new URL(`../../public/asset/marthire/flags/${code.toLowerCase()}.svg`, import.meta.url)));
    assert.deepEqual(missing, []);
});

function phone(options = {}) {
    const component = createInternationalPhone({ countries, ...options });
    component.$refs = {
        phoneInput: { message: '', focused: false, setCustomValidity(message) { this.message = message; }, focus() { this.focused = true; } },
        phoneSearch: { focus() {} }, phoneOptions: { querySelector() { return null; } },
        phoneCountryButton: { focus() {} },
    };
    component.$nextTick = (callback) => callback();
    component.init();
    return component;
}

for (const [country, value, canonical] of [
    ['US', '2025550123', '+12025550123'], ['CA', '4165550123', '+14165550123'],
    ['GB', '02079460018', '+442079460018'], ['NG', '08031234567', '+2348031234567'],
    ['IN', '09876543210', '+919876543210'], ['IT', '0236618300', '+390236618300'],
    ['FR', '0612345678', '+33612345678'], ['AU', '0412345678', '+61412345678'],
]) {
    test(`formats and validates a ${country} national number`, () => {
        const component = phone({ country, value });
        assert.equal(component.canonicalPhone, canonical);
        assert.equal(component.phoneError, '');
        assert.equal(component.$refs.phoneInput.message, '');
        assert.equal(component.phoneCountry, country);
    });
}

test('search supports country names, ISO codes, dial codes, and accents', () => {
    const component = phone();
    for (const [query, expected] of [['united kingdom', 'GB'], ['GB', 'GB'], ['+234', 'NG'], ['cote', 'CI']]) {
        component.countryQuery = query;
        assert.deepEqual(component.filteredPhoneCountries.map((item) => item.code), [expected]);
    }
    component.countryQuery = 'no such country';
    assert.equal(component.filteredPhoneCountries.length, 0);
});

test('pasting an international number selects its phone region without changing address country', () => {
    const component = phone({ country: 'NG' });
    component.countryCode = 'NG';
    const input = { value: '+44 20 7946 0018', selectionStart: 16, setSelectionRange() {} };
    component.phoneNumberInput({ target: input, inputType: 'insertFromPaste' });
    assert.equal(component.phoneCountry, 'GB');
    assert.equal(component.canonicalPhone, '+442079460018');
    assert.equal(component.countryCode, 'NG');
    assert.equal(input.value, '020 7946 0018');
});

test('shared calling codes still reject a number belonging to a different selected country', () => {
    const component = phone({ country: 'US', value: '2025550123' });
    component.choosePhoneCountry('CA');
    assert.equal(component.phoneCountry, 'CA');
    assert.equal(component.canonicalPhone, '');
    assert.match(component.phoneError, /Canada/);
});

test('global service prefixes are preserved instead of becoming a different national number', () => {
    const component = phone({ countries: [...countries, { code: 'SG', name: 'Singapore', dial_code: '+65' }], country: 'SG', value: '+80061234567' });
    assert.equal(component.phoneCountry, 'SG');
    assert.match(component.nationalValue, /^\+800/);
    assert.equal(component.canonicalPhone, '');
    assert.notEqual(component.phoneError, '');
});

test('invalid numbers and extensions produce an inline message and custom validity', () => {
    for (const value of ['123', '0000000000', 'Call 2025550123', '+12025550123 ext 2', '+80012345678']) {
        const component = phone({ country: 'US', value });
        component.phoneNumberBlur();
        assert.notEqual(component.phoneMessage(), '');
        assert.notEqual(component.$refs.phoneInput.message, '');
        assert.equal(component.canonicalPhone, '');
    }
});

test('empty optional and required numbers retain their respective validation rules', () => {
    assert.equal(phone({ required: false }).validatePhone(), true);
    const component = phone({ required: true });
    assert.equal(component.validatePhone(), false);
    component.phoneNumberBlur();
    assert.match(component.phoneMessage(), /Enter your phone number/);
});

test('keyboard selection wraps the filtered list, closes it, and focuses the phone input', () => {
    const component = phone();
    component.openPhoneCountries();
    component.countryQuery = '+1';
    component.searchPhoneCountries();
    component.movePhoneCountry(-1);
    assert.equal(component.activeCountryIndex, 1);
    component.chooseActivePhoneCountry();
    assert.equal(component.phoneCountry, 'CA');
    assert.equal(component.countryMenuOpen, false);
    assert.equal(component.$refs.phoneInput.focused, true);
});

test('explicit restored country mismatches stay visible instead of silently changing selection', () => {
    const component = phone({ country: 'US', value: '+442079460018', explicitCountry: true });
    assert.equal(component.phoneCountry, 'US');
    assert.notEqual(component.phoneError, '');
    assert.equal(component.canonicalPhone, '');
});
