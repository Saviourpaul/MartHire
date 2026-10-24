// The dashboard's activity chart: a smooth area chart with a toggle between series and a hover read-out.
// The numbers also exist as a plain table in the page for screen readers (see dashboard.blade.php).

const niceMax = (value) => {
    const step = value <= 5 ? 1 : value <= 10 ? 2 : value <= 25 ? 5 : value <= 50 ? 10 : 20;
    return Math.ceil(value / step) * step;
};

const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);

export function registerDashboardCharts(Alpine) {
    Alpine.data('areaChart', ({ labels, series, order }) => ({
        labels,
        series,
        key: order[0],
        hover: null,
        width: 640,
        height: 280,
        pad: { top: 18, right: 18, bottom: 34, left: 38 },
        init() {
            // The frame decides the size (it stretches to match the panel beside it); the SVG simply fills it.
            new ResizeObserver(([entry]) => {
                this.width = Math.max(260, Math.round(entry.contentRect.width));
                this.height = Math.max(220, Math.round(entry.contentRect.height));
            }).observe(this.$refs.frame);
        },
        get values() { return this.series[this.key].values; },
        get ceiling() { return niceMax(Math.max(...this.values, 1)); },
        get ticks() {
            const step = this.ceiling <= 4 ? 1 : Math.ceil(this.ceiling / 4);
            const ticks = [];
            for (let value = 0; value <= this.ceiling; value += step) ticks.push({ value, y: this.y(value) });
            return ticks;
        },
        get points() { return this.values.map((value, index) => ({ x: this.x(index), y: this.y(value), value, label: this.labels[index] })); },
        // Gridlines and axis labels are written as SVG text, because Alpine cannot loop (x-for) inside an <svg>.
        get gridMarkup() {
            const { left } = this.pad;
            const right = this.width - this.pad.right;
            const lines = this.ticks.map((tick) => `<line class="nw-chart__grid" x1="${left}" x2="${right}" y1="${tick.y}" y2="${tick.y}"/><text class="nw-chart__tick" x="${left - 10}" y="${tick.y + 4}" text-anchor="end">${tick.value}</text>`);
            const names = this.labels.map((label, index) => `<text class="nw-chart__tick" x="${this.x(index)}" y="${this.height - 10}" text-anchor="middle">${escapeHtml(label)}</text>`);
            return lines.join('') + names.join('');
        },
        get line() {
            const p = this.points;
            if (p.length < 2) return '';
            const floor = this.y(0);
            const top = this.pad.top;
            let d = `M${p[0].x},${p[0].y}`;
            for (let i = 0; i < p.length - 1; i++) {
                const before = p[i - 1] ?? p[i];
                const after = p[i + 2] ?? p[i + 1];
                const c1x = p[i].x + (p[i + 1].x - before.x) / 6;
                const c1y = Math.min(floor, Math.max(top, p[i].y + (p[i + 1].y - before.y) / 6));
                const c2x = p[i + 1].x - (after.x - p[i].x) / 6;
                const c2y = Math.min(floor, Math.max(top, p[i + 1].y - (after.y - p[i].y) / 6));
                d += ` C${c1x},${c1y} ${c2x},${c2y} ${p[i + 1].x},${p[i + 1].y}`;
            }
            return d;
        },
        get area() {
            const p = this.points;
            return p.length < 2 ? '' : `${this.line} L${p[p.length - 1].x},${this.y(0)} L${p[0].x},${this.y(0)} Z`;
        },
        get active() { return this.hover === null ? null : this.points[this.hover]; },
        x(index) { return this.pad.left + (index / Math.max(this.values.length - 1, 1)) * (this.width - this.pad.left - this.pad.right); },
        y(value) { return this.pad.top + (1 - value / this.ceiling) * (this.height - this.pad.top - this.pad.bottom); },
        total(key) { return this.series[key].values.reduce((sum, value) => sum + value, 0); },
        move(event) {
            const box = this.$refs.frame.getBoundingClientRect();
            const x = event.clientX - box.left;
            let nearest = 0;
            this.points.forEach((point, index) => { if (Math.abs(point.x - x) < Math.abs(this.points[nearest].x - x)) nearest = index; });
            this.hover = nearest;
        },
        leave() { this.hover = null; },
    }));
}
