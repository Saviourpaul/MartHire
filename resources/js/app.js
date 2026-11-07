import './bootstrap';
import './hero-map';
import { initializeRealtimeWorkspaces } from './realtime-workspaces';
import { initializeNotificationSound } from './notification-sound';

import Alpine from 'alpinejs';
import { registerDashboardCharts } from './dashboard-charts';
import { registerApplicationForm } from './application-form';
import { registerJobForm } from './job-form';
import { registerToastNotifications } from './toast-notifications';
import { registerLocationSelector } from './location-selector';
import { registerAvatarUpload } from './avatar-upload';
import { registerCandidateReview } from './candidate-review';
import { registerInterviewEmailTemplatePicker } from './interview-email-template-picker';
import { registerAdminUserDocumentViewer } from './admin-user-document-viewer';
import { initializeAlerts } from './alerts';
import { initializeLiveTables } from './live-tables';
import { createDeferredPhoneInitializer } from './deferred-phone';

window.Alpine = Alpine;
Alpine.data('notificationMenu', () => ({
    open: false,
    toggle() {
        this.open = ! this.open;

        if (this.open) {
            window.dispatchEvent(new CustomEvent('nw:data-changed', {
                detail: { topics: ['notifications'], source: 'notification-menu-open' },
            }));
        }
    },
    close() {
        this.open = false;
    },
    closeAndFocus() {
        if (! this.open) return;

        this.close();
        this.$refs.notificationToggle?.focus();
    },
}));
registerDashboardCharts(Alpine);
registerApplicationForm(Alpine);
registerJobForm(Alpine);
registerToastNotifications(Alpine);
registerLocationSelector(Alpine);
registerAvatarUpload(Alpine);
registerCandidateReview(Alpine);
registerInterviewEmailTemplatePicker(Alpine);
registerAdminUserDocumentViewer(Alpine);
initializeAlerts();
initializeRealtimeWorkspaces({ notificationSound: initializeNotificationSound() });

let phoneSupportPromise;
function loadPhoneSupport() {
    if (!phoneSupportPromise) {
        phoneSupportPromise = import('./international-phone')
            .then(({ registerInternationalPhone }) => registerInternationalPhone(Alpine))
            .catch((error) => { phoneSupportPromise = undefined; throw error; });
    }
    return phoneSupportPromise;
}

const deferredPhoneInitializer = createDeferredPhoneInitializer({
    Alpine,
    loadPhoneSupport,
    onError: (error) => console.error('Unable to load phone formatting.', error),
});

