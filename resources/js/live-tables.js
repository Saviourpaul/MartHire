import { createLatestTableRequest, tableFilterUrl, tableJson, topicsOverlap } from './live-table-transport.js';

export function initializeLiveTables({ document: doc = document, window: win = window, fetch: fetcher = fetch, Alpine } = {}) {
    const regions = new Map();
    const timers = new Map();
    const dirty = new Set();
    const removers = [];
    const instance = win.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`;
    let destroyed = false;
    let channel = null;
    const listen = (target, event, handler) => { target.addEventListener(event, handler); removers.push(() => target.removeEventListener(event, handler)); };
    const notify = (message, type = 'info') => win.Marthire?.notify(message, { type });
    const split = (value) => (value || '').split(',').map((part) => part.trim()).filter(Boolean);
    const visible = (element) => element?.isConnected && element.getClientRects().length > 0 && win.getComputedStyle(element).visibility !== 'hidden';
    const safeUrl = (value) => { const url = new URL(value, win.location.href); if (url.origin !== win.location.origin) throw new Error('Table requests must stay on this site.'); return url.href; };

    function status(state, message, error = false) {
        let bar = state.root.querySelector('[data-live-status-bar]');
        if (!bar) {
            bar = doc.createElement('div');
            bar.className = 'nw-live-table-status';
            bar.setAttribute('data-live-status-bar', '');
            const text = doc.createElement('span');
            text.setAttribute('data-live-status', '');
            text.setAttribute('role', 'status');
            text.setAttribute('aria-live', 'polite');
            text.tabIndex = -1;
            const refresh = doc.createElement('button');
            refresh.type = 'button'; refresh.className = 'nw-link nw-link--small';
            refresh.setAttribute('data-live-refresh', ''); refresh.textContent = 'Refresh';
            bar.append(text, refresh); state.root.prepend(bar);
        }
        bar.classList.toggle('has-error', error);
        bar.querySelector('[data-live-status]').textContent = message;
    }

    function scan() {
        doc.querySelectorAll('[data-live-table]').forEach((root) => {
            const id = root.dataset.liveTable;
            if (!regions.has(id)) regions.set(id, { id, root, url: safeUrl(root.dataset.liveUrl || win.location.href),
                topics: split(root.dataset.liveTopics), pages: split(root.dataset.livePages || 'page'),
                transport: createLatestTableRequest(fetcher), sequence: 0, loading: false, mutating: false });
            else regions.get(id).root = root;
        });
        for (const [id, state] of regions) if (!state.root.isConnected) { state.transport.invalidate(); regions.delete(id); }
    }

    function protectedEdits(state) {
        for (const form of dirty) {
            if (!form.isConnected) { dirty.delete(form); continue; }
            const modal = form.closest('[role="dialog"]');
            if (modal && !visible(modal)) { form.reset(); clearErrors(form); dirty.delete(form); }
        }
        return [...dirty].some((form) => state.root.contains(form))
            || [...state.root.querySelectorAll('[role="dialog"]')].some(visible)
            || Boolean(doc.querySelector('dialog[open]'));
    }

    function focusSnapshot(root) {
        const active = doc.activeElement;
        if (!root.contains(active)) return null;
        return { id: active.id, start: active.selectionStart, end: active.selectionEnd };
    }

    function closeModal(form) {
        const modal = form.closest('[role="dialog"]');
        const title = modal?.getAttribute('aria-labelledby');
        if (title?.endsWith('-title')) win.dispatchEvent(new CustomEvent('close-modal', { detail: title.slice(0, -6) }));
    }

    async function apply(state, payload, historyMode) {
        const html = payload.regions?.[state.id];
        if (typeof html !== 'string') throw new Error('The server did not return the requested table.');
        const parsed = new win.DOMParser().parseFromString(html, 'text/html');
        const replacement = [...parsed.querySelectorAll('[data-live-table]')].find((element) => element.dataset.liveTable === state.id);
        if (!replacement) throw new Error('The table response could not be displayed.');
        replacement.querySelectorAll('script').forEach((script) => script.remove());
        const focus = focusSnapshot(state.root);
        // Preserve the current review tab and selected private document across an
        // authorized stage/interview mutation; the new read model stays authoritative.
        const oldReview = state.root.matches('.nw-review') ? state.root : state.root.querySelector('.nw-review');
        const newReview = replacement.matches('.nw-review') ? replacement : replacement.querySelector('.nw-review');
        if (oldReview && newReview) {
            newReview.dataset.restoreReviewTab = oldReview.dataset.currentReviewTab || '';
            newReview.dataset.restoreDocument = oldReview.dataset.currentDocument || '';
        }
        const previous = state.root;
        const replace = () => { Alpine?.destroyTree(previous); previous.replaceWith(replacement); state.root = replacement; Alpine?.initTree(replacement); };
        if (Alpine) Alpine.mutateDom(replace); else replace();
        state.url = safeUrl(payload.url || state.url);
        state.pendingFilterUrl = null;
        state.deferredRefresh = null;
        state.root.dataset.liveUrl = state.url;
        status(state, 'Up to date');
        if (typeof payload.title === 'string') doc.title = payload.title;
        if (historyMode === 'push' && state.url !== win.location.href) win.history.pushState({ liveTable: state.id }, '', state.url);
        else if (historyMode === 'replace' || (historyMode && state.url !== win.location.href)) win.history.replaceState({ liveTable: state.id }, '', state.url);
        if (![...doc.querySelectorAll('[role="dialog"]')].some(visible)) doc.body.classList.remove('overflow-y-hidden');
        await Alpine?.nextTick();
        const target = focus?.id ? doc.getElementById(focus.id) : null;
        if (target && visible(target)) {
            target.focus({ preventScroll: true });
            if (typeof focus.start === 'number') { try { target.setSelectionRange(focus.start, focus.end); } catch (_) {} }
        } else if (focus) state.root.querySelector('[data-live-status]')?.focus({ preventScroll: true });
    }

    async function refresh(state, url = state.deferredRefresh?.url || state.pendingFilterUrl || state.url, { background = false, history = state.deferredRefresh?.history || 'replace', ignoreFocus = false } = {}) {
        if (destroyed || !state.root.isConnected || state.mutating) return;
        if (background && (doc.hidden || win.navigator.onLine === false || protectedEdits(state)
            || (!ignoreFocus && state.root.contains(doc.activeElement) && doc.activeElement.matches('input,textarea,select')))) {
            status(state, 'Updates paused while you are editing.'); return;
        }
        const sequence = ++state.sequence;
        state.loading = true;
        state.root.setAttribute('aria-busy', 'true');
        status(state, 'Updating…');
        try {
            const result = await state.transport.run(safeUrl(url), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Live-Tables': state.id } });
            if (!result || destroyed) return;
            // A dialog can be opened or edited after a filter/pager request
            // starts. Never let its late response erase those new edits.
            if (protectedEdits(state)) {
                state.deferredRefresh = { url, history };
                status(state, 'Updates paused while you are editing.'); return;
            }
            await apply(state, result, history);
        } catch (error) {
            status(state, error.message || 'Could not refresh. Please try again.', true);
            if (!background) notify(error.message || 'Could not refresh the table.', 'error');
        } finally {
            if (sequence === state.sequence) { state.loading = false; state.root.setAttribute('aria-busy', 'false'); }
        }
    }

    async function allowDiscard(state) {
        if (!protectedEdits(state)) return true;
        const approved = await win.Marthire?.confirm('Refresh this table and discard the unsaved changes?', { title: 'Unsaved changes', type: 'warning', confirmLabel: 'Discard and refresh' });
        if (!approved) return false;
        for (const form of dirty) if (state.root.contains(form)) { form.reset(); clearErrors(form); dirty.delete(form); }
        state.root.querySelectorAll('[role="dialog"]').forEach((modal) => { if (visible(modal)) closeModal(modal); });
        await Alpine?.nextTick();
        return true;
    }

    function clearErrors(form) {
        form.querySelectorAll('[data-live-validation]').forEach((element) => element.remove());
        form.querySelectorAll('[data-live-invalid]').forEach((element) => { element.removeAttribute('aria-invalid'); element.removeAttribute('data-live-invalid'); });
    }

    function validation(form, errors) {
        clearErrors(form);
        const summary = doc.createElement('div'); summary.className = 'nw-notice nw-notice--error';
        summary.setAttribute('data-live-validation', ''); summary.setAttribute('role', 'alert'); summary.tabIndex = -1;
        const list = doc.createElement('ul');
        for (const [name, value] of Object.entries(errors)) {
            const message = Array.isArray(value) ? value[0] : value;
            if (typeof message !== 'string') continue;
            const item = doc.createElement('li'); item.textContent = message; list.append(item);
            const field = [...form.elements].find((element) => element.name === name && element.type !== 'hidden');
            if (field) {
                field.setAttribute('aria-invalid', 'true'); field.setAttribute('data-live-invalid', '');
                const inline = doc.createElement('p'); inline.className = 'nw-field__error'; inline.textContent = message;
                inline.setAttribute('data-live-validation', ''); field.after(inline);
            }
        }
        summary.append(list); form.prepend(summary); summary.focus({ preventScroll: false });
    }

    function publish(topics, source) {
        const detail = { topics, source, origin: instance, at: Date.now() };
        win.dispatchEvent(new CustomEvent('nw:data-changed', { detail }));
    }

    async function mutate(state, form, submitter) {
        if (state.mutating) return;
        const mutation = Symbol('table mutation');
        state.mutating = mutation;
        state.transport.invalidate();
        clearErrors(form);
        const body = new win.FormData(form);
        if (submitter?.name) body.append(submitter.name, submitter.value);
        // Lock this form while its snapshot is being saved so a late response
        // cannot reset changes typed after submission. Other forms stay editable.
        const controls = [...form.elements].filter((control) => !control.disabled);
        controls.forEach((control) => { control.disabled = true; });
        status(state, 'Saving…');
        try {
            const response = await fetcher(safeUrl(form.action || win.location.href), { method: (form.method || 'post').toUpperCase(), body,
                credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-Live-Action': '1' } });
            const result = await tableJson(response);
            notify(result.message || 'Saved successfully.', result.type || 'success');
            dirty.delete(form);
            // Keep committed edit values if the following refresh fails. Only
            // declared new-entry forms should clear after a successful save.
            if (form.hasAttribute('data-live-reset')) form.reset();
            const modal = form.closest('[role="dialog"]');
            const otherEdits = modal && [...dirty].some((other) => other !== form && modal.contains(other));
            if (!otherEdits) closeModal(form);
            state.mutating = false;
            await Alpine?.nextTick();
            publish(Array.isArray(result.topics) ? result.topics : state.topics, state.id);
            // Keep the selected page after a row action. The server clamps it
            // only when the last row on that page was removed.
            const url = state.deferredRefresh?.url || state.pendingFilterUrl || state.url;
            await refresh(state, url, { background: true, history: 'replace', ignoreFocus: true });
        } catch (error) {
            if (error.status === 422) validation(form, error.errors);
            notify(error.message || 'The action failed. Your changes have been preserved.', 'error');
            status(state, 'Not saved. Check the form and try again.', true);
        } finally {
            if (state.mutating === mutation) state.mutating = false;
            controls.forEach((control) => { if (control.isConnected) control.disabled = false; });
        }
    }

    function stateFor(element) { const root = element.closest?.('[data-live-table]'); if (!root) return null; scan(); return regions.get(root.dataset.liveTable); }
    function filterChanged(form, immediate = false) {
        const state = stateFor(form);
        if (!state) return;
        state.transport.invalidate();
        state.pendingFilterUrl = tableFilterUrl(form.action, new win.FormData(form), state.url, state.pages);
        clearTimeout(timers.get(state.id));
        timers.set(state.id, setTimeout(async () => {
            if (await allowDiscard(state)) refresh(state, state.pendingFilterUrl, { history: 'replace' });
        }, immediate ? 0 : 300));
    }

    listen(doc, 'input', (event) => {
        const form = event.target.form;
        if (!form || !stateFor(form)) return;
        if (form.hasAttribute('data-live-filter')) { if (['text', 'search', 'number'].includes(event.target.type)) filterChanged(form); }
        else dirty.add(form);
    });
    listen(doc, 'change', (event) => { const form = event.target.form; if (!form || !stateFor(form)) return; if (form.hasAttribute('data-live-filter')) filterChanged(form, true); else dirty.add(form); });
    listen(doc, 'submit', async (event) => {
        if (event.defaultPrevented) return; // The shared confirmation runs first.
        const form = event.target;
        const state = stateFor(form);
        if (!state || form.hasAttribute('data-live-ignore')) return;
        event.preventDefault();
        if ((form.method || 'get').toLowerCase() === 'get') {
            if (await allowDiscard(state)) refresh(state, tableFilterUrl(form.action, new win.FormData(form), state.url, state.pages), { history: 'push' });
        } else mutate(state, form, event.submitter);
    });
    listen(doc, 'click', async (event) => {
        const refreshButton = event.target.closest?.('[data-live-refresh]');
        if (refreshButton) { const state = stateFor(refreshButton); if (state && await allowDiscard(state)) refresh(state); return; }
        const anchor = event.target.closest?.('a');
        if (!anchor || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
            || anchor.hasAttribute('download') || (anchor.target && anchor.target !== '_self')) return;
        const state = stateFor(anchor);
        if (!state || !(anchor.hasAttribute('data-live-link') || anchor.closest('.nw-pagination')) || anchor.origin !== win.location.origin) return;
        event.preventDefault();
        if (await allowDiscard(state)) refresh(state, anchor.href, { history: 'push' });
    });

    function refreshAll(detail = {}, local = false) {
        scan();
        regions.forEach((state) => {
            if (detail.initial && Number(state.root.dataset.liveInterval || 0) === 0) return;
            if (local && detail.origin === instance && detail.source === state.id) return;
            if (topicsOverlap(state.topics, Array.isArray(detail.topics) ? detail.topics : [])) refresh(state, undefined, { background: true });
        });
    }
    listen(win, 'nw:data-changed', (event) => {
        const detail = { ...event.detail, origin: instance };
        refreshAll(detail, true);
        if (channel) channel.postMessage(detail);
        else { try { win.localStorage.setItem('marthire:table-change', JSON.stringify(detail)); } catch (_) {} }
    });
    try { channel = new win.BroadcastChannel('marthire:tables'); channel.onmessage = (event) => refreshAll(event.data); } catch (_) {}
    listen(win, 'storage', (event) => { if (!channel && event.key === 'marthire:table-change') { try { refreshAll(JSON.parse(event.newValue)); } catch (_) {} } });
    listen(win, 'focus', () => refreshAll());
    listen(doc, 'visibilitychange', () => { if (!doc.hidden) refreshAll(); });
    listen(win, 'popstate', () => { scan(); regions.forEach((state) => { if (!protectedEdits(state)) refresh(state, win.location.href, { history: 'replace' }); }); });
    scan();
    const polling = win.setInterval(() => {
        regions.forEach((state) => { if (Number(state.root.dataset.liveInterval || 0) > 0 && !state.loading) refresh(state, undefined, { background: true }); });
    }, 30000);
    queueMicrotask(() => {
        const signal = doc.querySelector('[data-live-changes]');
        if (signal) { try { publish(JSON.parse(signal.dataset.liveChanges)); return; } catch (_) {} }
        refreshAll({ initial: true });
    });
    return { refreshAll, destroy() { destroyed = true; removers.forEach((remove) => remove()); regions.forEach((state) => state.transport.invalidate()); timers.forEach(clearTimeout); win.clearInterval(polling); channel?.close(); } };
}
