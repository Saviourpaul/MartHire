import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';
import { createPdfSession } from './candidate-pdf-session.js';

GlobalWorkerOptions.workerSrc = workerUrl;

export async function createPdfPreview(buffer) {
    const data = buffer instanceof ArrayBuffer ? new Uint8Array(buffer)
        : ArrayBuffer.isView(buffer) ? new Uint8Array(buffer.buffer, buffer.byteOffset, buffer.byteLength) : null;
    if (!data?.byteLength) throw new Error('This PDF is empty or cannot be read.');
    const base = new URL(__CANDIDATE_PDF_ASSET_PATH__, globalThis.location.href).href;
    const task = getDocument({
        data, cMapUrl: `${base}cmaps/`, cMapPacked: true,
        standardFontDataUrl: `${base}standard_fonts/`, wasmUrl: `${base}wasm/`, iccUrl: `${base}iccs/`,
        useSystemFonts: true, isEvalSupported: false, enableXfa: false,
        canvasMaxAreaInBytes: 64 * 1024 * 1024,
        disableAutoFetch: true, disableStream: true, disableRange: true,
    });
    try {
        return createPdfSession(await task.promise);
    } catch (error) {
        await task.destroy().catch(() => {});
        if (error.name === 'PasswordException') throw new Error('This PDF is password protected. Download it to review with your PDF application.');
        throw new Error('This PDF could not be read. Download the document to review it.');
    }
}
