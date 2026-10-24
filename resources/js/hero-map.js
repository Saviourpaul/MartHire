import { LAND_TOP, LAND_COLUMNS, LAND_ROWS } from './world-land';

// The hero map: dotted land, a network linking the places that have jobs, and a glow travelling along it.
// The flag markers are plain links rendered by Blade; this script only decides where they sit.

const WIDE = window.matchMedia('(min-width: 1024px)'); // same breakpoint as the CSS: from here the map sits behind the hero text

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
const mix = (from, to, amount) => from.map((channel, index) => Math.round(channel + (to[index] - channel) * amount));
const decode = (hex) => hex.split('').map((digit) => parseInt(digit, 16).toString(2).padStart(4, '0')).join('');

class HeroMap {
    constructor(root) {
        this.root = root;
        this.canvas = root.querySelector('canvas');
        this.context = this.canvas.getContext('2d');
        this.dotLayer = document.createElement('canvas');
        this.land = LAND_ROWS.map(decode);
        this.pins = [...root.querySelectorAll('[data-pin]')].map((element) => ({
            element,
            lat: Number(element.dataset.lat),
            lon: Number(element.dataset.lon),
            x: 0,
            y: 0,
        }));
        this.curves = [];
        this.pulses = [];
        this.nextPulse = 0;
        this.visible = true;
        this.frame = 0;
        this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');

        new ResizeObserver(() => this.layout()).observe(root);
        new MutationObserver(() => this.layout()).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        new IntersectionObserver(([entry]) => { this.visible = entry.isIntersecting; this.sync(); }).observe(root);
        this.motion.addEventListener('change', () => this.sync());
        this.layout();
    }

    layout() {
        const width = this.root.clientWidth;
        const height = this.root.clientHeight;
        if (!width || !height) return;

        this.width = width;
        this.height = height;
        this.ratio = Math.min(window.devicePixelRatio || 1, 2);
        for (const layer of [this.canvas, this.dotLayer]) {
            layer.width = Math.round(width * this.ratio);
            layer.height = Math.round(height * this.ratio);
        }

        this.readColours();
        this.project();
        this.link();
        this.paintDots();
        this.placePins();
        this.sync();
        this.draw(performance.now());
        this.root.classList.add('is-ready');
    }

    readColours() {
        const style = getComputedStyle(this.root);
        const channels = (name, fallback) => {
            const parsed = style.getPropertyValue(name).trim().split(/\s+/).map(Number);
            return parsed.length === 3 && parsed.every(Number.isFinite) ? parsed : fallback;
        };
        this.colours = {
            dot: channels('--map-dot', [21, 61, 52]),
            hot: channels('--map-hot', [96, 150, 52]),
            arc: channels('--map-arc', [21, 61, 52]),
            pulse: channels('--map-pulse', [150, 200, 60]),
            dotAlpha: Number(style.getPropertyValue('--map-dot-alpha')) || 0.2,
        };
    }

    // Works out the zoom and position so every marker is visible, on the right-hand side when text sits on the left.
    project() {
        const { width, height } = this;
        const wide = WIDE.matches;
        const left = width * (wide ? 0.49 : 0.1);
        const right = width * (wide ? 0.96 : 0.9);
        const lons = this.pins.map((pin) => pin.lon);
        const lats = this.pins.map((pin) => pin.lat);
        const lonMin = lons.length ? Math.min(...lons) : -15;
        const lonMax = lons.length ? Math.max(...lons) : 45;
        const latMin = lats.length ? Math.min(...lats) : 28;
        const latMax = lats.length ? Math.max(...lats) : 58;
        const lonSpan = Math.max(lonMax - lonMin, 10) + 12;
        const latSpan = Math.max(latMax - latMin, 10) + 16;

        this.scale = clamp(Math.min((right - left) / lonSpan, (height * 0.8) / latSpan), 1.2, wide ? 13 : 10);
        const spare = Math.max(0, right - left - lonSpan * this.scale);
        this.originX = left + spare / 2 - (lonMin - 6) * this.scale; // x = originX + longitude * scale
        this.originY = height / 2 + ((latMin + latMax) / 2) * this.scale; // y = originY - latitude * scale

        for (const pin of this.pins) {
            pin.x = this.originX + pin.lon * this.scale;
            pin.y = this.originY - pin.lat * this.scale;
        }
    }

    // Joins the markers into one network (nearest neighbours first), then closes a few loops so it is not just a tree.
    link() {
        const { pins } = this;
        const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
        const edges = [];
        const has = new Set();
        const add = (i, j) => {
            const key = i < j ? `${i}-${j}` : `${j}-${i}`;
            if (has.has(key)) return;
            has.add(key);
            edges.push([i, j]);
        };

        const joined = new Set(pins.length ? [0] : []);
        while (joined.size < pins.length) {
            let best = null;
            for (const i of joined) {
                for (let j = 0; j < pins.length; j++) {
                    if (joined.has(j)) continue;
                    const d = distance(pins[i], pins[j]);
                    if (!best || d < best.d) best = { i, j, d };
                }
            }
            add(best.i, best.j);
            joined.add(best.j);
        }

        const degree = pins.map((_, index) => edges.filter(([a, b]) => a === index || b === index).length);
        pins.forEach((pin, index) => {
            if (degree[index] !== 1) return;
            const nearest = pins
                .map((other, j) => ({ j, d: distance(pin, other) }))
                .filter(({ j }) => j !== index && !has.has(index < j ? `${index}-${j}` : `${j}-${index}`))
                .sort((a, b) => a.d - b.d)[0];
            if (nearest) add(index, nearest.j);
        });

        this.curves = edges.map(([i, j]) => {
            const a = pins[i];
            const b = pins[j];
            const length = Math.hypot(b.x - a.x, b.y - a.y) || 1;
            let nx = (b.y - a.y) / length;
            let ny = -(b.x - a.x) / length;
            if (ny > 0) { nx = -nx; ny = -ny; } // always bow upwards, like a flight path
            return { a, b, c: { x: (a.x + b.x) / 2 + nx * length * 0.24, y: (a.y + b.y) / 2 + ny * length * 0.24 } };
        });
        this.pulses = [];
    }

