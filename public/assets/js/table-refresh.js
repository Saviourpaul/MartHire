(function () {
    const regionSelector = '[data-table-refresh-region]';
    const refreshButtonSelector = '[data-table-refresh-button]';
    const resetLinkSelector = '[data-table-reset-link]';
    const tableLinkSelector = '[data-table-link]';
    const actionFormSelector = '[data-table-action-form]';
    const handledParams = [
        'page',
        'per_page',
        'sort',
        'direction',
        'search',
        'role',
        'status',
        'category',
        'submitted_from',
        'submitted_to',
        'job_id',
    ];

    function regionFor(element) {
        const targetId = element.dataset.tableTarget;

        if (targetId) {
            return document.querySelector(`${regionSelector}[data-table-refresh-id="${targetId}"]`);
        }

        return element.closest(regionSelector);
    }

    function setMessage(region, message, tone) {
        const target = region.querySelector('[data-table-refresh-message]');

        if (!target) {
            return;
        }

        target.textContent = message || '';
        target.classList.toggle('hidden', !message);
        target.classList.toggle('border-error-500/30', tone === 'error');
        target.classList.toggle('bg-error-50', tone === 'error');
        target.classList.toggle('text-error-700', tone === 'error');
        target.classList.toggle('dark:bg-error-500/10', tone === 'error');
        target.classList.toggle('dark:text-error-400', tone === 'error');
        target.classList.toggle('border-success-500/30', tone === 'success');
        target.classList.toggle('bg-success-50', tone === 'success');
        target.classList.toggle('text-success-700', tone === 'success');
        target.classList.toggle('dark:bg-success-500/10', tone === 'success');
        target.classList.toggle('dark:text-success-400', tone === 'success');
    }

    function setRegionLoading(region, loading, trigger) {
        region.dataset.refreshing = loading ? 'true' : 'false';
        region.setAttribute('aria-busy', loading ? 'true' : 'false');
        region.classList.toggle('opacity-60', loading);

        region.querySelectorAll(refreshButtonSelector).forEach((button) => {
            button.disabled = loading;
            button.setAttribute('aria-busy', loading ? 'true' : 'false');
            button.querySelector('[data-table-refresh-icon]')?.classList.toggle('animate-spin', loading);
            button.querySelector('[data-table-refresh-label]')?.classList.toggle('opacity-70', loading);
        });

        if (trigger && !trigger.matches(refreshButtonSelector)) {
            trigger.setAttribute('aria-busy', loading ? 'true' : 'false');
        }
    }

    function buildFormUrl(form) {
        const url = new URL(form.action || window.location.href, window.location.origin);
        const formData = new FormData(form);

        url.search = '';
        formData.forEach((value, key) => {
            if (value !== null && value !== '') {
                url.searchParams.append(key, value);
            }
        });

        url.searchParams.delete('page');

        return url;
    }

    function shouldHandleLink(link) {
        if (!link || link.target || link.hasAttribute('download') || link.dataset.tableSkip === 'true') {
            return false;
        }

        if (link.matches(`${tableLinkSelector}, ${resetLinkSelector}`)) {
            return true;
        }

        if (link.closest('nav[role="navigation"], .pagination')) {
            return true;
        }

        try {
            const url = new URL(link.href, window.location.origin);

            return url.origin === window.location.origin
                && url.pathname === window.location.pathname
                && handledParams.some((param) => url.searchParams.has(param));
        } catch (error) {
            return false;
        }
    }

    function swapRegion(region, html) {
        const documentFragment = new DOMParser().parseFromString(html, 'text/html');
        const replacement = documentFragment.querySelector(
            `${regionSelector}[data-table-refresh-id="${region.dataset.tableRefreshId}"]`
        );

        if (!replacement) {
            throw new Error('Refresh target was not found in the response.');
        }

        replacement.querySelector('[data-table-refresh-message]')?.classList.add('hidden');
        region.replaceWith(replacement);

        return replacement;
    }

    async function loadTable(region, url, options) {
        const settings = {
            pushState: true,
            trigger: null,
            ...options,
        };

        if (!region || region.dataset.refreshing === 'true') {
            return;
        }

        setRegionLoading(region, true, settings.trigger);
        setMessage(region, '', 'error');

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Table request failed with status ${response.status}`);
            }

            const replacement = swapRegion(region, await response.text());

            if (settings.pushState) {
                window.history.pushState({}, '', url.toString());
            }

            return replacement;
        } catch (error) {
            setMessage(region, 'Unable to load this table. Please try again.', 'error');
            setRegionLoading(region, false, settings.trigger);
        }
    }

    function setFormError(form, message) {
        const target = form.querySelector('[data-form-error]');

        if (!target) {
            return;
        }

        target.textContent = message || '';
        target.classList.toggle('hidden', !message);
    }

    function setFormLoading(form, loading) {
        form.dataset.submitting = loading ? 'true' : 'false';
        form.querySelectorAll('button, input, select, textarea').forEach((field) => {
            field.disabled = loading;
        });
    }

    function firstErrorMessage(payload) {
        if (payload?.message) {
            return payload.message;
        }

        if (!payload?.errors) {
            return 'Unable to complete this action. Please try again.';
        }

        const firstErrors = Object.values(payload.errors)[0];

        return Array.isArray(firstErrors) && firstErrors.length
            ? firstErrors[0]
            : 'Unable to complete this action. Please try again.';
    }

    async function submitActionForm(form) {
        const region = regionFor(form);

        if (!region || form.dataset.submitting === 'true' || region.dataset.refreshing === 'true') {
            return;
        }

        const formData = new FormData(form);

        setFormLoading(form, true);
        setFormError(form, '');
        setMessage(region, '', 'error');

        try {
            const response = await fetch(form.action, {
                method: (form.method || 'post').toUpperCase(),
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.ok === false) {
                throw payload;
            }

            const replacement = await loadTable(region, new URL(window.location.href), { pushState: false });

            if (replacement) {
                setMessage(replacement, payload.message || 'Action completed successfully.', 'success');
            }
        } catch (error) {
            const message = firstErrorMessage(error);
            setFormError(form, message);
            setMessage(region, message, 'error');
        } finally {
            setFormLoading(form, false);
        }
    }

    document.addEventListener('click', function (event) {
        const refreshButton = event.target.closest(refreshButtonSelector);

        if (refreshButton) {
            event.preventDefault();
            const region = regionFor(refreshButton);
            const url = new URL(region?.dataset.tableRefreshUrl || window.location.href, window.location.origin);
            loadTable(region, url, { pushState: false, trigger: refreshButton });

            return;
        }

        const link = event.target.closest('a[href]');

        if (!shouldHandleLink(link)) {
            return;
        }

        const region = regionFor(link);

        if (!region) {
            return;
        }

        event.preventDefault();
        loadTable(region, new URL(link.href, window.location.origin), { trigger: link });
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form');

        if (!form) {
            return;
        }

        if (form.matches(actionFormSelector)) {
            event.preventDefault();
            submitActionForm(form);

            return;
        }

        if ((form.method || 'get').toLowerCase() !== 'get') {
            return;
        }

        const region = regionFor(form);

        if (!region) {
            return;
        }

        event.preventDefault();
        loadTable(region, buildFormUrl(form), { trigger: form });
    });

    document.addEventListener('change', function (event) {
        const field = event.target.closest('[data-table-auto-submit]');
        const form = field?.form;

        if (!field || !form || (form.method || 'get').toLowerCase() !== 'get') {
            return;
        }

        const region = regionFor(form) || regionFor(field);

        if (!region) {
            return;
        }

        loadTable(region, buildFormUrl(form), { trigger: field });
    });

    window.addEventListener('popstate', function () {
        document.querySelectorAll(regionSelector).forEach((region) => {
            loadTable(region, new URL(window.location.href), { pushState: false });
        });
    });
})();