Alpine.data('resumeAutofill', (messages = {}) => ({
    dragging: false,
    extracting: false,
    fileName: '',
    message: '',
    error: '',
    async extract(input) {
        const file = input?.files?.[0];
        if (!file) return;

        this.fileName = file.name;
        this.message = '';
        this.error = '';
        this.extracting = true;
        try {
            const { extractResumeText } = await import('./candidate-resume-autofill.js');
            const text = await extractResumeText(file);
            const filled = this.fillRecognizedFields(text);
            this.message = filled ? (messages.success ?? 'We filled fields we could recognize. Review and save each section.')
                : (messages.noMatches ?? 'The résumé was read, but no profile fields could be matched. You can still upload it and fill the sections manually.');
        } catch (error) {
            this.message = error?.message || messages.unsupported || 'This file can still be uploaded. Complete your profile manually if text autofill is unavailable.';
        } finally {
            this.extracting = false;
        }
    },
    fillRecognizedFields(text) {
        const lines = text.split(/\r?\n/).map((line) => line.replace(/\s+/g, ' ').trim()).filter(Boolean);
        const fill = (selector, value) => {
            if (!value) return false;
            const elements = document.querySelectorAll(selector);
            let changed = false;
            elements.forEach((element) => {
                if (element.value?.trim()) return;
                element.value = value;
                element.dispatchEvent(new Event('input', { bubbles: true }));
                element.dispatchEvent(new Event('change', { bubbles: true }));
                changed = true;
            });
            return changed;
        };
        let changed = false;

        const heading = /^(?:professional\s+)?(?:summary|profile|objective|about\s+me)$/i;
        const nextHeading = /^(?:technical\s+)?(?:skills|key\s+skills|core\s+competencies|experience|work\s+experience|employment\s+history|education|education\s+and\s+qualifications|projects|certifications|references)$/i;
        const section = (pattern, stopPattern = nextHeading) => {
            const start = lines.findIndex((line) => pattern.test(line));
            if (start < 0) return [];
            const result = [];
            for (const line of lines.slice(start + 1)) {
                if (stopPattern.test(line)) break;
                result.push(line);
            }
            return result;
        };

        const nameLine = lines.slice(0, 8).find((line) => /^[\p{L}][\p{L} .'-]{2,79}$/u.test(line) && line.trim().split(/\s+/).length >= 2 && !heading.test(line));
        if (nameLine) {
            const nameParts = nameLine.split(/\s+/);
            changed = fill('[name="first_name"]', nameParts.shift()) || changed;
            changed = fill('[name="last_name"]', nameParts.join(' ')) || changed;
        }

        const summary = section(heading).join('\n').slice(0, 3000);
        changed = fill('[name="about"]', summary) || changed;

        const skills = section(/^(?:technical\s+)?(?:skills|key\s+skills|core\s+competencies)$/i)
            .join(', ').split(/[,;|•]+/).map((skill) => skill.trim()).filter(Boolean).slice(0, 30).join(', ');
        changed = fill('[name="skills"]', skills) || changed;

        const linkPatterns = {
            linkedin: /https?:\/\/(?:www\.)?linkedin\.com\/[\w./?=%-]+/i,
            github: /https?:\/\/(?:www\.)?github\.com\/[\w./?=%-]+/i,
        };
        for (const [platform, pattern] of Object.entries(linkPatterns)) {
            const link = text.match(pattern)?.[0]?.replace(/[),.;]+$/, '');
            changed = fill(`[name="links[${platform}]"]`, link) || changed;
        }
        const website = lines.map((line) => line.match(/https?:\/\/(?:www\.)?(?!linkedin\.com|github\.com)[\w.-]+\.[a-z]{2,}(?:\/[^\s]*)?/i)?.[0]).find(Boolean);
        changed = fill('[name="links[website]"]', website?.replace(/[),.;]+$/, '')) || changed;

        const phoneLine = lines.find((line) => /(?:phone|mobile|tel\.?|\+\d)/i.test(line));
        const phone = phoneLine?.match(/\+?\d[\d\s().-]{6,}\d/)?.[0]?.trim();
        changed = fill('[name="phone"]', phone) || changed;

        return changed;
    },
}));

