import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { TextStyleKit } from '@tiptap/extension-text-style';
import TextAlign from '@tiptap/extension-text-align';
import Image from '@tiptap/extension-image';
import Highlight from '@tiptap/extension-highlight';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { TableKit } from '@tiptap/extension-table';

const JobImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: { default: '100%', parseHTML: element => element.getAttribute('width') || '100%' },
            align: {
                default: 'left',
                parseHTML: element => element.getAttribute('data-align') || 'left',
                renderHTML: attributes => ({ 'data-align': attributes.align }),
            },
        };
    },
});

export function safeEditorUrl(value, image = false) {
    const url = value.trim();
    if (/[\s\u0000-\u001f\u007f]/u.test(url)) return null;

    try {
        const parsed = new URL(url);
        const schemes = image ? ['https:'] : ['https:', 'http:', 'mailto:'];
        if (!schemes.includes(parsed.protocol) || parsed.username || parsed.password) return null;
        return parsed.href;
    } catch {
        return null;
    }
}

export function hasDescriptionText(value) {
    return value.replace(/[\s\p{Z}\u200b-\u200d\ufeff]/gu, '').length > 0;
}

export function initializeJobDialog(dialog) {
    if (!dialog || dialog.jobDialogCleanup) return;
    let isOpen = false;
    let previousFocus = null;
    let previousOverflow = '';
    const focusable = () => [...dialog.querySelectorAll('button, input, select, textarea, a[href], [contenteditable="true"], [tabindex]')]
        .filter(element => !element.disabled && element.tabIndex >= 0 && !element.closest('[hidden]') && getComputedStyle(element).display !== 'none');
    const focusFirst = () => focusable()[0]?.focus();
    const sync = () => {
        const open = !dialog.hasAttribute('x-cloak') && getComputedStyle(dialog).display !== 'none';
        if (open === isOpen) return;
        isOpen = open;
        if (open) {
            previousFocus = document.activeElement;
            previousOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            focusFirst();
        } else {
            document.body.style.overflow = previousOverflow;
            if (previousFocus?.isConnected) previousFocus.focus();
        }
    };
    const onKeydown = event => {
        if (!isOpen || event.key !== 'Tab') return;
        const elements = focusable();
        const first = elements[0];
        const last = elements.at(-1);
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    };
    const onFocus = event => { if (isOpen && !dialog.contains(event.target)) focusFirst(); };
    const observer = new MutationObserver(sync);
    observer.observe(dialog, { attributes: true, attributeFilter: ['style', 'x-cloak'] });
    dialog.addEventListener('keydown', onKeydown);
    document.addEventListener('focusin', onFocus);
    dialog.jobDialogCleanup = () => {
        observer.disconnect();
        dialog.removeEventListener('keydown', onKeydown);
        document.removeEventListener('focusin', onFocus);
        if (isOpen) document.body.style.overflow = previousOverflow;
        delete dialog.jobDialogCleanup;
    };
    sync();
}

