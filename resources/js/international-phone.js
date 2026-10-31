import { AsYouType, getCountries, parsePhoneNumberFromString } from 'libphonenumber-js/max';

const supported = new Set(getCountries());
const fold = (value) => value.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase();
const safePhone = /^[+\p{Nd}\s().-]*$/u;
const digits = (value) => value.replace(/[^\p{Nd}]/gu, '');

export function createInternationalPhone({ countries = [], country = 'NG', value = '', required = false, name = 'phone', explicitCountry = false, wizard = false, serverError = '' } = {}) {
    const choices = countries.filter((item) => supported.has(item.code));
    const initial = choices.some((item) => item.code === country) ? country : (choices.find((item) => item.code === 'NG')?.code || choices[0]?.code);

    return {
        phoneCountries: choices,
        phoneCountry: initial,
        nationalValue: typeof value === 'string' ? value : '',
        canonicalPhone: '',
        countryMenuOpen: false,
        phoneMenuAbove: false,
        countryQuery: '',
        activeCountryIndex: 0,
        phoneError: '',
        phoneTouched: false,
        phoneEnhanced: false,
        serverPhoneError: serverError,
        get selectedPhoneCountry() { return this.phoneCountries.find((item) => item.code === this.phoneCountry); },
        get filteredPhoneCountries() {
            const query = fold(this.countryQuery.trim());
            return this.phoneCountries.filter((item) => !query || fold(`${item.name} ${item.code} ${item.dial_code}`).includes(query));
        },
        init() {
            this.formatPhone(this.nationalValue, !explicitCountry);
            this.phoneEnhanced = true;
            this.$nextTick?.(() => this.validatePhone());
        },
        parsedPhone(raw = this.nationalValue) {
            if (!safePhone.test(raw) || raw.length > 80) return null;
            return parsePhoneNumberFromString(raw, { defaultCountry: this.phoneCountry, extract: false }) || null;
        },
        formatPhone(raw, inferCountry = true) {
            const parsed = this.parsedPhone(raw);
            if (inferCountry && raw.trim().startsWith('+') && parsed?.country && this.phoneCountries.some((item) => item.code === parsed.country)) {
                this.phoneCountry = parsed.country;
            }
            // Preserve a mismatching international prefix so an invalid choice is visible.
            const matches = parsed?.country === this.phoneCountry;
            if (parsed?.isValid() && matches && !parsed.ext) this.nationalValue = parsed.formatNational();
            else this.nationalValue = safePhone.test(raw) ? new AsYouType(this.phoneCountry).input(raw) : raw;
            this.validatePhone();
        },
        validatePhone() {
            const parsed = this.parsedPhone();
            let message = '';
            if (!this.nationalValue.trim()) message = required ? 'Enter your phone number.' : '';
            else if (!parsed?.isValid() || parsed.ext || parsed.country !== this.phoneCountry) {
                message = `Enter a valid phone number for ${this.selectedPhoneCountry?.name || 'the selected country'} (${this.selectedPhoneCountry?.dial_code || ''}).`;
            }
            this.phoneError = message;
            this.canonicalPhone = !message && parsed ? parsed.number : '';
            this.$refs?.phoneInput?.setCustomValidity(message);
            return !message;
        },
        phoneNumberInput(event) {
            const input = event.target;
            let raw = input.value;
            const originalCaret = input.selectionStart ?? raw.length;
            let before = digits(raw.slice(0, originalCaret)).length;
            const atEnd = originalCaret === raw.length;
            // Backspace over a formatting separator should remove the preceding digit.
            if (event.inputType === 'deleteContentBackward' && raw.length < this.nationalValue.length && digits(raw) === digits(this.nationalValue) && before > 0) {
                const plain = digits(raw);
                raw = plain.slice(0, before - 1) + plain.slice(before);
                before -= 1;
            }
            this.serverPhoneError = '';
            if (wizard) this.clear?.(name);
            const wasInternational = raw.trim().startsWith('+');
            this.formatPhone(raw);
            input.value = this.nationalValue;
            this.$nextTick?.(() => {
                if (atEnd || wasInternational) { input.setSelectionRange?.(this.nationalValue.length, this.nationalValue.length); return; }
                let seen = 0;
                let position = 0;
                while (position < this.nationalValue.length && seen < before) {
                    if (/\p{Nd}/u.test(this.nationalValue[position])) seen += 1;
                    position += 1;
                }
                input.setSelectionRange?.(position, position);
            });
        },
        phoneNumberBlur() { this.phoneTouched = true; this.validatePhone(); },
        phoneMessage() {
            return (wizard ? this.error?.(name) : this.serverPhoneError) || (this.phoneTouched ? this.phoneError : '');
        },
        openPhoneCountries() {
            this.positionPhoneMenu();
            this.countryMenuOpen = true;
            this.countryQuery = '';
            this.activeCountryIndex = Math.max(0, this.phoneCountries.findIndex((item) => item.code === this.phoneCountry));
            this.$nextTick?.(() => { this.$refs.phoneSearch?.focus(); this.scrollPhoneCountry(); });
        },
        positionPhoneMenu() {
            const bounds = this.$el?.getBoundingClientRect?.();
            if (bounds && typeof window !== 'undefined') this.phoneMenuAbove = window.innerHeight - bounds.bottom < 300 && bounds.top > window.innerHeight - bounds.bottom;
        },
        closePhoneCountries(restoreFocus = false) {
            this.countryMenuOpen = false;
            if (restoreFocus) this.$nextTick?.(() => this.$refs.phoneCountryButton?.focus());
        },
        searchPhoneCountries() { this.activeCountryIndex = 0; this.$nextTick?.(() => this.scrollPhoneCountry()); },
        movePhoneCountry(direction) {
            const total = this.filteredPhoneCountries.length;
            if (!total) return;
            this.activeCountryIndex = (this.activeCountryIndex + direction + total) % total;
            this.scrollPhoneCountry();
        },
        scrollPhoneCountry() { this.$refs.phoneOptions?.querySelector(`[data-option-index="${this.activeCountryIndex}"]`)?.scrollIntoView({ block: 'nearest' }); },
        choosePhoneCountry(code) {
            if (!this.phoneCountries.some((item) => item.code === code)) return;
            const parsed = this.parsedPhone();
            const number = parsed?.isValid() && parsed.country === this.phoneCountry ? parsed.nationalNumber : this.nationalValue;
            this.phoneCountry = code;
            this.serverPhoneError = '';
            if (wizard) this.clear?.(name);
            this.formatPhone(number, false);
            this.closePhoneCountries();
            this.$nextTick?.(() => this.$refs.phoneInput?.focus());
        },
        chooseActivePhoneCountry() {
            const option = this.filteredPhoneCountries[this.activeCountryIndex];
            if (option) this.choosePhoneCountry(option.code);
        },
    };
}

export function registerInternationalPhone(Alpine) {
    Alpine.data('internationalPhone', (options) => createInternationalPhone(options));
}
