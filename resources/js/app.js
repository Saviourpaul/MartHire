import './bootstrap';
import './hero-map';

import Alpine from 'alpinejs';
import { registerDashboardCharts } from './dashboard-charts';
import { registerApplicationForm } from './application-form';

window.Alpine = Alpine;
registerDashboardCharts(Alpine);
registerApplicationForm(Alpine);

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

Alpine.start();

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
        try { localStorage.setItem('nearbyworks-theme', control.value); } catch (_) {}
    });
});
document.querySelectorAll('[data-theme-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        setTheme(next);
        try { localStorage.setItem('nearbyworks-theme', next); } catch (_) {}
    });
});
themeQuery.addEventListener('change', () => {
    if (document.documentElement.dataset.themePreference === 'system') setTheme('system');
});
