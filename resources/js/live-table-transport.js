export class TableRequestError extends Error {
    constructor(message, status = 0, errors = {}) { super(message); this.status = status; this.errors = errors; }
}

export async function tableJson(response) {
    let body;
    try { body = await response.json(); } catch { body = null; }
    if (response.redirected || !body || typeof body !== 'object') {
        throw new TableRequestError('This page could not be refreshed. Your session may have expired. Please sign in again if needed.', response.status);
    }
    if (!response.ok) {
        const fallback = { 401: 'Your session has expired. Please sign in again.', 403: 'You no longer have permission to perform this action.',
            404: 'This list or record is no longer available.',
            419: 'Your session has expired. Please sign in again before submitting.', 429: 'Too many requests. Please wait a moment and try again.' };
        throw new TableRequestError(fallback[response.status] || (response.status >= 500 ? 'The server could not complete this request. Please try again.' : body.message) || 'The request failed. Please try again.', response.status, body.errors || {});
    }
    return body;
}

/** Aborting is an optimization; the revision check also rejects late responses. */
export function createLatestTableRequest(fetcher = fetch) {
    let controller = null;
    let revision = 0;
    return {
        invalidate() { revision += 1; controller?.abort(); controller = null; },
        async run(url, options = {}) {
            this.invalidate();
            const current = revision;
            const pending = new AbortController();
            controller = pending;
            try {
                const response = await fetcher(url, { ...options, signal: pending.signal, credentials: 'same-origin', cache: 'no-store' });
                const result = await tableJson(response);
                return revision === current ? result : null;
            } catch (error) {
                if (revision !== current || error.name === 'AbortError') return null;
                throw error;
            } finally { if (revision === current) controller = null; }
        },
    };
}

export function tableFilterUrl(action, entries, current, pageNames = ['page']) {
    const url = new URL(action || current, current);
    const previous = new URL(current);
    if (url.origin !== previous.origin) throw new TableRequestError('Table requests must stay on this site.');
    const explicit = new Set(url.searchParams.keys());
    for (const [name, value] of previous.searchParams) if (!explicit.has(name)) url.searchParams.append(name, value);
    const values = [...entries];
    for (const name of new Set(values.map(([name]) => name))) url.searchParams.delete(name);
    for (const [name, value] of values) if (typeof value === 'string' && value !== '') url.searchParams.append(name, value);
    pageNames.forEach((name) => url.searchParams.delete(name));
    url.hash = '';
    return url.href;
}

export function topicsOverlap(left, right) {
    return !left.length || !right.length || left.some((topic) => right.includes(topic));
}
