import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = workerUrl;

export async function extractResumeText(file) {
    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension === 'pdf') return extractPdf(file);
    if (extension === 'txt') return file.text();
    if (extension === 'rtf') return extractRtf(await file.text());
    if (extension === 'docx') return extractDocx(file);
    if (extension === 'doc') {
        throw new Error('Older DOC files can still be uploaded, but automatic text fill supports PDF, DOCX, TXT, and RTF.');
    }

    throw new Error('Choose a PDF, DOC, DOCX, TXT, or RTF résumé.');
}

async function extractPdf(file) {
    const task = getDocument({ data: new Uint8Array(await file.arrayBuffer()), isEvalSupported: false, useSystemFonts: true });
    let document;
    try {
        document = await task.promise;
        const pages = [];
        for (let pageNumber = 1; pageNumber <= Math.min(document.numPages, 30); pageNumber += 1) {
            const page = await document.getPage(pageNumber);
            const content = await page.getTextContent();
            pages.push(content.items.filter((item) => typeof item.str === 'string')
                .map((item) => item.str + (item.hasEOL ? '\n' : ' ')).join(' '));
        }
        const text = pages.join('\n').trim();
        if (!text) throw new Error('This PDF contains no selectable text. You can still upload it and fill the profile manually.');
        return text;
    } finally {
        await (document?.destroy() ?? task.destroy()).catch(() => {});
    }
}

async function extractRtf(value) {
    return value
        .replace(/\\'[0-9a-f]{2}/gi, (match) => String.fromCharCode(parseInt(match.slice(2), 16)))
        .replace(/\\par[d]?\b/g, '\n')
        .replace(/\\line\b/g, '\n')
        .replace(/\\tab\b/g, '\t')
        .replace(/\\[a-z]+-?\d* ?/gi, '')
        .replace(/\\([{}\\])/g, '$1')
        .replace(/[{}]/g, '')
        .trim();
}

async function extractDocx(file) {
    const bytes = new Uint8Array(await file.arrayBuffer());
    const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
    const decoder = new TextDecoder('utf-8');
    let endRecord = -1;
    for (let offset = Math.max(0, bytes.length - 65_557); offset <= bytes.length - 22; offset += 1) {
        if (view.getUint32(offset, true) === 0x06054b50) endRecord = offset;
    }
    if (endRecord < 0) throw new Error('This DOCX file could not be read. You can still upload it and fill the profile manually.');

    const entries = view.getUint16(endRecord + 10, true);
    let cursor = view.getUint32(endRecord + 16, true);
    let documentEntry = null;
    for (let index = 0; index < entries; index += 1) {
        if (view.getUint32(cursor, true) !== 0x02014b50) break;
        const method = view.getUint16(cursor + 10, true);
        const compressedSize = view.getUint32(cursor + 20, true);
        const nameLength = view.getUint16(cursor + 28, true);
        const extraLength = view.getUint16(cursor + 30, true);
        const commentLength = view.getUint16(cursor + 32, true);
        const localOffset = view.getUint32(cursor + 42, true);
        const name = decoder.decode(bytes.subarray(cursor + 46, cursor + 46 + nameLength));
        if (name === 'word/document.xml') documentEntry = { method, compressedSize, localOffset, nameLength };
        cursor += 46 + nameLength + extraLength + commentLength;
    }
    if (!documentEntry || view.getUint32(documentEntry.localOffset, true) !== 0x04034b50) {
        throw new Error('This DOCX file does not contain readable document text. You can still upload it and fill the profile manually.');
    }

    const localNameLength = view.getUint16(documentEntry.localOffset + 26, true);
    const localExtraLength = view.getUint16(documentEntry.localOffset + 28, true);
    const dataStart = documentEntry.localOffset + 30 + localNameLength + localExtraLength;
    const compressed = bytes.slice(dataStart, dataStart + documentEntry.compressedSize);
    let xmlBytes = compressed;
    if (documentEntry.method === 8) {
        if (!('DecompressionStream' in globalThis)) throw new Error('DOCX text extraction is not supported in this browser. You can still upload it.');
        const stream = new Blob([compressed]).stream().pipeThrough(new DecompressionStream('deflate-raw'));
        xmlBytes = new Uint8Array(await new Response(stream).arrayBuffer());
    } else if (documentEntry.method !== 0) {
        throw new Error('This DOCX compression format cannot be read in this browser. You can still upload it.');
    }

    const xml = decoder.decode(xmlBytes).replace(/<w:tab\b[^>]*\/>/g, ' ').replace(/<w:br\b[^>]*\/>/g, '\n').replace(/<\/w:p>/g, '\n');
    const parsed = new DOMParser().parseFromString(xml, 'application/xml');
    if (parsed.querySelector('parsererror')) throw new Error('This DOCX file could not be read. You can still upload it and fill the profile manually.');
    const wordNamespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    const paragraphs = [...parsed.getElementsByTagNameNS(wordNamespace, 'p')]
        .map((paragraph) => paragraph.textContent?.trim() ?? '').filter(Boolean);
    const text = paragraphs.join('\n');
    if (!text) throw new Error('This DOCX contains no readable text. You can still upload it and fill the profile manually.');
    return text;
}
