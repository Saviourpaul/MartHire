const states = new Set(['success', 'error', 'warning', 'info']);
const validState = (type) => states.has(type) ? type : 'info';
const confirmationAttribute = (form, submitter, key) => submitter?.getAttribute?.(key) ?? form.getAttribute(key);

/** Progressive enhancement for normal Laravel forms; native validation and CSRF remain authoritative. */
export function createFormConfirmationHandler(confirm, { createInput = () => document.createElement('input') } = {}) {
    const pending = new WeakSet();
    let submitted = new WeakSet();
    const approved = new WeakMap();

    const handler = async (event) => {
        const form = event.target;
        if (form?.tagName !== 'FORM') return;
        const approval = approved.get(form);
        if (approval && approval.submitter === event.submitter) {
            approval.event = event;
            return;
        }
        if (submitted.has(form)) { event.preventDefault(); return; }
        const message = confirmationAttribute(form, event.submitter, 'data-confirm');
        if (!message) return;
        event.preventDefault();
        if (pending.has(form)) return;
        pending.add(form);
        // An accepted flag belongs to this attempt, never a previous cancelled or failed action.
        form.querySelectorAll('[data-modal-confirmed]').forEach((input) => input.remove());
        try {
            const accepted = await confirm(message, {
                title: confirmationAttribute(form, event.submitter, 'data-confirm-title') || 'Confirm action',
                type: confirmationAttribute(form, event.submitter, 'data-confirm-type') || 'warning',
                confirmLabel: confirmationAttribute(form, event.submitter, 'data-confirm-label') || 'Confirm',
            });
            if (!accepted || !form.isConnected) return;
            const field = confirmationAttribute(form, event.submitter, 'data-confirm-field');
            if (field && /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(field) && !['_token', '_method'].includes(field)) {
                const input = createInput();
                input.type = 'hidden';
                input.name = field;
                input.value = '1';
                input.setAttribute('data-modal-confirmed', '');
                form.append(input);
            }
            const approval = { submitter: event.submitter, event: null };
            approved.set(form, approval);
            // requestSubmit re-runs browser validation and includes the clicked button's name/value.
            form.requestSubmit(event.submitter || undefined);
            if (approval.event && !approval.event.defaultPrevented) submitted.add(form);
        } finally {
            approved.delete(form);
            pending.delete(form);
        }
    };
    handler.reset = () => { submitted = new WeakSet(); };
    return handler;
}

