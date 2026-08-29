(function () {
    const svgNs = 'http://www.w3.org/2000/svg';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function formatNumber(value) {
        return new Intl.NumberFormat().format(Number(value) || 0);
    }

    function createSvg(width, height, className) {
        const svg = document.createElementNS(svgNs, 'svg');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', height);
        svg.setAttribute('class', className || '');
        svg.setAttribute('role', 'img');
        return svg;
    }

    function appendSvg(parent, tag, attributes, text) {
        const element = document.createElementNS(svgNs, tag);

        Object.entries(attributes || {}).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });

        if (text !== undefined) {
            element.textContent = text;
        }

        parent.appendChild(element);
        return element;
    }

    function createTooltip(container) {
        const tooltip = document.createElement('div');
        tooltip.className = 'pointer-events-none absolute z-30 hidden rounded-lg bg-gray-900 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-lg dark:bg-gray-700';
        container.classList.add('relative');
        container.appendChild(tooltip);
        return tooltip;
    }

    function showTooltip(container, tooltip, event, html) {
        const bounds = container.getBoundingClientRect();
        tooltip.replaceChildren(...html);
        tooltip.classList.remove('hidden');

        const x = event.clientX - bounds.left + 14;
        const y = event.clientY - bounds.top - tooltip.offsetHeight - 14;
        const maxX = Math.max(8, bounds.width - tooltip.offsetWidth - 8);

        tooltip.style.left = `${Math.min(Math.max(8, x), maxX)}px`;
        tooltip.style.top = `${Math.max(8, y)}px`;
    }

    function hideTooltip(tooltip) {
        tooltip.classList.add('hidden');
    }

    function tooltipLine(label, name, value, color) {
        const fragment = document.createDocumentFragment();
        const labelElement = document.createElement('div');
        labelElement.className = 'mb-1 text-gray-300';
        labelElement.textContent = label;

        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';

        const dot = document.createElement('span');
        dot.className = 'h-2 w-2 rounded-full';
        dot.style.backgroundColor = color;

        const text = document.createElement('span');
        text.textContent = `${name}: ${formatNumber(value)}`;

        row.appendChild(dot);
        row.appendChild(text);
        fragment.appendChild(labelElement);
        fragment.appendChild(row);

        return [fragment];
    }

    function tooltipStatus(label, value, color) {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';

        const dot = document.createElement('span');
        dot.className = 'h-2 w-2 rounded-full';
        dot.style.backgroundColor = color;

        const text = document.createElement('span');
        text.textContent = `${label}: ${formatNumber(value)}`;

        row.appendChild(dot);
        row.appendChild(text);

        return [row];
    }

    function showChartMessage(element, message, tone) {
        if (!element) {
            return;
        }

        element.replaceChildren();

        const messageElement = document.createElement('div');
        messageElement.className = [
            'flex min-h-[220px] items-center justify-center rounded-xl text-theme-sm',
            tone === 'error' ? 'text-error-500' : 'text-gray-500 dark:text-gray-400',
        ].join(' ');
        messageElement.textContent = message;
        element.appendChild(messageElement);
    }

    function maxSeriesValue(series) {
        return Math.max(0, ...series.flatMap((item) => item.data || []).map((value) => Number(value) || 0));
    }

    function niceMax(value) {
        if (value <= 0) {
            return 5;
        }

        const power = Math.pow(10, Math.floor(Math.log10(value)));
        return Math.ceil(value / power) * power;
    }

    function linePath(points) {
        if (points.length === 0) {
            return '';
        }

        return points.reduce((path, point, index) => {
            if (index === 0) {
                return `M ${point.x} ${point.y}`;
            }

            const previous = points[index - 1];
            const controlX = previous.x + (point.x - previous.x) / 2;
            return `${path} C ${controlX} ${previous.y}, ${controlX} ${point.y}, ${point.x} ${point.y}`;
        }, '');
    }

    function polarToCartesian(cx, cy, radius, angle) {
        const radians = (angle - 90) * Math.PI / 180;

        return {
            x: cx + radius * Math.cos(radians),
            y: cy + radius * Math.sin(radians),
        };
    }

    function describeArc(cx, cy, radius, startAngle, endAngle) {
        if (endAngle - startAngle >= 359.99) {
            return [
                describeArc(cx, cy, radius, 0, 180),
                describeArc(cx, cy, radius, 180, 359.99),
            ].join(' ');
        }

        const start = polarToCartesian(cx, cy, radius, endAngle);
        const end = polarToCartesian(cx, cy, radius, startAngle);
        const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';

        return `M ${start.x} ${start.y} A ${radius} ${radius} 0 ${largeArcFlag} 0 ${end.x} ${end.y}`;
    }

    function renderLineChart(element, payload) {
        if (!element) {
            return;
        }

        const categories = payload.categories || [];
        const series = payload.series || [];
        const width = 1300;
        const height = 350;
        const padding = { top: 24, right: 36, bottom: 42, left: 64 };
        const chartWidth = width - padding.left - padding.right;
        const chartHeight = height - padding.top - padding.bottom;
        const maxValue = niceMax(maxSeriesValue(series));
        const colors = ['#1a619a', '#9CB9FF'];

        element.replaceChildren();
        const tooltip = createTooltip(element);
        const svg = createSvg(width, height, 'overflow-visible');
        element.appendChild(svg);

        for (let index = 0; index <= 5; index++) {
            const value = Math.round((maxValue / 5) * index);
            const y = padding.top + chartHeight - (chartHeight / 5) * index;

            appendSvg(svg, 'line', {
                x1: padding.left,
                y1: y,
                x2: width - padding.right,
                y2: y,
                stroke: '#EAECF0',
                'stroke-dasharray': '5 5',
            });

            appendSvg(svg, 'text', {
                x: 24,
                y: y + 4,
                fill: '#98A2B3',
                'font-size': '12',
                'font-family': 'Outfit, sans-serif',
            }, formatNumber(value));
        }

        categories.forEach((label, index) => {
            const x = padding.left + (categories.length <= 1 ? chartWidth / 2 : (chartWidth / (categories.length - 1)) * index);
            appendSvg(svg, 'text', {
                x,
                y: height - 12,
                fill: '#98A2B3',
                'font-size': '12',
                'font-family': 'Outfit, sans-serif',
                'text-anchor': 'middle',
            }, label);
        });

        series.forEach((item, seriesIndex) => {
            const values = item.data || [];
            const points = values.map((value, index) => ({
                x: padding.left + (values.length <= 1 ? chartWidth / 2 : (chartWidth / (values.length - 1)) * index),
                y: padding.top + chartHeight - ((Number(value) || 0) / maxValue) * chartHeight,
                value: Number(value) || 0,
                label: categories[index] || '',
            }));

            appendSvg(svg, 'path', {
                d: linePath(points),
                fill: 'none',
                stroke: colors[seriesIndex % colors.length],
                'stroke-width': '3',
                'stroke-linecap': 'round',
                'stroke-linejoin': 'round',
            });

            points.forEach((point) => {
                const marker = appendSvg(svg, 'circle', {
                    cx: point.x,
                    cy: point.y,
                    r: 10,
                    fill: 'transparent',
                    stroke: 'transparent',
                    'stroke-width': '1',
                    class: 'cursor-pointer',
                });

                marker.addEventListener('mouseenter', (event) => {
                    marker.setAttribute('fill', colors[seriesIndex % colors.length]);
                    marker.setAttribute('fill-opacity', '0.14');
                    marker.setAttribute('stroke', colors[seriesIndex % colors.length]);
                    showTooltip(element, tooltip, event, tooltipLine(
                        point.label,
                        item.name,
                        point.value,
                        colors[seriesIndex % colors.length],
                    ));
                });

                marker.addEventListener('mousemove', (event) => {
                    showTooltip(element, tooltip, event, tooltipLine(
                        point.label,
                        item.name,
                        point.value,
                        colors[seriesIndex % colors.length],
                    ));
                });

                marker.addEventListener('mouseleave', () => {
                    marker.setAttribute('fill', 'transparent');
                    marker.setAttribute('stroke', 'transparent');
                    hideTooltip(tooltip);
                });
            });
        });
    }

    function renderDonutChart(element, payload) {
        if (!element) {
            return;
        }

        const labels = payload?.labels || ['No Applications'];
        const values = payload?.series || [0];
        const colors = payload?.colors || ['#dde9ff'];
        const total = values.reduce((sum, value) => sum + (Number(value) || 0), 0);
        const displayValues = total > 0 ? values : [1];
        const displayLabels = total > 0 ? labels : ['No Applications'];
        const displayColors = total > 0 ? colors : ['#dde9ff'];
        const displayTotal = displayValues.reduce((sum, value) => sum + (Number(value) || 0), 0);
        const size = 290;
        const center = size / 2;
        const radius = 100;

        element.replaceChildren();
        const tooltip = createTooltip(element);
        const wrapper = document.createElement('div');
        wrapper.className = 'w-full';
        const svg = createSvg(size, size, 'mx-auto max-w-full');
        wrapper.appendChild(svg);
        element.appendChild(wrapper);

        let currentAngle = 0;
        displayValues.forEach((value, index) => {
            const degrees = ((Number(value) || 0) / displayTotal) * 360;
            const path = appendSvg(svg, 'path', {
                d: describeArc(center, center, radius, currentAngle, currentAngle + degrees),
                fill: 'none',
                stroke: displayColors[index % displayColors.length],
                'stroke-width': '54',
                'stroke-linecap': 'butt',
                class: 'cursor-pointer',
            });

            const tooltipContent = () => tooltipStatus(
                displayLabels[index],
                total > 0 ? values[index] : 0,
                displayColors[index % displayColors.length],
            );

            path.addEventListener('mouseenter', (event) => {
                path.setAttribute('stroke-width', '60');
                showTooltip(element, tooltip, event, tooltipContent());
            });
            path.addEventListener('mousemove', (event) => showTooltip(element, tooltip, event, tooltipContent()));
            path.addEventListener('mouseleave', () => {
                path.setAttribute('stroke-width', '54');
                hideTooltip(tooltip);
            });
            currentAngle += degrees;
        });

        const legend = document.createElement('div');
        legend.className = 'mt-4 flex flex-wrap items-center justify-center gap-x-5 gap-y-2';
        displayLabels.forEach((label, index) => {
            const item = document.createElement('span');
            item.className = 'flex items-center gap-2 text-theme-sm text-gray-500 dark:text-gray-400';

            const dot = document.createElement('span');
            dot.className = 'h-2 w-2 rounded-full';
            dot.style.backgroundColor = displayColors[index % displayColors.length];

            const text = document.createTextNode(`${label}: ${formatNumber(total > 0 ? values[index] : 0)}`);
            item.appendChild(dot);
            item.appendChild(text);
            legend.appendChild(item);
        });
        wrapper.appendChild(legend);
    }

    function renderAreaChart(element, payload) {
        if (!element) {
            return;
        }

        const data = payload.series?.[0]?.data || [];
        const width = 420;
        const height = 155;
        const maxValue = niceMax(Math.max(0, ...data));
        const points = data.map((value, index) => ({
            x: data.length <= 1 ? width / 2 : (width / (data.length - 1)) * index,
            y: height - ((Number(value) || 0) / maxValue) * (height - 20) - 10,
        }));
        const path = linePath(points);
        const area = points.length ? `${path} L ${width} ${height} L 0 ${height} Z` : '';

        element.replaceChildren();
        const svg = createSvg(width, height, 'h-full w-full');
        element.appendChild(svg);
        appendSvg(svg, 'path', { d: area, fill: '#1a619a', opacity: '0.12' });
        appendSvg(svg, 'path', {
            d: path,
            fill: 'none',
            stroke: '#1a619a',
            'stroke-width': '2',
            'stroke-linecap': 'round',
        });
    }

    ready(function () {
        const config = window.AdminDashboardCharts;

        if (!config) {
            return;
        }

        const analyticsElement = document.querySelector('#adminAnalyticsChart');
        const statusElement = document.querySelector('#adminApplicationStatusChart');
        const activeUsersElement = document.querySelector('#adminActiveUsersChart');
        const activeUsersCount = document.querySelector('.activeUsers');
        const jobsTotal = document.getElementById('adminAnalyticsJobsTotal');
        const applicantsTotal = document.getElementById('adminAnalyticsApplicantsTotal');
        const emptyState = document.getElementById('adminAnalyticsEmptyState');
        const initialAnalytics = config.analytics || { categories: [], series: [], totals: {}, empty: true };

        function updateAnalyticsSummary(payload) {
            if (jobsTotal) {
                jobsTotal.textContent = formatNumber(payload.totals?.jobs);
            }

            if (applicantsTotal) {
                applicantsTotal.textContent = formatNumber(payload.totals?.applicants);
            }

            if (emptyState) {
                emptyState.classList.toggle('hidden', !payload.empty);
            }
        }

        async function loadAnalytics(period) {
            showChartMessage(analyticsElement, 'Loading analytics...');
            showChartMessage(statusElement, 'Loading application status...');

            try {
                const response = await fetch(`${config.analyticsUrl}?period=${encodeURIComponent(period)}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Analytics request failed with status ${response.status}`);
                }

                const payload = await response.json();
                renderLineChart(analyticsElement, payload);
                renderDonutChart(statusElement, payload.applicationStatus);
                updateAnalyticsSummary(payload);
            } catch (error) {
                showChartMessage(analyticsElement, 'Unable to load analytics. Please try again.', 'error');
                showChartMessage(statusElement, 'Unable to load application status.', 'error');
            }
        }

        async function loadActiveUsers() {
            try {
                const response = await fetch(config.activeUsersUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`Active users request failed with status ${response.status}`);
                }

                const payload = await response.json();

                if (activeUsersCount) {
                    activeUsersCount.textContent = formatNumber(payload.liveVisitors);
                }

                renderAreaChart(activeUsersElement, payload.trend || {});
            } catch (error) {
                showChartMessage(activeUsersElement, 'Unable to refresh live visitors.', 'error');
            }
        }

        renderLineChart(analyticsElement, initialAnalytics);
        renderDonutChart(statusElement, initialAnalytics.applicationStatus || config.applicationStatus);
        if (activeUsersElement) {
            renderAreaChart(activeUsersElement, config.activeUsersTrend || {});
        }
        updateAnalyticsSummary(initialAnalytics);

        document.querySelectorAll('[data-analytics-period]').forEach((button) => {
            button.addEventListener('click', function () {
                loadAnalytics(this.dataset.analyticsPeriod);
            });
        });

        if (config.activeUsersUrl) {
            loadActiveUsers();
            window.setInterval(loadActiveUsers, 30000);
        }
    });
})();