Alpine.data('applicantProfileChecklistEditor', () => ({
    activeSection: null,
    successMessage: '',
    async open(section) {
        this.activeSection = section;
        this.successMessage = '';
        this.$dispatch('open-modal', 'applicant-profile-checklist-editor');
        if (section === 'contact') await deferredPhoneInitializer.initialize(this.$root);
    },
    clearFieldError(event) {
        const field = event.target;
        if (!field?.matches?.('[aria-invalid="true"]')) return;
        field.removeAttribute('aria-invalid');
        const container = field.closest('.nw-field, .nw-form-row, label') || field.parentElement;
        container?.querySelectorAll('[data-async-field-error]').forEach((error) => {
            if (error.dataset.asyncFieldError === field.name) error.remove();
        });
    },
    showFormError(form, message) {
        let notice = form.querySelector('[data-async-form-error]');
        if (!notice) {
            notice = document.createElement('p');
            notice.className = 'nw-field__error';
            notice.setAttribute('role', 'alert');
            notice.dataset.asyncFormError = '';
            form.prepend(notice);
        }
        notice.textContent = message;
    },
    clearFormErrors(form) {
        form.querySelectorAll('[data-async-field-error], [data-async-form-error]').forEach((node) => node.remove());
        form.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
    },
    showValidationErrors(form, errors) {
        this.clearFormErrors(form);
        const fields = [...form.elements].filter((field) => field.name);
        Object.entries(errors || {}).forEach(([key, messages]) => {
            const normalizedKey = key.replace(/\[(.*?)\]/g, '.$1');
            const targetFields = fields.filter((field) => {
                const name = field.name.replace(/\[(.*?)\]/g, '.$1').replace(/\[\]/g, '');
                return name === normalizedKey || normalizedKey === name || normalizedKey.startsWith(`${name}.`);
            });
            if (!targetFields.length) {
                this.showFormError(form, (messages || []).join(' '));
                return;
            }
            targetFields.forEach((field) => {
                field.setAttribute('aria-invalid', 'true');
                const container = field.closest('.nw-field, .nw-form-row, label') || field.parentElement;
                const error = document.createElement('p');
                error.className = 'nw-field__error';
                error.dataset.asyncFieldError = field.name;
                error.setAttribute('role', 'alert');
                error.textContent = (messages || []).join(' ');
                container?.append(error);
            });
        });
        const firstInvalid = form.querySelector('[aria-invalid="true"]');
        firstInvalid?.focus({ preventScroll: true });
    },
    replaceServerMarkup(selector, html) {
        if (typeof html !== 'string') return;
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const replacement = parsed.querySelector(selector);
        const current = document.querySelector(selector);
        if (!replacement || !current) return;
        replacement.querySelectorAll('script').forEach((script) => script.remove());
        const swap = () => {
            Alpine.destroyTree(current);
            current.replaceWith(replacement);
            Alpine.initTree(replacement);
        };
        Alpine.mutateDom(swap);
    },
    async submitForm(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-applicant-profile-async-form]')) return;
        event.preventDefault();
        this.clearFormErrors(form);
        this.successMessage = '';

        const submitButtons = [...form.querySelectorAll('button[type="submit"], input[type="submit"]')];
        const originalDisabled = submitButtons.map((button) => button.disabled);
        const originalLabels = submitButtons.map((button) => button instanceof HTMLInputElement ? button.value : button.textContent);
        submitButtons.forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (button instanceof HTMLInputElement) button.value = 'Saving…';
            else button.textContent = 'Saving…';
        });

        try {
            const response = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (response.status === 422 && payload.errors) this.showValidationErrors(form, payload.errors);
                else this.showFormError(form, payload.message || 'Your changes could not be saved. Please try again.');
                return;
            }

            this.successMessage = payload.message || 'Your profile was updated.';
            this.replaceServerMarkup('#profile-checklist', payload.checklist_html);
            this.replaceServerMarkup('[data-applicant-profile-editor-content]', payload.editor_html);
        } catch (_) {
            this.showFormError(form, 'We could not reach the server. Check your connection and try again.');
        } finally {
            submitButtons.forEach((button, index) => {
                if (!button.isConnected) return;
                button.disabled = originalDisabled[index];
                button.removeAttribute('aria-busy');
                if (button instanceof HTMLInputElement) button.value = originalLabels[index];
                else button.textContent = originalLabels[index];
            });
        }
    },
}));

const scrollToElement = (element) => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    element?.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
};

// Front-page job board: the cards are rendered by Blade, this only decides which of them show.
Alpine.data('jobBoard', (jobs, limit = 6) => ({
    jobs,
    limit,
    filter: 'all',
    get matches() {
        return this.jobs.reduce((found, job, index) => {
            const inFilter = this.filter === 'all'
                || (this.filter === 'remote' ? job.workplace === 'Remote' : job.type === this.filter);
            if (inFilter) found.push(index);
            return found;
        }, []);
    },
    get visible() { return this.matches.slice(0, this.limit); },
    get summary() {
        const total = this.matches.length;
        if (total === 0) return 'No roles found';
        return `Showing ${this.visible.length} of ${total} ${total === 1 ? 'role' : 'roles'}`;
    },
    isShown(index) { return this.visible.includes(index); },
}));

