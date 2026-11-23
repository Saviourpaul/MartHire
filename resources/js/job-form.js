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
    Alpine.data('jobForm', ({ maxLength = 100000, storageKey = 'job-form', screeningQuestions = [], requiresIdentityDocument = false } = {}) => {
        // Quill/Parchment relies on object identity. Keep its instance outside
        // Alpine's deeply reactive state, including calls made after init.
        let quill = null;
        let onTextChange = null;
        let onBeforeUnload = null;

        return {
            count: 0,
            submitting: false,
            dirty: false,
            requiresIdentityDocument: Boolean(requiresIdentityDocument),
            screeningQuestions: Array.isArray(screeningQuestions) ? screeningQuestions.map((question, index) => ({
                id: question.id || null,
                localKey: `existing-${question.id || index}`,
                prompt: question.prompt || '',
                type: question.type || 'short_text',
                required: Boolean(question.required),
                options: Array.isArray(question.options) ? [...question.options] : [],
            })) : [],
            nextScreeningQuestionKey: 1,

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

            addScreeningQuestion() {
                if (this.screeningQuestions.length >= 15) return;
                this.screeningQuestions.push({
                    id: null,
                    localKey: `new-${this.nextScreeningQuestionKey++}`,
                    prompt: '',
                    type: 'short_text',
                    required: true,
                    options: [],
                });
            },

            removeScreeningQuestion(index) {
                this.screeningQuestions.splice(index, 1);
            },

            addScreeningOption(question) {
                if (question.options.length < 10) question.options.push('');
            },

            removeScreeningOption(question, index) {
                if (question.options.length <= 2) return;
                question.options.splice(index, 1);
            },

            ensureScreeningOptions(question) {
                while (question.options.length < 2) question.options.push('');
            },

            destroy() {
                if (quill && onTextChange) quill.off('text-change', onTextChange);
                if (onBeforeUnload) window.removeEventListener('beforeunload', onBeforeUnload);
                quill = null;
            },
        };
    });
}
