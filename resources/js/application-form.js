// Shared application wizard. The existing Workable fields/uploads and native
// organization application use the same navigation and validation behavior.
import { createFormWizard } from './form-wizard.js';
import { createLocationSelector } from './location-selector.js';

const readableSize = (bytes) => (bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);

// Laravel reports errors as "answers.q-why"; the form names them "answers[q-why]".
const errorKey = (name) => name.replace(/\[\]$/, '').replace(/\[([^\]]+)\]/g, '.$1');

export function createApplicationForm({ preview = true, errors = {}, mode = 'workable', location = {}, documents = [{ key: 0, name: '' }] } = {}) {
    return {
        ...createFormWizard(),
        ...(mode === 'native' ? createLocationSelector(location) : {}),
        preview,
        mode,
        errors: {},
        serverErrors: {},
        fileProblems: {},
        documents,
        nextDocument: documents.length,
        unmappedErrors: [],
        summary: [],
        files: {},
        over: null,
        submitting: false,
        submissionError: '',
        done: false,
        get isLastStep() { return this.isLast(); },
        get progress() { return this.completionPercentage(); },
        init() {
            this.$nextTick(() => {
                this.initializeWizard();
                this.restoreErrors(errors);
                if (this.mode === 'native') this.initLocations();
            });
        },
        destroy() { if (this.mode === 'native') this.destroyLocations(); },
        fields() { return [...this.$refs.form.querySelectorAll('[data-field]')]; },
        controlFor(field) {
            const controls = [...field.querySelectorAll('input, select, textarea')].filter((control) => !control.disabled && control.type !== 'hidden' && !control.hasAttribute?.('data-phone-search'));
            return controls.find((control) => control.hasAttribute?.('data-phone-input')) || controls[0];
        },
        error(name) { return this.errors[name] ?? ''; },
        clear(name) {
            delete this.errors[name];
            delete this.serverErrors[name];
            this.summary = this.summary.filter((item) => item.name !== name);
        },
        handleInput(event) {
            if (event.target.hasAttribute?.('data-phone-search')) return;
            const field = event.target.closest('[data-field]');
            if (!field || event.target.type === 'file') return;
            this.clear(field.dataset.field);
            if (this.mode === 'native' && field.dataset.field === 'country_code') {
                ['state', 'city', 'local_government_area'].forEach((name) => this.clear(name));
            } else if (this.mode === 'native' && field.dataset.field === 'state') {
                ['city', 'local_government_area'].forEach((name) => this.clear(name));
            }
        },
        addDocument() {
            if (this.documents.length < 10) this.documents.push({ key: this.nextDocument++, name: '' });
        },
        removeDocument(index) {
            if (this.documents.length < 2) return;
            this.documents.splice(index, 1);
            Object.keys(this.errors).filter((name) => name.startsWith('education_documents')).forEach((name) => this.clear(name));
            this.fileProblems = Object.fromEntries(Object.entries(this.fileProblems).filter(([name]) => !name.startsWith('education_documents')));
            this.files = Object.fromEntries(Object.entries(this.files).filter(([name]) => !name.startsWith('education_documents')));
        },
        restoreErrors(messages) {
            this.errors = {};
            this.serverErrors = {};
            this.unmappedErrors = [];
            Object.entries(messages || {}).forEach(([key, value]) => {
                const message = Array.isArray(value) ? value[0] : value;
                if (typeof message !== 'string') return;
                const alias = this.mode === 'workable'
                    ? key.replace(/^first_name(?=\.|$)/, 'firstname').replace(/^last_name(?=\.|$)/, 'lastname').replace(/^question_/, 'answers.')
                    : key;
                const field = this.fields().find((candidate) => {
                    const names = [errorKey(candidate.dataset.field), candidate.dataset.errorKey, ...(candidate.dataset.type === 'tel' ? [candidate.dataset.field + '_country'] : [])].filter(Boolean);
                    return names.some((name) => [key, alias].some((error) => error === name || error.startsWith(`${name}.`) || name.startsWith(`${error}.`)));
                });
                if (field) {
                    this.errors[field.dataset.field] = message;
                    this.serverErrors[field.dataset.field] = message;
                } else this.unmappedErrors.push({ name: key, label: 'Application', message });
            });
            this.summary = this.listErrors();
            if (this.summary.length) {
                this.openFirstError();
                this.focusErrors();
            }
        },
        listErrors() {
            return this.fields()
                .filter((field) => this.errors[field.dataset.field])
                .map((field) => ({ name: field.dataset.field, label: field.dataset.label, message: this.errors[field.dataset.field], target: this.controlFor(field)?.id, step: this.panels().indexOf(field.closest('[data-wizard-step]')) }))
                .concat(this.unmappedErrors);
        },
        problemWith(field) {
            const name = field.dataset.field;
            const type = field.dataset.type;
            const required = field.hasAttribute('data-required');

            const control = this.controlFor(field);
            if (!control) return '';
            if (field.hasAttribute('data-location-field') && (this.loadingStates || this.loadingCities)) return 'Please wait for the locations to finish loading.';
            if (field.hasAttribute('data-location-field') && this.locationError) return this.locationError;
            if (type === 'file') {
                if (this.fileProblems[name]) return this.fileProblems[name];
                const file = control.files?.[0];
                if (!file) return required || control.required ? 'Please choose a file.' : '';
                return this.fileProblem(field, file);
            }
            if (type === 'boolean' || type === 'multiple_choice') {
                const checked = field.querySelector('input:checked');
                if (required && !checked) return field.dataset.requiredMessage || 'Please choose an answer.';
                if (field.hasAttribute('data-must-accept') && checked && !['1', 'yes', 'true', 'on'].includes(checked.value)) return 'Please confirm this statement to continue.';
                return '';
            }

            const value = control.value.trim();
            if ((required || control.required) && !value) return type === 'dropdown' ? 'Please choose an option.' : 'This is required.';
            if (value && type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Enter a valid email address, like name@example.com.';
            if (value && type === 'numeric' && Number.isNaN(Number(value))) return 'Enter a number.';
            if (value && control.minLength > 0 && value.length < control.minLength) return `Enter at least ${control.minLength} characters.`;
            if (value && !control.checkValidity()) return control.validationMessage || 'Please check this value.';
            return '';
        },
        validate(index = null) {
            const fields = index === null ? this.fields() : this.stepFields(index);
            fields.forEach((field) => {
                // Disabled City/LGA branches are not submitted and cannot be corrected.
                if (!this.controlFor(field)) {
                    this.clear(field.dataset.field);
                    return;
                }
                const problem = this.problemWith(field);
                if (problem) this.errors[field.dataset.field] = problem;
                else if (!this.serverErrors[field.dataset.field]) delete this.errors[field.dataset.field];
            });
            this.summary = this.listErrors();
            return !fields.some((field) => this.errors[field.dataset.field]) && (index !== null || !this.unmappedErrors.length);
        },
        openFirstError() {
            const first = this.summary.find((item) => Number.isInteger(item.step) && item.step >= 0);
            if (first) this.moveTo(first.step, false);
        },
        focusErrors() { this.$nextTick(() => this.$refs.summary?.focus()); },
        async submit(event) {
            event.preventDefault();
            if (this.submitting) return;
            if (!this.isLastStep) { this.next(); return; }
            this.unmappedErrors = [];
            this.submissionError = '';
            if (!this.validate()) {
                this.openFirstError();
                this.focusErrors();
                return;
            }
            if (this.preview) {
                this.done = true;
                this.$nextTick(() => this.$refs.success?.focus());
                return;
            }
            this.submitting = true;
            try {
                const response = await fetch(this.$refs.form.action, {
                    method: 'POST', body: new FormData(this.$refs.form), credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Please refresh this page and sign in again if your session has expired.');
                const result = await response.json();
                if (response.status === 422) { this.restoreErrors(result.errors || {}); return; }
                if (result.redirect && (response.ok || response.status === 409)) {
                    const destination = new URL(result.redirect, window.location.href);
                    if (!['https:', 'http:'].includes(destination.protocol)) throw new Error('The application destination is unavailable.');
                    window.location.assign(destination.href);
                    return;
                }
                if (!response.ok) throw new Error(response.status === 419 ? 'Your session has expired. Refresh this page before trying again.' : 'We could not submit your application. Your entries are still here; please try again.');
                if (result.submitted !== true) throw new Error('We could not confirm your application. Please try again.');
                this.done = true;
                this.$nextTick(() => this.$refs.success?.focus());
            } catch (error) {
                this.submissionError = error instanceof TypeError ? 'We could not connect. Your entries are still here; please try again.' : error.message;
                this.$nextTick(() => this.$refs.submissionError?.focus());
            } finally { this.submitting = false; }
        },
        goTo(item) {
            if (Number.isInteger(item.step) && item.step >= 0) this.moveTo(item.step, false);
            this.$nextTick(() => {
                const field = this.fields().find((candidate) => candidate.dataset.field === item.name);
                const target = (field && this.controlFor(field)) || (item.target ? document.getElementById(item.target) : null);
                target?.focus();
                target?.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            });
        },
        fileProblem(field, file) {
            const allowed = (field.dataset.accept || '').split(',').map((extension) => extension.trim().replace(/^\./, '').toLowerCase()).filter(Boolean);
            const limit = Number(field.dataset.max) || 0;
            const extension = file.name.split('.').pop().toLowerCase();
            const accepted = allowed.some((rule) => rule.includes('/')
                ? (rule.endsWith('/*') ? file.type?.startsWith(rule.slice(0, -1)) : file.type === rule)
                : rule === extension);
            if (allowed.length && !accepted) return `That file type isn't supported. Please choose a ${allowed.map((item) => item.toUpperCase()).join(', ')} file.`;
            if (limit && file.size > limit) return `That file is ${readableSize(file.size)}. Please choose one under ${readableSize(limit)}.`;
            return '';
        },
        // Files: checked as soon as they are chosen, so the person finds out straight away.
        pick(event) {
            const input = event.target;
            const field = input.closest('[data-field]');
            const name = field.dataset.field;
            const file = input.files[0];

            if (!file) { this.remove(name, input); return; }

            const problem = this.fileProblem(field, file);
            if (problem) {
                this.remove(name, input);
                this.fileProblems[name] = problem;
                this.errors[name] = problem;
            } else {
                this.clear(name);
                delete this.fileProblems[name];
                this.files[name] = { name: file.name, size: readableSize(file.size) };
            }
            this.summary = this.listErrors();
        },
        drop(event, name) {
            this.over = null;
            const input = this.$refs.form.querySelector(`[data-field="${CSS.escape(name)}"] input[type="file"]`);
            if (!input || !event.dataTransfer.files.length) return;
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },
        remove(name, input = null) {
            delete this.files[name];
            delete this.fileProblems[name];
            this.clear(name);
            const control = input ?? this.$refs.form.querySelector(`[data-field="${CSS.escape(name)}"] input[type="file"]`);
            if (control) control.value = '';
        },
    };
}

export function registerApplicationForm(Alpine) {
    Alpine.data('applicationForm', (options) => createApplicationForm(options));
    Alpine.data('nativeApplicationForm', (options) => createApplicationForm({ ...options, mode: 'native', preview: false }));
}
