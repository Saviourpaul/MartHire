const text = (value) => typeof value === 'string' ? value : '';

const optionsList = (options) => {
    const seen = new Set();
    return (Array.isArray(options) ? options : []).flatMap((option) => {
        if (!option || typeof option.value !== 'string' || !option.value || seen.has(option.value)) return [];
        seen.add(option.value);
        return [{ value: option.value, label: typeof option.label === 'string' ? option.label : option.value }];
    });
};

const lookupUrl = (url, parameters) => {
    if (!url) throw new Error('A location lookup URL is required.');
    const base = url.split('#')[0];
    return base + (base.includes('?') ? '&' : '?') + new URLSearchParams(parameters);
};

/** Shared country → state → city/LGA lookups for profile and application forms. */
export function createLocationSelector(options = {}) {
    let statesController = null;
    let citiesController = null;
    let statesSequence = 0;
    let citiesSequence = 0;
    let destroyed = false;
    let activeCountry = text(options.countryCode).trim().toUpperCase();
    let activeState = text(options.state);
    const nigeriaUsesLga = options.nigeriaUsesLga !== false;
    const initialCity = text(options.city);
    const initialLga = text(options.lga);

    const abortStates = () => {
        statesSequence += 1;
        statesController?.abort();
        statesController = null;
    };
    const abortCities = () => {
        citiesSequence += 1;
        citiesController?.abort();
        citiesController = null;
    };
    const updateError = (selector) => {
        selector.locationError = [selector.statesError, selector.citiesError].filter(Boolean).join(' ');
    };

    return {
        countries: optionsList(options.countries),
        countryCode: activeCountry,
        state: activeState,
        city: activeCountry === 'NG' && nigeriaUsesLga ? '' : (initialCity || (activeCountry === 'NG' ? initialLga : '')),
        lga: activeCountry === 'NG' && nigeriaUsesLga ? (initialLga || initialCity) : '',
        states: optionsList(options.preloadedStates ?? options.states),
        cities: optionsList(options.preloadedCities ?? options.cities),
        loadingStates: false,
        loadingCities: false,
        stateManual: false,
        cityManual: false,
        locationError: '',
        statesError: '',
        citiesError: '',

        countryName() {
            return this.countries.find((country) => country.value === this.countryCode)?.label ?? '';
        },

        async initLocations() {
            if (destroyed) return false;
            const country = this.countryCode;
            const state = this.state;
            const loadedStates = await this.loadStates(true);
            if (destroyed || this.countryCode !== country || this.state !== state) return false;
            if (this.state) return (await this.loadCities(true)) && loadedStates;
            return loadedStates;
        },

        async loadStates(keepSelection = false) {
            if (destroyed) return false;
            const country = this.countryCode;
            const preserve = keepSelection && country === activeCountry;
            abortStates();
            const sequence = statesSequence;
            activeCountry = country;
            this.statesError = '';

            if (!preserve || !country) {
                abortCities();
                activeState = '';
                this.state = '';
                this.city = '';
                this.lga = '';
                this.states = [];
                this.cities = [];
                this.stateManual = false;
                this.cityManual = false;
                this.citiesError = '';
                this.loadingCities = false;
            }
            updateError(this);

            if (!country) {
                this.loadingStates = false;
                return false;
            }

            const controller = new AbortController();
            statesController = controller;
            this.loadingStates = true;
            const isCurrent = () => !destroyed && sequence === statesSequence && this.countryCode === country;

            try {
                const response = await fetch(lookupUrl(text(options.statesUrl), { country }), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('The state lookup failed.');
                const json = await response.json();
                if (!json || !Array.isArray(json.data)) throw new Error('Invalid state lookup response.');
                if (!isCurrent() || controller.signal.aborted) return false;
                this.states = optionsList(json.data);
                this.stateManual = json.meta?.manual === true;
                return true;
            } catch (error) {
                if (isCurrent() && !controller.signal.aborted && error?.name !== 'AbortError') {
                    this.statesError = text(options.statesErrorMessage) || 'We could not load the states. Please try again.';
                    updateError(this);
                }
                return false;
            } finally {
                if (isCurrent()) {
                    this.loadingStates = false;
                    statesController = null;
                }
            }
        },

        async loadCities(keepSelection = false) {
            if (destroyed) return false;
            const country = this.countryCode;
            const state = this.state;
            const preserve = keepSelection && country === activeCountry && state === activeState;
            abortCities();
            const sequence = citiesSequence;
            activeState = state;
            this.citiesError = '';
            if (!preserve || !country || !state) {
                this.city = '';
                this.lga = '';
                this.cities = [];
                this.cityManual = false;
            }
            updateError(this);

            if (!country || !state) {
                this.loadingCities = false;
                return false;
            }

            const controller = new AbortController();
            citiesController = controller;
            this.loadingCities = true;
            const isCurrent = () => !destroyed && sequence === citiesSequence
                && this.countryCode === country && this.state === state;

            try {
                const response = await fetch(lookupUrl(text(options.citiesUrl), { country, state }), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('The city lookup failed.');
                const json = await response.json();
                if (!json || !Array.isArray(json.data)) throw new Error('Invalid city lookup response.');
                if (!isCurrent() || controller.signal.aborted) return false;
                this.cities = optionsList(json.data);
                this.cityManual = json.meta?.manual === true;
                return true;
            } catch (error) {
                if (isCurrent() && !controller.signal.aborted && error?.name !== 'AbortError') {
                    this.citiesError = text(options.citiesErrorMessage) || 'We could not load the cities. Please try again.';
                    updateError(this);
                }
                return false;
            } finally {
                if (isCurrent()) {
                    this.loadingCities = false;
                    citiesController = null;
                }
            }
        },

        destroyLocations() {
            destroyed = true;
            abortStates();
            abortCities();
            this.loadingStates = false;
            this.loadingCities = false;
        },
    };
}

export function registerLocationSelector(Alpine) {
    Alpine.data('locationSelector', (options) => ({
        ...createLocationSelector(options),
        init() { return this.initLocations(); },
        destroy() { this.destroyLocations(); },
    }));
}
