// Shared application wizard. The existing Workable fields/uploads and native
// organization application use the same navigation and validation behavior.
import { createFormWizard } from './form-wizard.js';
import { createLocationSelector } from './location-selector.js';

const readableSize = (bytes) => (bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);
const UPLOAD_TIMEOUT_ERROR = 'application-upload-timeout';

function submitMultipartForm(form, onProgress) {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();
        request.open('POST', form.action, true);
        request.withCredentials = true;
        request.timeout = 300000;
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) onProgress(Math.min(100, Math.round((event.loaded / event.total) * 100)));
        });
        request.addEventListener('load', () => resolve({
            status: request.status,
            ok: request.status >= 200 && request.status < 300,
            headers: { get: (name) => request.getResponseHeader(name) },
            json: async () => JSON.parse(request.responseText),
        }));
        request.addEventListener('error', () => reject(new TypeError('The application request could not connect.')));
        request.addEventListener('timeout', () => reject(new Error(UPLOAD_TIMEOUT_ERROR)));
        request.send(new FormData(form));
    });
}

// Laravel reports errors as "answers.q-why"; the form names them "answers[q-why]".
const errorKey = (name) => name.replace(/\[\]$/, '').replace(/\[([^\]]+)\]/g, '.$1');

export function createApplicationForm({ preview = true, errors = {}, mode = 'workable', location = {}, documents = [{ key: 0, name: '' },], profileDisabilityStatus = '', profileEmploymentCurrent = false, identityRequired = false } = {}) {
    return {
        ...createFormWizard(),
        ...(mode === 'native' ? createLocationSelector(location) : {}),
        preview,
        mode,
        errors: {},
        serverErrors: {},
        fileProblems: {},
        documents,
        profileDisabilityStatus,
        profileEmploymentCurrent,
        identityRequired: Boolean(identityRequired),
        nextDocument: documents.length,
        unmappedErrors: [],
        summary: [],
        files: {},
        over: null,
        submitting: false,
        uploadProgress: null,
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
            } else if (this.mode === 'native' && field.dataset.field === 'profile_contact.disability_status' && event.target.value === 'no') {
                this.clear('profile_contact.disability_details');
                const disabilityDetails = this.$refs.form.querySelector('[data-field="profile_contact.disability_details"] input');
                if (disabilityDetails) disabilityDetails.value = '';
            } else if (this.mode === 'native' && field.dataset.field === 'profile_employment.is_current' && event.target.checked) {
                const endDate = this.$refs.form.querySelector('[data-field="profile_employment.end_date"] input');
                if (endDate) endDate.value = '';
                this.clear('profile_employment.end_date');
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

            if (field.hasAttribute('data-location-field') && (this.loadingStates || this.loadingCities)) return 'Please wait for the locations to finish loading.';
            if (field.hasAttribute('data-location-field') && this.locationError) return this.locationError;
            const control = this.controlFor(field);
            if (!control) return '';
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
            const fileInputs = [...this.$refs.form.querySelectorAll('input[type="file"]')]
                .filter((input) => !input.disabled && input.name);
            const maxTotalUploadBytes = Number(this.$refs.form.dataset.maxTotalUploadBytes);
            const maxUploadFiles = Number(this.$refs.form.dataset.maxUploadFiles);
            const selectedUploadBytes = fileInputs
                .reduce((total, input) => total + [...(input.files || [])].reduce((size, file) => size + file.size, 0), 0);
            const selectedUploadCount = fileInputs.reduce((total, input) => total + (input.files?.length || 0), 0);
            if (Number.isFinite(maxUploadFiles) && selectedUploadCount > maxUploadFiles) {
                this.submissionError = this.message('errorUploadCount', 'You selected :actual files. This form can send up to :limit files at once. Remove a file and try again.', {
                    actual: selectedUploadCount,
                    limit: maxUploadFiles,
                });
                this.$nextTick(() => this.$refs.submissionError?.focus());
                return;
            }
            if (Number.isFinite(maxTotalUploadBytes) && maxTotalUploadBytes >= 0 && selectedUploadBytes > maxTotalUploadBytes) {
                this.submissionError = this.message('errorUploadTotal', 'Your selected files total :selected. This form can send up to :limit at once. Remove a file or choose smaller files, then try again.', {
                    selected: readableSize(selectedUploadBytes),
                    limit: readableSize(maxTotalUploadBytes),
                });
                this.$nextTick(() => this.$refs.submissionError?.focus());
                return;
            }
            this.submitting = true;
            this.uploadProgress = null;
            try {
                const response = await submitMultipartForm(this.$refs.form, (progress) => {
                    this.uploadProgress = progress;
                });
                if (response.status === 413) {
                    let message = this.message('errorUploadLarge', 'The application upload is larger than the server allows. Remove a file or choose smaller files, then try again.');
                    try {
                        const result = await response.json();
                        if (typeof result.message === 'string') message = result.message;
                    } catch {}
                    throw new Error(message);
                }
                if (!response.headers.get('content-type')?.includes('application/json')) {
                    if (response.status === 401 || response.status === 419 || (response.status >= 200 && response.status < 300)) {
                        throw new Error(this.message('errorSession', 'Please refresh this page and sign in again if your session has expired.'));
                    }
                    if (response.status === 403) throw new Error(this.message('errorPermission', 'Your account does not have permission to submit this application.'));
                    if (response.status === 404) throw new Error(this.message('errorNotFound', 'This job is no longer accepting applications.'));
                    if (response.status === 429) throw new Error(this.message('errorRateLimit', 'Too many attempts. Wait a moment, then try again.'));
                    if (response.status >= 500) throw new Error(this.message('errorServer', 'The server could not complete your application. Your entries are still here; please try again.'));
                    throw new Error(this.message('errorGeneric', 'We could not submit your application. Your entries are still here; please try again.'));
                }
                const result = await response.json();
                if (response.status === 422) { this.restoreErrors(result.errors || {}); return; }
                if (result.redirect && (response.ok || response.status === 409)) {
                    const destination = new URL(result.redirect, window.location.href);
                    if (!['https:', 'http:'].includes(destination.protocol)) throw new Error('The application destination is unavailable.');
                    window.location.assign(destination.href);
                    return;
                }
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) {
                        throw new Error(this.message('errorSession', 'Please refresh this page and sign in again if your session has expired.'));
                    }
                    if (response.status === 403) throw new Error(this.message('errorPermission', 'Your account does not have permission to submit this application.'));
                    if (response.status === 404) throw new Error(this.message('errorNotFound', 'This job is no longer accepting applications.'));
                    if (response.status === 429) throw new Error(this.message('errorRateLimit', 'Too many attempts. Wait a moment, then try again.'));
                    if (response.status >= 500) throw new Error(this.message('errorServer', 'The server could not complete your application. Your entries are still here; please try again.'));
                    if (typeof result.message === 'string' && result.message.trim()) throw new Error(result.message);
                    throw new Error(this.message('errorGeneric', 'We could not submit your application. Your entries are still here; please try again.'));
                }
                if (result.submitted !== true) throw new Error(this.message('errorGeneric', 'We could not confirm your application. Please try again.'));
                this.done = true;
                this.$nextTick(() => this.$refs.success?.focus());
            } catch (error) {
                if (error instanceof TypeError) {
                    this.submissionError = this.message('errorConnection', 'We could not connect. Your entries are still here; please try again.');
                } else if (error?.message === UPLOAD_TIMEOUT_ERROR) {
                    this.submissionError = this.message('errorTimeout', 'The upload took too long. Check your connection and try again.');
                } else if (error instanceof SyntaxError) {
                    this.submissionError = this.message('errorGeneric', 'We could not submit your application. Your entries are still here; please try again.');
                } else {
                    this.submissionError = error.message;
                }
                this.$nextTick(() => this.$refs.submissionError?.focus());
            } finally {
                this.submitting = false;
                this.uploadProgress = null;
            }
        },
        submissionProgressLabel() {
            const labels = this.$refs.progressStatus?.dataset ?? {};
            if (this.uploadProgress === null) return labels.progressPreparing ?? 'Preparing your application…';
            if (this.uploadProgress >= 100) return labels.progressFinishing ?? 'Files uploaded. Finishing your submission…';
            return `${labels.progressUploading ?? 'Uploading your application…'} ${this.uploadProgress}%`;
        },
        message(key, fallback, replacements = {}) {
            let message = this.$refs.progressStatus?.dataset?.[key] ?? fallback;
            Object.entries(replacements).forEach(([name, value]) => {
                const replacement = String(value);
                message = message.split(`__${name.toUpperCase()}__`).join(replacement);
                message = message.split(`:${name}`).join(replacement);
            });
            return message;
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
            const fieldLimit = Number(field.dataset.max) || 0;
            const runtimeLimit = Number(this.$refs.form?.dataset.maxSingleUploadBytes);
            if (Number.isFinite(runtimeLimit) && runtimeLimit === 0) return this.message('errorNoFileUploads', 'The server is not configured to accept file uploads. Try again later.');
            const limit = Number.isFinite(runtimeLimit) && runtimeLimit >= 0
                ? (fieldLimit ? Math.min(fieldLimit, runtimeLimit) : runtimeLimit)
                : fieldLimit;
            const extension = file.name.split('.').pop().toLowerCase();
            const accepted = allowed.some((rule) => rule.includes('/')
                ? (rule.endsWith('/*') ? file.type?.startsWith(rule.slice(0, -1)) : file.type === rule)
                : rule === extension);
            if (allowed.length && !accepted) return this.message('errorFileType', "This file type isn't supported. Choose a :types file.", {
                types: allowed.map((item) => item.toUpperCase()).join(', '),
            });
            if (limit && file.size > limit) return this.message('errorFileSize', 'That file is :selected. Please choose one under :limit.', {
                selected: readableSize(file.size),
                limit: readableSize(limit),
            });
            return '';
        },
        // Files: checked as soon as they are chosen, so the person finds out straight away.
        pick(event) {
            const input = event.target;
            const field = input.closest('[data-field]');
            const name = field.dataset.field;
            const file = input.files[0];

            if (!file) { this.remove(name, input); return; }

            if (name === 'identification_document') {
                ['identification_type', 'identity_issuing_country_code'].forEach((fieldName) => {
                    const control = this.$refs.form.querySelector(`[name="${fieldName}"]`);
                    if (control) control.value = '';
                    this.clear(fieldName);
                });
            }

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