    paintDots() {
        const context = this.dotLayer.getContext('2d');
        context.setTransform(this.ratio, 0, 0, this.ratio, 0, 0);
        context.clearRect(0, 0, this.width, this.height);

        const { dot, hot, dotAlpha } = this.colours;
        const step = Math.max(1, Math.ceil(5 / this.scale));
        const radius = clamp(this.scale * step * 0.2, 0.7, 2.4);
        const reach = Math.max(60, this.scale * 16);

        for (let row = 0; row < this.land.length; row += step) {
            const y = this.originY - (LAND_TOP - row - 0.5) * this.scale;
            if (y < -radius || y > this.height + radius) continue;
            const bits = this.land[row];

            for (let column = 0; column < LAND_COLUMNS; column += step) {
                if (bits[column] !== '1') continue;
                const x = this.originX + (column - 180 + 0.5) * this.scale;
                if (x < -radius || x > this.width + radius) continue;

                let heat = 0;
                for (const pin of this.pins) heat = Math.max(heat, 1 - Math.hypot(x - pin.x, y - pin.y) / reach);
                const [r, g, b] = mix(dot, hot, heat);
                context.fillStyle = `rgb(${r} ${g} ${b} / ${(dotAlpha + heat * 0.55).toFixed(2)})`;
                context.beginPath();
                context.arc(x, y, radius * (1 + heat * 0.35), 0, Math.PI * 2);
                context.fill();
            }
        }
    }

    placePins() {
        for (const pin of this.pins) {
            const inside = pin.x > 8 && pin.x < this.width - 8 && pin.y > 8 && pin.y < this.height - 8;
            pin.element.hidden = !inside;
            pin.element.style.left = `${pin.x.toFixed(1)}px`;
            pin.element.style.top = `${pin.y.toFixed(1)}px`;
            pin.element.dataset.edge = pin.x > this.width * 0.7 ? 'end' : pin.x < this.width * 0.3 ? 'start' : 'middle';
        }
    }

    pointOn({ a, b, c }, t) {
        const u = 1 - t;
        return { x: u * u * a.x + 2 * u * t * c.x + t * t * b.x, y: u * u * a.y + 2 * u * t * c.y + t * t * b.y };
    }

    draw(now) {
        const { context, colours } = this;
        context.setTransform(this.ratio, 0, 0, this.ratio, 0, 0);
        context.clearRect(0, 0, this.width, this.height);
        context.drawImage(this.dotLayer, 0, 0, this.width, this.height);

        context.lineWidth = 1;
        context.strokeStyle = `rgb(${colours.arc.join(' ')} / 0.3)`;
        for (const { a, b, c } of this.curves) {
            context.beginPath();
            context.moveTo(a.x, a.y);
            context.quadraticCurveTo(c.x, c.y, b.x, b.y);
            context.stroke();
        }

        if (!this.frame) return; // standing still: no travelling glow

        if (this.curves.length && this.pulses.length < 3 && now >= this.nextPulse) {
            this.pulses.push({ curve: this.curves[Math.floor(Math.random() * this.curves.length)], reverse: Math.random() < 0.5, start: now, duration: 1500 + Math.random() * 1300 });
            this.nextPulse = now + 450 + Math.random() * 900;
        }
        this.pulses = this.pulses.filter((pulse) => now - pulse.start < pulse.duration);

        for (const pulse of this.pulses) {
            const progress = (now - pulse.start) / pulse.duration;
            const head = progress;
            const tail = Math.max(0, progress - 0.3);
            const steps = 14;
            context.lineCap = 'round';
            context.lineWidth = 2;
            for (let step = 0; step < steps; step++) {
                const t0 = tail + ((head - tail) * step) / steps;
                const t1 = tail + ((head - tail) * (step + 1)) / steps;
                const from = this.pointOn(pulse.curve, pulse.reverse ? 1 - t0 : t0);
                const to = this.pointOn(pulse.curve, pulse.reverse ? 1 - t1 : t1);
                context.strokeStyle = `rgb(${colours.pulse.join(' ')} / ${((step + 1) / steps) * 0.9})`;
                context.beginPath();
                context.moveTo(from.x, from.y);
                context.lineTo(to.x, to.y);
                context.stroke();
            }
            const tip = this.pointOn(pulse.curve, pulse.reverse ? 1 - head : head);
            context.fillStyle = `rgb(${colours.pulse.join(' ')})`;
            context.shadowColor = `rgb(${colours.pulse.join(' ')})`;
            context.shadowBlur = 10;
            context.beginPath();
            context.arc(tip.x, tip.y, 2.6, 0, Math.PI * 2);
            context.fill();
            context.shadowBlur = 0;
        }
    }

    // Animates only while the map is on screen and motion is welcome; otherwise draws one still picture.
    sync() {
        const animate = this.visible && !this.motion.matches && this.curves.length > 0;

        if (animate && !this.frame) {
            const tick = (now) => {
                this.draw(now);
                this.frame = requestAnimationFrame(tick);
            };
            this.frame = requestAnimationFrame(tick);
        } else if (!animate && this.frame) {
            cancelAnimationFrame(this.frame);
            this.frame = 0;
            this.pulses = [];
            this.draw(performance.now());
        }
    }
}

document.querySelectorAll('[data-hero-map]').forEach((root) => {
    if (root.querySelector('canvas')) new HeroMap(root);
});
