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
    Alpine.data('jobForm', ({ maxLength = 100000, storageKey = 'job-form' } = {}) => ({
        count: 0,
        quill: null,
        submitting: false,
        dirty: false,

        init() {
            const input = this.$refs.input;

            this.quill = new Quill(this.$refs.editor, {
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
                this.quill.clipboard.dangerouslyPasteHTML(initial);
            }

            input.style.display = 'none';
            this.sync();

            this.quill.on('text-change', () => {
                if (this.quill.getLength() > maxLength) {
                    this.quill.deleteText(maxLength, this.quill.getLength());
                }
                this.dirty = true;
                this.sync();
            });

            this.onBeforeUnload = (event) => {
                if (this.dirty && !this.submitting) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            };
            window.addEventListener('beforeunload', this.onBeforeUnload);
        },

        sync() {
            const html = this.quill.getText().trim() === '' ? '' : this.quill.root.innerHTML;
            this.$refs.input.value = html;
            this.count = Math.max(0, this.quill.getLength() - 1);
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
    }));
}
