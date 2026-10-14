import { test, afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { JSDOM } from 'jsdom';
import { initializeJobDescriptionEditors, initializeJobDialog, safeEditorUrl, hasDescriptionText } from '../resources/js/job-description-editor.js';

let dom;
let editors = [];

function mount(contents = ['<p>Build APIs.</p>']) {
    dom = new JSDOM('<!doctype html><body></body>', { url: 'https://marthire.test', pretendToBeVisual: true });
    for (const name of ['window', 'document', 'navigator', 'Node', 'HTMLElement', 'Element', 'DOMParser', 'MutationObserver', 'getComputedStyle', 'requestAnimationFrame', 'cancelAnimationFrame']) {
        Object.defineProperty(globalThis, name, { configurable: true, value: typeof dom.window[name] === 'function' && ['getComputedStyle', 'requestAnimationFrame', 'cancelAnimationFrame'].includes(name) ? dom.window[name].bind(dom.window) : dom.window[name] });
    }
    dom.window.Range.prototype.getClientRects = () => [];
    dom.window.Range.prototype.getBoundingClientRect = () => ({ top: 0, bottom: 0, left: 0, right: 0 });
    dom.window.HTMLElement.prototype.getClientRects = () => [];
    dom.window.HTMLElement.prototype.scrollIntoView = () => {};
    dom.window.scrollBy = () => {};

    document.body.innerHTML = contents.map((content, index) => `<form><div data-job-description-editor>
        <label for="description_${index}">Job Description</label><textarea name="description" id="description_${index}" required aria-invalid="false" aria-describedby="hint_${index} error_${index}" data-editor-source></textarea>
        <template data-editor-initial-content>${content}</template><div data-editor-ui hidden>
        ${['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'code', 'bulletList', 'orderedList', 'blockquote', 'codeBlock', 'undo', 'redo', 'unlink', 'removeImage', 'link', 'image', 'clear', 'horizontalRule'].map(command => `<button type="button" data-editor-command="${command}" ${['bold', 'italic', 'underline', 'strike'].includes(command) ? 'aria-pressed="false"' : ''}>${command}</button>`).join('')}
        ${Object.entries({ block: ['paragraph', '1', '2', '3', '4', '5', '6'], fontFamily: ['', 'Arial', 'Georgia'], fontSize: ['', '24px'], lineHeight: ['', '1.5'], textAlign: ['left', 'center', 'right', 'justify'], table: ['', 'insertTable', 'addRowAfter', 'deleteTable'] }).map(([name, values]) => `<select data-editor-select="${name}">${values.map(value => `<option value="${value}">${value}</option>`).join('')}</select>`).join('')}
        <input type="color" data-editor-color="color"><input type="color" data-editor-color="highlight">
        <div data-editor-asset-panel hidden>${['url', 'alt', 'title'].map(name => `<label data-editor-image-option><input data-editor-asset="${name}"></label>`).join('')}<select data-editor-asset="width"><option>100%</option><option>50%</option></select><select data-editor-asset="align"><option>left</option><option>center</option></select><p data-editor-asset-error></p><button type="button" data-editor-asset-apply>Apply</button><button type="button" data-editor-asset-cancel>Cancel</button></div>
        <div data-editor-surface></div><span data-editor-word-count></span></div><p id="error_${index}" data-field-error="description" class="hidden"></p>
        </div><button type="submit">Save</button></form>`).join('');
    editors = initializeJobDescriptionEditors();
    return document.querySelector('[data-job-description-editor]');
}

afterEach(() => { editors.forEach(editor => editor.destroy()); editors = []; dom?.window.close(); });

test('accepts safe link and image URLs and rejects executable or mixed content URLs', () => {
    assert.equal(safeEditorUrl('https://example.com/image.jpg', true), 'https://example.com/image.jpg');
    assert.equal(safeEditorUrl('mailto:jobs@example.com'), 'mailto:jobs@example.com');
    for (const value of ['javascript:alert(1)', 'data:image/png;base64,abc', '//example.com/a.png', 'http://example.com/a.png', 'https://user:pass@example.com/a.png', 'java\nscript:alert(1)']) assert.equal(safeEditorUrl(value, true), null);
    assert.equal(hasDescriptionText('\u00a0\u200b \n'), false);
});

test('initializes Create and Edit once and synchronizes each description independently', () => {
    mount(['<p>New role</p>', '<h2>Existing role</h2><p>Maintain APIs.</p>']);
    assert.equal(initializeJobDescriptionEditors()[0], editors[0]);
    editors[0].commands.setContent('<p><strong>Updated role</strong></p>');
    assert.match(new dom.window.FormData(document.forms[0]).get('description'), /<strong>Updated role<\/strong>/);
    assert.match(new dom.window.FormData(document.forms[1]).get('description'), /<h2>Existing role<\/h2>/);
    assert.equal(document.querySelector('[data-editor-source]').required, false);
    assert.equal(document.querySelector('[data-editor-source]').hidden, true);
    assert.equal(document.querySelector('[data-editor-ui]').hidden, false);
});

test('toolbar formats headings, text, colors, alignment, lists and tables', () => {
    const container = mount();
    const select = (type, value) => { const element = container.querySelector(`[data-editor-select="${type}"]`); element.value = value; element.dispatchEvent(new dom.window.Event('change')); };
    editors[0].commands.selectAll();
    container.querySelector('[data-editor-command="bold"]').click();
    container.querySelector('[data-editor-command="underline"]').click();
    select('block', '2');
    select('fontFamily', 'Georgia');
    select('fontSize', '24px');
    select('textAlign', 'center');
    const color = container.querySelector('[data-editor-color="color"]'); color.value = '#123456'; color.dispatchEvent(new dom.window.Event('input'));
    const html = editors[0].getHTML();
    assert.match(html, /<h2[^>]*text-align: center/);
    assert.match(html, /<strong>/);
    assert.match(html, /<u>/);
    assert.match(html, /Georgia/);
    assert.match(html, /24px/);
    assert.match(html, /rgb\(18, 52, 86\)/);
    container.querySelector('[data-editor-command="clear"]').click();
    container.querySelector('[data-editor-command="bulletList"]').click();
    assert.match(editors[0].getHTML(), /<ul>/);
    editors[0].commands.setContent('<p>Table follows</p>');
    select('table', 'insertTable');
    assert.match(editors[0].getHTML(), /<table/);
    select('table', 'deleteTable');
    assert.doesNotMatch(editors[0].getHTML(), /<table/);
});

test('inserts and updates image URL, alt text, title, width and alignment', () => {
    const container = mount();
    const set = (name, value) => { container.querySelector(`[data-editor-asset="${name}"]`).value = value; };
    container.querySelector('[data-editor-command="image"]').click();
    set('url', 'javascript:alert(1)');
    container.querySelector('[data-editor-asset-apply]').click();
    assert.match(container.querySelector('[data-editor-asset-error]').textContent, /HTTPS/);
    assert.doesNotMatch(editors[0].getHTML(), /<img/);
    set('url', 'https://example.com/team.jpg'); set('alt', 'Our team'); set('title', 'Team photo'); set('width', '50%'); set('align', 'center');
    container.querySelector('[data-editor-asset-apply]').click();
    assert.match(editors[0].getHTML(), /alt="Our team"/);
    assert.match(editors[0].getHTML(), /width="50%"/);
    assert.match(editors[0].getHTML(), /data-align="center"/);
    let imagePosition;
    editors[0].state.doc.descendants((node, position) => { if (node.type.name === 'image') imagePosition = position; });
    editors[0].commands.setNodeSelection(imagePosition);
    container.querySelector('[data-editor-command="image"]').click();
    set('alt', 'Updated team'); container.querySelector('[data-editor-asset-apply]').click();
    assert.equal((editors[0].getHTML().match(/<img/g) || []).length, 1);
    assert.match(editors[0].getHTML(), /alt="Updated team"/);
});

test('blocks image-only descriptions and mirrors Laravel validation feedback', async () => {
    const container = mount(['<p><br></p>']);
    let submitted = false;
    document.forms[0].addEventListener('submit', () => { submitted = true; });
    const submit = new dom.window.Event('submit', { bubbles: true, cancelable: true });
    document.forms[0].dispatchEvent(submit);
    assert.equal(submit.defaultPrevented, true);
    assert.equal(submitted, false);
    assert.equal(editors[0].view.dom.getAttribute('aria-invalid'), 'true');
    editors[0].commands.setContent('<p>Meaningful role text.</p>');
    await Promise.resolve();
    assert.equal(editors[0].view.dom.getAttribute('aria-invalid'), 'false');
    container.querySelector('[data-editor-source]').setAttribute('aria-invalid', 'true');
    await Promise.resolve();
    assert.equal(editors[0].view.dom.getAttribute('aria-invalid'), 'true');
});

test('keeps keyboard focus inside an open job dialog and restores focus when closed', async () => {
    mount();
    document.body.insertAdjacentHTML('beforeend', '<button id="open-dialog">Create Job</button><div role="dialog" style="display:none"><button id="first">Close</button><input><button id="last">Save</button></div>');
    const dialog = document.querySelector('[role="dialog"]');
    const trigger = document.querySelector('#open-dialog');
    trigger.focus();
    initializeJobDialog(dialog);
    dialog.style.display = 'block';
    await Promise.resolve();
    assert.equal(document.activeElement.id, 'first');
    document.querySelector('#last').focus();
    dialog.dispatchEvent(new dom.window.KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }));
    assert.equal(document.activeElement.id, 'first');
    dialog.dispatchEvent(new dom.window.KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true, cancelable: true }));
    assert.equal(document.activeElement.id, 'last');
    dialog.style.display = 'none';
    await Promise.resolve();
    assert.equal(document.activeElement, trigger);
    assert.equal(document.body.style.overflow, '');
    dialog.jobDialogCleanup();
});
