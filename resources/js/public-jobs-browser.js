const browser = document.querySelector('[data-jobs-browser]');

if (browser) {
    const form = browser.querySelector('[data-jobs-filter]');
    const results = browser.querySelector('[data-jobs-results]');
    const skeleton = browser.querySelector('[data-jobs-skeleton]');
    const errorMessage = browser.querySelector('[data-jobs-error]');
    const status = browser.querySelector('[data-jobs-status]');
    let requestController;
    let lastRequestedUrl = window.location.href;

    const syncFilters = (url) => {
        for (const field of form.elements) {
            if (field.name && url.searchParams.has(field.name)) {
                field.value = url.searchParams.get(field.name);
            } else if (field.name) {
                field.value = '';
            }
        }
    };

    const loadResults = async (target, { updateHistory = true, scroll = true } = {}) => {
        const url = new URL(target, window.location.href);
        lastRequestedUrl = url.href;
        requestController?.abort();
        const controller = new AbortController();
        requestController = controller;

        browser.setAttribute('aria-busy', 'true');
        results.setAttribute('aria-busy', 'true');
        results.hidden = true;
        skeleton.classList.remove('hidden');
        skeleton.setAttribute('aria-hidden', 'false');
        errorMessage.classList.add('hidden');

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            });
            const payload = await response.json();

            if (!response.ok || typeof payload.html !== 'string') {
                throw new Error('The job listings request failed.');
            }

            results.innerHTML = payload.html;
            results.hidden = false;
            syncFilters(url);

            if (updateHistory && url.href !== window.location.href) {
                window.history.pushState({}, '', url);
            }

            if (status) {
                status.textContent = `${payload.total} ${payload.total === 1 ? 'job' : 'jobs'} found.`;
            }

            if (scroll) {
                results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                results.hidden = false;
                errorMessage.classList.remove('hidden');
            }
        } finally {
            if (!controller.signal.aborted) {
                browser.setAttribute('aria-busy', 'false');
                results.setAttribute('aria-busy', 'false');
                skeleton.classList.add('hidden');
                skeleton.setAttribute('aria-hidden', 'true');
            }
        }
    };

    form?.addEventListener('submit', (event) => {
        event.preventDefault();

        const url = new URL(form.action, window.location.href);
        const existing = new URLSearchParams(window.location.search);
        const formNames = new Set(Array.from(form.elements, (field) => field.name).filter(Boolean));

        for (const [name, value] of existing) {
            if (name !== 'page' && !formNames.has(name)) {
                url.searchParams.set(name, value);
            }
        }

        for (const [name, value] of new FormData(form)) {
            if (String(value).trim()) {
                url.searchParams.set(name, String(value).trim());
            } else {
                url.searchParams.delete(name);
            }
        }

        url.searchParams.delete('page');
        loadResults(url);
    });

    browser.addEventListener('click', (event) => {
        const retry = event.target.closest('[data-jobs-retry]');

        if (retry) {
            loadResults(lastRequestedUrl, { updateHistory: false, scroll: false });
            return;
        }

        const reset = event.target.closest('[data-jobs-reset]');

        if (reset) {
            event.preventDefault();
            form.reset();
            loadResults(reset.href);
            return;
        }

        const pageLink = event.target.closest('[data-jobs-pagination] a[href]');

        if (pageLink && pageLink.origin === window.location.origin) {
            event.preventDefault();
            loadResults(pageLink.href);
        }
    });

    window.addEventListener('popstate', () => {
        loadResults(window.location.href, { updateHistory: false, scroll: false });
    });
}