// Jobs page: search, filters, sorting and "show more". Filters live in the address bar so a search can be shared.
Alpine.data('jobSearch', ({ jobs, categories, pageSize = 9 }) => ({
    jobs,
    categories,
    pageSize,
    query: '',
    place: '',
    category: '',
    types: [],
    workplaces: [],
    sort: 'newest',
    shown: pageSize,
    filtersOpen: false,
    init() {
        const params = new URLSearchParams(window.location.search);
        const category = params.get('category') ?? '';
        this.query = params.get('q') ?? '';
        this.place = params.get('place') ?? '';
        this.category = category in this.categories ? category : '';
        this.types = params.getAll('type');
        this.workplaces = params.getAll('workplace');
        this.sort = params.get('sort') === 'title' ? 'title' : 'newest';

        this.$watch(
            () => JSON.stringify([this.query, this.place, this.category, this.types, this.workplaces, this.sort]),
            () => { this.shown = this.pageSize; this.syncUrl(); },
        );
    },
    get matches() {
        const query = this.query.trim().toLowerCase();
        const place = this.place.trim().toLowerCase();
        const found = this.jobs.reduce((list, job, index) => {
            if ((!query || job.text.includes(query))
                && (!place || job.where.includes(place))
                && (!this.category || job.category === this.category)
                && (!this.types.length || this.types.includes(job.type))
                && (!this.workplaces.length || this.workplaces.includes(job.workplace))) list.push(index);
            return list;
        }, []);

        return found.sort((a, b) => this.sort === 'title'
            ? this.jobs[a].title.localeCompare(this.jobs[b].title)
            : this.jobs[b].posted - this.jobs[a].posted);
    },
    get visible() { return this.matches.slice(0, this.shown); },
    get remaining() { return this.matches.length - this.visible.length; },
    get summary() {
        const total = this.matches.length;
        if (total === 0) return 'No roles found';
        return `Showing ${this.visible.length} of ${total} ${total === 1 ? 'role' : 'roles'}`;
    },
    get sidebarCount() { return (this.category ? 1 : 0) + this.types.length + this.workplaces.length; },
    get activeFilters() {
        const list = [];
        if (this.query.trim()) list.push({ key: 'query', label: `“${this.query.trim()}”` });
        if (this.place.trim()) list.push({ key: 'place', label: this.place.trim() });
        if (this.category) list.push({ key: 'category', label: this.categories[this.category] });
        this.types.forEach((value) => list.push({ key: 'types', value, label: value }));
        this.workplaces.forEach((value) => list.push({ key: 'workplaces', value, label: value }));
        return list;
    },
    isShown(index) { return this.visible.includes(index); },
    order(index) { return this.matches.indexOf(index); },
    loadMore() { this.shown += this.pageSize; },
    removeFilter(filter) {
        if (filter.key === 'types' || filter.key === 'workplaces') {
            this[filter.key] = this[filter.key].filter((value) => value !== filter.value);
        } else {
            this[filter.key] = '';
        }
    },
    clearAll() { this.query = ''; this.place = ''; this.category = ''; this.types = []; this.workplaces = []; },
    showResults() { scrollToElement(this.$refs.results); },
    syncUrl() {
        const params = new URLSearchParams();
        if (this.query.trim()) params.set('q', this.query.trim());
        if (this.place.trim()) params.set('place', this.place.trim());
        if (this.category) params.set('category', this.category);
        this.types.forEach((value) => params.append('type', value));
        this.workplaces.forEach((value) => params.append('workplace', value));
        if (this.sort !== 'newest') params.set('sort', this.sort);
        const search = params.toString();
        try { window.history.replaceState(null, '', window.location.pathname + (search ? `?${search}` : '')); } catch (_) {}
    },
}));

// Keep full phone metadata off pages without a phone field. Register before
// Alpine walks the form so nested phone components initialize in the right order.
async function startApplication() {
    if (document.querySelector('[data-phone-field]:not([data-phone-deferred])')) {
        try {
            await loadPhoneSupport();
        } catch (error) {
            // A failed chunk request retains the native country select and input.
            document.querySelectorAll('[data-phone-field]:not([data-phone-deferred])').forEach((field) => field.setAttribute('x-ignore', ''));
            console.error('Unable to load phone formatting.', error);
        }
    }
    Alpine.start();
    initializeLiveTables({ Alpine });
}
startApplication();

const themeQuery = window.matchMedia('(prefers-color-scheme: dark)');
const setTheme = (preference) => {
    document.documentElement.dataset.themePreference = preference;
    document.documentElement.dataset.theme = preference === 'system'
        ? (themeQuery.matches ? 'dark' : 'light')
        : preference;
    document.querySelectorAll('[data-theme-control]').forEach(control => { control.value = preference; });
};

setTheme(document.documentElement.dataset.themePreference || 'system');
document.querySelectorAll('[data-theme-control]').forEach(control => {
    control.addEventListener('change', () => {
        setTheme(control.value);
        try { localStorage.setItem('marthire-theme', control.value); } catch (_) {}
    });
});
document.querySelectorAll('[data-theme-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        setTheme(next);
        try { localStorage.setItem('marthire-theme', next); } catch (_) {}
    });
});
themeQuery.addEventListener('change', () => {
    if (document.documentElement.dataset.themePreference === 'system') setTheme('system');
});
