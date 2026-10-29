// Quill rich-text editor for the native job description field.
// The enabled formats are deliberately limited to those that round-trip through
// the server-side App\Support\JobDescription sanitizer (no alignment/indent/
// code-block, which Quill serializes as ql-* classes / <div> the sanitizer strips).
// The editor syncs its HTML into the fallback <textarea name="description"> so the
// form works with or without JS; the server always re-sanitizes on save.
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const FORMATS = ['header', 'bold', 'italic', 'underline', 'strike', 'list', 'blockquote', 'code', 'link'];

const TOOLBAR = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['blockquote', 'code'],
    ['link'],
    ['clean'],
];

export function registerJobForm(Alpine) {
    Alpine.data('jobForm', ({ maxLength = 100000, storageKey = 'job-form' } = {}) => {
        // Quill/Parchment relies on object identity. Keep its instance outside
        // Alpine's deeply reactive state, including calls made after init.
        let quill = null;
        let onTextChange = null;
        let onBeforeUnload = null;

        return {
            count: 0,
            submitting: false,
            dirty: false,

            init() {
                const input = this.$refs.input;
                const shell = this.$refs.editorShell;

                try {
                    quill = new Quill(shell.querySelector('[data-job-editor]'), {
                        theme: 'snow',
                        placeholder: 'Describe the role, responsibilities, and requirements…',
                        formats: FORMATS,
                        modules: { toolbar: TOOLBAR },
                    });

                    // Prefer an autosaved draft only when the field has no server/old content.
                    let initial = input.value;
                    try {
                        const saved = window.localStorage.getItem(storageKey);
                        if (saved && saved.trim() && (!initial || !initial.trim())) {
                            initial = saved;
                        }
                    } catch (_) {}
                    if (initial && initial.trim()) {
                        quill.clipboard.dangerouslyPasteHTML(initial);
                    }

                    this.sync();

                    onTextChange = () => {
                        if (quill.getLength() > maxLength) {
                            quill.deleteText(maxLength, quill.getLength());
                        }
                        this.dirty = true;
                        this.sync();
                    };
                    quill.on('text-change', onTextChange);

                    onBeforeUnload = (event) => {
                        if (this.dirty && !this.submitting) {
                            event.preventDefault();
                            event.returnValue = '';
                        }
                    };
                    window.addEventListener('beforeunload', onBeforeUnload);
                    // Hide the fallback only once content loading and syncing succeed.
                    input.style.display = 'none';
                } catch (error) {
                    this.destroy();
                    shell.hidden = true;
                    input.style.display = '';
                    console.error('Unable to initialize the job description editor.', error);
                }
            },

            sync() {
                const html = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
                this.$refs.input.value = html;
                this.count = Math.max(0, quill.getLength() - 1);
                try {
                    window.localStorage.setItem(storageKey, html);
                } catch (_) {}
            },

            allowSubmit() {
                this.submitting = true;
                try {
                    window.localStorage.removeItem(storageKey);
                } catch (_) {}
            },

            destroy() {
                if (quill && onTextChange) quill.off('text-change', onTextChange);
                if (onBeforeUnload) window.removeEventListener('beforeunload', onBeforeUnload);
                quill = null;
            },
        };
    });
}