export function initializeJobDescriptionEditors(root = document) {
    return [...root.querySelectorAll('[data-job-description-editor]')].map(container => {
        if (container.jobEditor) return container.jobEditor;

        const source = container.querySelector('[data-editor-source]');
        const surface = container.querySelector('[data-editor-surface]');
        const ui = container.querySelector('[data-editor-ui]');
        const error = container.querySelector('[data-field-error="description"]');
        const panel = container.querySelector('[data-editor-asset-panel]');
        const assetError = container.querySelector('[data-editor-asset-error]');
        const asset = name => container.querySelector(`[data-editor-asset="${name}"]`);
        const label = container.querySelector(`label[for="${source.id}"]`);
        const form = source.form;
        let assetMode = null;

        if (label) label.id ||= `${source.id}_label`;

        const editor = new Editor({
            element: surface,
            extensions: [
                StarterKit.configure({
                    link: { openOnClick: false, autolink: false, HTMLAttributes: { target: null, rel: 'noopener noreferrer nofollow' } },
                }),
                TextStyleKit,
                TextAlign.configure({ types: ['heading', 'paragraph'] }),
                JobImage.configure({ allowBase64: false, HTMLAttributes: { loading: 'lazy', referrerpolicy: 'no-referrer' } }),
                Highlight.configure({ multicolor: true }),
                Subscript,
                Superscript,
                TableKit.configure({ table: { resizable: false } }),
            ],
            content: container.querySelector('[data-editor-initial-content]').innerHTML,
            editorProps: {
                attributes: {
                    id: `${source.id}_editor`,
                    role: 'textbox',
                    'aria-multiline': 'true',
                    'aria-required': 'true',
                    'aria-labelledby': label?.id || '',
                    'aria-describedby': source.getAttribute('aria-describedby') || '',
                    'aria-invalid': source.getAttribute('aria-invalid') || 'false',
                    spellcheck: 'true',
                },
                handlePaste: (_view, event) => {
                    if ([...(event.clipboardData?.files || [])].length) {
                        announce('Use Image to insert an HTTPS image URL.');
                        return true;
                    }
                    return false;
                },
                handleDrop: (_view, event) => {
                    if ([...(event.dataTransfer?.files || [])].length) {
                        announce('Use Image to insert an HTTPS image URL.');
                        return true;
                    }
                    return false;
                },
            },
        });

        function announce(message) {
            error.textContent = message;
            error.classList.remove('hidden');
        }

        function sync() {
            source.value = hasDescriptionText(editor.getText()) ? editor.getHTML() : '';
            const count = editor.getText().trim().split(/\s+/u).filter(Boolean).length;
            container.querySelector('[data-editor-word-count]').textContent = `${count} ${count === 1 ? 'word' : 'words'}`;
        }

        function syncValidity() {
            const invalid = source.getAttribute('aria-invalid') || 'false';
            editor.view.dom.setAttribute('aria-invalid', invalid);
            ui.dataset.invalid = invalid;
        }

        function updateToolbar() {
            container.querySelectorAll('[data-editor-command][aria-pressed]').forEach(button => {
                button.setAttribute('aria-pressed', String(editor.isActive(button.dataset.editorCommand)));
            });
            ['undo', 'redo'].forEach(command => {
                container.querySelector(`[data-editor-command="${command}"]`).disabled = !editor.can()[command]();
            });
            container.querySelector('[data-editor-command="unlink"]').disabled = !editor.isActive('link');
            container.querySelector('[data-editor-command="removeImage"]').disabled = !editor.isActive('image');
            const textStyle = editor.getAttributes('textStyle');
            const values = {
                block: editor.isActive('heading') ? String(editor.getAttributes('heading').level) : 'paragraph',
                fontFamily: (textStyle.fontFamily || '').replace(/["']/g, ''),
                fontSize: textStyle.fontSize || '',
                lineHeight: textStyle.lineHeight || '',
                textAlign: editor.getAttributes(editor.isActive('heading') ? 'heading' : 'paragraph').textAlign || 'left',
            };
            Object.entries(values).forEach(([name, value]) => {
                const select = container.querySelector(`[data-editor-select="${name}"]`);
                if (document.activeElement !== select) select.value = value;
            });
        }

        function openAsset(mode) {
            assetMode = mode;
            const attributes = editor.getAttributes(mode);
            asset('url').value = attributes[mode === 'image' ? 'src' : 'href'] || '';
            asset('url').placeholder = mode === 'image' ? 'https://example.com/image.jpg' : 'https://example.com';
            ['alt', 'title', 'width', 'align'].forEach(name => {
                asset(name).value = attributes[name] || ({ width: '100%', align: 'left' }[name] || '');
            });
            container.querySelectorAll('[data-editor-image-option]').forEach(element => { element.hidden = mode !== 'image'; });
            assetError.textContent = '';
            panel.hidden = false;
            asset('url').focus();
        }

        const toggles = {
            bold: 'toggleBold', italic: 'toggleItalic', underline: 'toggleUnderline', strike: 'toggleStrike',
            subscript: 'toggleSubscript', superscript: 'toggleSuperscript', code: 'toggleCode',
            bulletList: 'toggleBulletList', orderedList: 'toggleOrderedList', blockquote: 'toggleBlockquote', codeBlock: 'toggleCodeBlock',
        };

        container.querySelectorAll('[data-editor-command]').forEach(button => {
            button.addEventListener('mousedown', event => event.preventDefault());
            button.addEventListener('click', () => {
                const command = button.dataset.editorCommand;
                if (command === 'link' || command === 'image') return openAsset(command);
                const chain = editor.chain().focus();
                if (toggles[command]) chain[toggles[command]]().run();
                else if (command === 'unlink') chain.extendMarkRange('link').unsetLink().run();
                else if (command === 'removeImage') chain.deleteSelection().run();
                else if (command === 'horizontalRule') chain.setHorizontalRule().run();
                else if (command === 'clear') chain.unsetAllMarks().clearNodes().unsetTextAlign().run();
                else if (command === 'undo' || command === 'redo') chain[command]().run();
            });
        });

        container.querySelectorAll('[data-editor-select]').forEach(select => {
            select.addEventListener('change', () => {
                const type = select.dataset.editorSelect;
                const value = select.value;
                const chain = editor.chain().focus();
                if (type === 'block') {
                    if (value === 'paragraph') chain.setParagraph().run();
                    else chain.setHeading({ level: Number(value) }).run();
                } else if (type === 'textAlign') chain.setTextAlign(value).run();
                else if (type === 'table') {
                    if (value === 'insertTable') chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
                    else if (value && typeof chain[value] === 'function') chain[value]().run();
                    select.value = '';
                } else {
                    const command = `${value ? 'set' : 'unset'}${type[0].toUpperCase()}${type.slice(1)}`;
                    chain[command](...(value ? [value] : [])).run();
                }
                updateToolbar();
            });
        });

        container.querySelectorAll('[data-editor-color]').forEach(input => {
            input.addEventListener('input', () => {
                const chain = editor.chain().focus();
                if (input.dataset.editorColor === 'highlight') chain.setHighlight({ color: input.value }).run();
                else chain.setColor(input.value).run();
            });
        });

        container.querySelector('[data-editor-asset-apply]').addEventListener('click', () => {
            const url = safeEditorUrl(asset('url').value, assetMode === 'image');
            if (!url) {
                assetError.textContent = assetMode === 'image' ? 'Enter a valid HTTPS image URL.' : 'Enter a valid HTTPS, HTTP, or email link.';
                asset('url').focus();
                return;
            }
            const chain = editor.chain().focus();
            if (assetMode === 'link') chain.extendMarkRange('link').setLink({ href: url }).run();
            else {
                const attributes = { src: url, alt: asset('alt').value, title: asset('title').value, width: asset('width').value, align: asset('align').value };
                if (editor.isActive('image')) chain.updateAttributes('image', attributes).run();
                else chain.setImage(attributes).run();
            }
            panel.hidden = true;
        });
        container.querySelector('[data-editor-asset-cancel]').addEventListener('click', () => { panel.hidden = true; editor.commands.focus(); });
        panel.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); panel.hidden = true; editor.commands.focus(); }
            if (event.key === 'Enter' && event.target.tagName === 'INPUT') { event.preventDefault(); container.querySelector('[data-editor-asset-apply]').click(); }
        });

        editor.on('update', () => {
            sync();
            if (source.value) {
                source.setAttribute('aria-invalid', 'false');
                error.textContent = '';
                error.classList.add('hidden');
            }
        });
        editor.on('transaction', updateToolbar);
        const validityObserver = new MutationObserver(syncValidity);
        validityObserver.observe(source, { attributes: true, attributeFilter: ['aria-invalid'] });
        editor.on('destroy', () => {
            validityObserver.disconnect();
            container.closest('[role="dialog"]')?.jobDialogCleanup?.();
        });
        form?.addEventListener('submit', event => {
            sync();
            panel.hidden = true;
            if (!source.value) {
                event.preventDefault();
                event.stopImmediatePropagation();
                source.setAttribute('aria-invalid', 'true');
                syncValidity();
                announce('The description must contain text about the role.');
                editor.commands.focus();
            }
        }, true);
        form?.addEventListener('formdata', event => { sync(); event.formData.set(source.name, source.value); });
        label?.addEventListener('click', event => { event.preventDefault(); editor.commands.focus(); });

        sync();
        syncValidity();
        updateToolbar();
        source.required = false;
        source.hidden = true;
        ui.hidden = false;
        container.jobEditor = editor;
        initializeJobDialog(container.closest('[role="dialog"]'));
        return editor;
    });
}