/** Shared, escaped DOM notifications. No candidate data is interpreted as HTML. */
export function initializeAlerts({ document: doc = document, window: win = window } = {}) {
    const dialog = doc.querySelector('[data-alert-dialog]');
    const list = doc.querySelector('[data-toast-list]');
    if (!dialog || !list) return null;
    const title = dialog.querySelector('[data-alert-title]');
    const message = dialog.querySelector('[data-alert-message]');
    const confirmButton = dialog.querySelector('[data-alert-confirm]');
    const cancelButton = dialog.querySelector('[data-alert-cancel]');
    const dismissButton = dialog.querySelector('[data-alert-dismiss]');
    const queue = [];
    let current = null;

    const advance = () => {
        if (current || !queue.length) return;
        current = queue.shift();
        const options = current.options;
        const state = validState(options.type || (options.confirmation ? 'warning' : 'info'));
        title.textContent = options.title || (options.confirmation ? 'Confirm action' : ({ success: 'Success', error: 'Something went wrong', warning: 'Please check', info: 'Information' }[state]));
        message.textContent = options.message;
        dialog.dataset.alertType = state;
        dialog.querySelectorAll('[data-alert-icon]').forEach((icon) => { icon.hidden = icon.dataset.alertIcon !== state; });
        cancelButton.hidden = !options.confirmation;
        confirmButton.textContent = options.confirmLabel || (options.confirmation ? 'Confirm' : 'OK');
        confirmButton.classList.toggle('nw-button--danger', state === 'error');
        confirmButton.classList.toggle('nw-button--primary', state !== 'error');
        dialog.showModal();
        (options.confirmation ? cancelButton : confirmButton).focus();
    };
    const finish = (accepted) => {
        if (!current) return;
        const previous = current;
        current = null;
        dialog.close();
        if (previous.focus?.isConnected) previous.focus.focus({ preventScroll: true });
        previous.resolve(accepted);
        advance();
    };
    const open = (options) => new Promise((resolve) => {
        queue.push({ options, resolve, focus: dialog.contains(doc.activeElement) ? current?.focus : doc.activeElement });
        advance();
    });
    confirmButton.addEventListener('click', () => finish(true));
    cancelButton.addEventListener('click', () => finish(false));
    dismissButton.addEventListener('click', () => finish(false));
    dialog.addEventListener('cancel', (event) => { event.preventDefault(); finish(false); });
    dialog.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') return;
        const controls = [...dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]')]
            .filter((element) => !element.disabled && !element.hidden && element.tabIndex >= 0);
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey && doc.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && doc.activeElement === last) { event.preventDefault(); first?.focus(); }
    });

    const notify = (content, { type = 'info', duration } = {}) => {
        if (typeof content !== 'string' || !content.trim()) return;
        const state = validState(type);
        duration = Number.isFinite(duration) ? Math.max(0, duration) : ['warning', 'error'].includes(state) ? 0 : 6500;
        const toast = doc.createElement('li');
        toast.className = `nw-toast nw-toast--${state}`;
        toast.dataset.toastType = state;
        toast.setAttribute('role', ['error', 'warning'].includes(state) ? 'alert' : 'status');
        toast.setAttribute('aria-atomic', 'true');
        const icon = doc.querySelector(`[data-alert-icon="${state}"] svg`)?.cloneNode(true);
        if (icon) { icon.classList.add('nw-toast__icon'); toast.append(icon); }
        const copy = doc.createElement('p');
        copy.className = 'nw-toast__message';
        copy.textContent = content;
        toast.append(copy);
        const close = doc.createElement('button');
        close.type = 'button';
        close.className = 'nw-toast__close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = '×';
        toast.append(close);
        let timer = null;
        let started = 0;
        let remaining = duration;
        const dismiss = () => { if (timer !== null) win.clearTimeout(timer); toast.remove(); };
        const pause = () => {
            if (timer === null) return;
            win.clearTimeout(timer);
            timer = null;
            remaining = Math.max(0, remaining - (win.performance.now() - started));
        };
        const resume = () => {
            if (!duration || timer !== null || toast.matches(':hover, :focus-within')) return;
            started = win.performance.now();
            timer = win.setTimeout(dismiss, remaining);
        };
        close.addEventListener('click', dismiss);
        toast.addEventListener('pointerenter', pause);
        toast.addEventListener('pointerleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', (event) => { if (!toast.contains(event.relatedTarget)) resume(); });
        // Bound the visible stack while persistent errors remain dismissible.
        const dynamic = list.querySelectorAll('[data-dynamic-toast]');
        if (dynamic.length >= 5) dynamic[0].dispatchEvent(new Event('nw:toast-dismiss'));
        toast.setAttribute('data-dynamic-toast', '');
        toast.addEventListener('nw:toast-dismiss', dismiss);
        list.append(toast);
        resume();
        return { dismiss };
    };
    const api = {
        notify,
        alert: (content, options = {}) => open({ ...options, message: String(content), confirmation: false }),
        confirm: (content, options = {}) => open({ ...options, message: String(content), confirmation: true }),
    };
    const submit = createFormConfirmationHandler(api.confirm, { createInput: () => doc.createElement('input') });
    doc.addEventListener('submit', submit);
    win.addEventListener('pageshow', submit.reset);
    win.addEventListener('nw:notify', (event) => { if (event.detail?.message) notify(event.detail.message, event.detail); });
    win.Marthire = { ...(win.Marthire || {}), ...api };
    return api;
}
