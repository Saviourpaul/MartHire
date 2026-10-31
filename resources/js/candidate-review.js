const tabs = ['overview', 'interviews', 'activity'];
const previewTypes = new Set(['application/pdf', 'image/jpeg', 'image/png']);

export function createCandidateReview({ documents = [], tab = 'overview' } = {}, { loadPdf = async (buffer) => (await import('./candidate-pdf.js')).createPdfPreview(buffer) } = {}) {
    // Request/controller state stays outside Alpine's reactive proxy.
    let pending = null;
    let revision = 0;
    let destroyed = false;
    let pdf = null;
    let pdfRevision = 0;
    let pdfDisposal = Promise.resolve();

    return {
        reviewTab: tabs.includes(tab) ? tab : 'overview',
        reviewDocuments: documents,
        documentId: documents[0]?.id || '',
        documentState: 'empty',
        documentError: '',
        documentUrl: '',
        documentMime: '',
        pdfPage: 1,
        pdfPages: 0,
        pdfZoom: 1,
        pdfText: '',
        pdfBusy: false,
        get selectedDocument() { return this.reviewDocuments.find((document) => String(document.id) === String(this.documentId)); },
        init() {
            const restored = this.$el?.dataset;
            if (tabs.includes(restored?.restoreReviewTab)) this.reviewTab = restored.restoreReviewTab;
            if (this.reviewDocuments.some((document) => String(document.id) === restored?.restoreDocument)) this.documentId = restored.restoreDocument;
            if (this.documentId) return this.loadDocument();
        },
        destroy() {
            destroyed = true;
            revision += 1;
            pending?.abort();
            this.releaseDocument();
        },
        selectReviewTab(tab, focus = false) {
            if (!tabs.includes(tab)) return;
            this.reviewTab = tab;
            if (focus) this.$nextTick(() => this.$refs[`reviewTab${tab}`]?.focus());
        },
        moveReviewTab(direction) {
            this.selectReviewTab(tabs[(tabs.indexOf(this.reviewTab) + direction + tabs.length) % tabs.length], true);
        },
        openSchedule() {
            this.selectReviewTab('interviews');
            this.$nextTick(() => {
                const form = this.$refs.scheduleInterview;
                if (form) { form.open = true; form.querySelector('summary')?.focus(); }
            });
        },
        selectDocument(id) {
            if (String(id) === String(this.documentId)) return;
            this.documentId = String(id);
            return this.loadDocument();
        },
        releaseDocument() {
            pdfRevision += 1;
            if (pdf) pdfDisposal = Promise.allSettled([pdfDisposal, pdf.destroy()]).then(() => {});
            pdf = null;
            if (this.documentUrl) URL.revokeObjectURL(this.documentUrl);
            this.documentUrl = '';
            this.documentMime = '';
            this.pdfPage = 1;
            this.pdfPages = 0;
            this.pdfZoom = 1;
            this.pdfText = '';
            this.pdfBusy = false;
        },
        async loadDocument() {
            const request = ++revision;
            pending?.abort();
            pending = null;
            this.releaseDocument();
            this.documentError = '';
            const document = this.selectedDocument;
            if (!document) { this.documentState = 'empty'; return; }
            if (!document.available) { this.documentState = 'unavailable'; return; }
            if (!document.previewable) { this.documentState = 'unsupported'; return; }

            this.documentState = 'loading';
            const controller = new AbortController();
            pending = controller;
            try {
                const response = await fetch(document.preview_url, {
                    signal: controller.signal, credentials: 'same-origin', cache: 'no-store',
                    headers: { Accept: 'application/pdf, image/jpeg, image/png', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (destroyed || request !== revision) return;
                if (response.redirected) throw new Error('Your session has expired. Sign in again to view this document.');
                if (!response.ok) {
                    const messages = {
                        401: 'Your session has expired. Sign in again to view this document.',
                        403: 'You do not have permission to preview this document.',
                        404: 'This document is no longer available.',
                        415: 'This file cannot be previewed. Use the download option to review it.',
                        429: 'Too many preview requests. Wait a moment and try again.',
                    };
                    throw new Error(messages[response.status] || 'The document could not be loaded. Please try again.');
                }
                const mime = (response.headers.get('Content-Type') || '').split(';')[0].trim().toLowerCase();
                if (!previewTypes.has(mime)) throw new Error('This file cannot be displayed safely. Use the download option to review it.');
                const blob = await response.blob();
                if (destroyed || request !== revision) return;
                if (!blob.size) throw new Error('This document is empty. Please contact the candidate for a replacement.');
                this.documentMime = mime;
                if (mime === 'application/pdf') {
                    let session;
                    try { session = await loadPdf(await blob.arrayBuffer()); }
                    catch { throw new Error('This PDF could not be opened. It may be password-protected or damaged. Use Download to review it.'); }
                    if (destroyed || request !== revision) { await session.destroy(); return; }
                    pdf = session;
                    this.pdfPages = session.numPages;
                    this.documentState = 'ready';
                    await this.renderPdfPage();
                } else {
                    this.documentUrl = URL.createObjectURL(blob);
                    this.documentState = 'ready';
                }
            } catch (error) {
                if (destroyed || request !== revision || error.name === 'AbortError') return;
                this.documentState = 'error';
                this.documentError = error instanceof TypeError ? 'The document could not be loaded. Check your connection and try again.' : error.message;
            } finally {
                if (request === revision) pending = null;
            }
        },
        documentRenderFailed() {
            this.releaseDocument();
            this.documentState = 'error';
            this.documentError = 'Your browser could not display this document. Use the download option to review it.';
        },
        async renderPdfPage() {
            if (!pdf || destroyed) return;
            const session = pdf;
            const render = ++pdfRevision;
            this.pdfBusy = true;
            try {
                await this.$nextTick();
                await pdfDisposal;
                if (session !== pdf || destroyed || render !== pdfRevision) return;
                const result = await session.render(this.$refs.pdfCanvas, this.$refs.pdfContainer, this.pdfPage, { zoom: this.pdfZoom });
                if (session !== pdf || destroyed || render !== pdfRevision) return;
                this.pdfText = result.pageText;
            } catch {
                if (session !== pdf || destroyed || render !== pdfRevision) return;
                this.documentState = 'error';
                this.documentError = 'This PDF page could not be displayed. Try again or use Download to review it.';
            } finally {
                if (render === pdfRevision) this.pdfBusy = false;
            }
        },
        changePdfPage(direction) {
            const next = this.pdfPage + direction;
            if (this.pdfBusy || next < 1 || next > this.pdfPages) return;
            this.pdfPage = next;
            return this.renderPdfPage();
        },
        zoomPdf(amount) {
            if (this.pdfBusy) return;
            this.pdfZoom = amount === 0 ? 1 : Math.max(.5, Math.min(2, this.pdfZoom + amount));
            return this.renderPdfPage();
        },
    };
}

export function registerCandidateReview(Alpine) {
    Alpine.data('candidateReview', (options) => createCandidateReview(options));
}
