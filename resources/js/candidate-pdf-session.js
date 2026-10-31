const MAX_CANVAS_PIXELS = 16 * 1024 * 1024;
const MAX_CANVAS_SIDE = 16384;
const cancelled = () => Object.assign(new Error('PDF rendering was cancelled.'), { name: 'AbortError' });

// Keep PDF.js objects out of Alpine's reactive proxy. A serialized queue also
// prevents cancelled page renders from drawing into a newly resized canvas.
export function createPdfSession(pdf) {
    let revision = 0;
    let destroyed = false;
    let activeTask = null;
    let queue = Promise.resolve();
    let destruction = null;

    const assertCurrent = (request) => { if (destroyed || request !== revision) throw cancelled(); };

    return {
        numPages: pdf.numPages,
        render(canvas, container, pageNumber, { zoom = 1 } = {}) {
            if (destroyed) return Promise.reject(cancelled());
            if (!Number.isInteger(pageNumber) || pageNumber < 1 || pageNumber > pdf.numPages) {
                return Promise.reject(new Error('Choose a valid document page.'));
            }
            const request = ++revision;
            activeTask?.cancel();
            const rendering = queue.then(async () => {
                assertCurrent(request);
                try {
                    const page = await pdf.getPage(pageNumber);
                    assertCurrent(request);
                    const original = page.getViewport({ scale: 1 });
                    if (![original.width, original.height].every((value) => Number.isFinite(value) && value > 0)) throw new Error('Invalid PDF page dimensions.');
                    const measuredWidth = container?.clientWidth || container?.getBoundingClientRect?.().width || 640;
                    const width = Number.isFinite(measuredWidth) && measuredWidth > 0 ? measuredWidth : 640;
                    const boundedZoom = Number.isFinite(zoom) ? Math.max(0.5, Math.min(3, zoom)) : 1;
                    const viewport = page.getViewport({ scale: width / original.width * boundedZoom });
                    if (![viewport.width, viewport.height].every((value) => Number.isFinite(value) && value > 0)) throw new Error('Invalid PDF page dimensions.');
                    const pixelRatio = Math.min(2, Math.max(1, globalThis.devicePixelRatio || 1),
                        Math.sqrt(MAX_CANVAS_PIXELS / (viewport.width * viewport.height)),
                        MAX_CANVAS_SIDE / viewport.width, MAX_CANVAS_SIDE / viewport.height);
                    canvas.width = Math.max(1, Math.floor(viewport.width * pixelRatio));
                    canvas.height = Math.max(1, Math.floor(viewport.height * pixelRatio));
                    canvas.style.width = `${viewport.width}px`;
                    canvas.style.height = `${viewport.height}px`;
                    canvas.setAttribute?.('role', 'img');
                    canvas.setAttribute?.('aria-label', `Document page ${pageNumber} of ${pdf.numPages}`);
                    const context = canvas.getContext('2d');
                    if (!context) throw new Error('Canvas rendering is unavailable.');
                    const task = page.render({ canvas, canvasContext: context, viewport,
                        transform: pixelRatio === 1 ? null : [pixelRatio, 0, 0, pixelRatio, 0, 0],
                        background: '#ffffff' });
                    activeTask = task;
                    try { await task.promise; } finally { if (activeTask === task) activeTask = null; }
                    assertCurrent(request);
                    let pageText = '';
                    try {
                        const text = await page.getTextContent();
                        pageText = text.items.filter((item) => typeof item.str === 'string')
                            .map((item) => item.str + (item.hasEOL ? '\n' : ' ')).join('').trim();
                    } catch (_) { /* A scanned/image-only page can still be reviewed visually. */ }
                    assertCurrent(request);
                    return { pageText, width: viewport.width, height: viewport.height };
                } catch (error) {
                    if (destroyed || request !== revision || ['AbortError', 'RenderingCancelledException'].includes(error.name)) throw cancelled();
                    throw new Error('This PDF page could not be displayed. Download the document to review it.');
                }
            });
            queue = rendering.catch(() => {});
            return rendering;
        },
        destroy() {
            if (destruction) return destruction;
            destroyed = true;
            revision += 1;
            activeTask?.cancel();
            // Destroying the transport also releases pending getPage/text work.
            destruction = Promise.allSettled([queue, Promise.resolve().then(() => pdf.destroy())]).then(() => {});
            return destruction;
        },
    };
}
