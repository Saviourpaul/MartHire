const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[character]));

export function createOrganizationEmailTemplateStudio({
    templates = {},
    sample = {},
    editor = {},
    selected = null,
    organizationName = '',
    requiredPlaceholder = null,
}) {
    return {
        templates,
        sample: { ...sample },
        editor: { ...editor },
        selected,
        organizationName,
        requiredPlaceholder,
        view: 'preview',
        device: 'desktop',
        submitting: false,
        statusMessage: '',
        savedValues: null,

        init() {
            this.savedValues = this.editorSnapshot();
        },

        get renderedSubject() {
            return this.replacePlaceholders(this.editor.subject);
        },

        get renderedBody() {
            return this.replacePlaceholders(this.editor.body);
        },

        get textPreview() {
            return `From: ${this.organizationName} via Marthire\nTo: ${this.sample.candidate_name || 'Candidate'}\nSubject: ${this.renderedSubject}\n\n${this.renderedBody}`;
        },

        get htmlPreview() {
            const actions = this.selected === 'interview_cancel' ? '' : '\n    <p style="margin-top:28px"><a href="{{ confirm_url }}" style="display:inline-block;padding:10px 18px;background:#153d31;color:#fff;text-decoration:none;border-radius:6px">Confirm attendance</a> <a href="{{ decline_url }}" style="display:inline-block;padding:10px 18px;color:#153d31;border:1px solid #ccd8d1;text-decoration:none;border-radius:6px">Decline invitation</a></p>\n    <!-- Signed response URLs are added for each interview when the email is sent. -->';
            return `<!doctype html>\n<html lang="en">\n<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>${escapeHtml(this.renderedSubject)}</title></head>\n<body style="margin:0;background:#f4f6f5;color:#153d31;font-family:Arial,sans-serif;line-height:1.6">\n  <main style="max-width:600px;margin:32px auto;padding:32px;background:#fff;border:1px solid #e0e7e3;border-radius:12px">\n    <p style="font-size:13px;color:#65766e">${escapeHtml(this.organizationName)}</p><h1 style="font-size:22px">${escapeHtml(this.renderedSubject)}</h1>\n    <div style="white-space:pre-wrap">${escapeHtml(this.renderedBody)}</div>${actions}\n  </main>\n</body>\n</html>`;
        },

        get isDirty() {
            return ! this.savedValues
                || this.savedValues.subject !== this.editor.subject
                || this.savedValues.body !== this.editor.body
                || this.savedValues.is_active !== Boolean(this.editor.is_active);
        },

        replacePlaceholders(content) {
            return String(content ?? '').replace(/\{\{\s*([a-z_]+)\s*\}\}/g, (token, name) => (
                Object.prototype.hasOwnProperty.call(this.sample, name) ? this.sample[name] : token
            ));
        },

        previewSubject(type) {
            return this.replacePlaceholders(this.templates[type]?.subject ?? '');
        },

        previewExcerpt(type) {
            return this.replacePlaceholders(this.templates[type]?.body ?? '')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, 132);
        },

        usesPlaceholder(name) {
            const content = `${this.editor.subject ?? ''}\n${this.editor.body ?? ''}`;
            return content.includes(`{{ ${name} }}`)
                || new RegExp(`\\{\\{\\s*${name}\\s*\\}\\}`).test(content);
        },

        editorSnapshot() {
            return {
                subject: this.editor.subject ?? '',
                body: this.editor.body ?? '',
                is_active: Boolean(this.editor.is_active),
            };
        },

        confirmLeave(event) {
            if (this.isDirty && ! window.confirm('You have unsaved changes. Leave this template?')) {
                event.preventDefault();
            }
        },

        setView(view) {
            this.view = view;
        },

        handleTabKeydown(event) {
            if (! ['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key) || ! event.target.matches('[role="tab"]')) return;

            const tabs = [...event.currentTarget.querySelectorAll('[role="tab"]')];
            const currentIndex = tabs.indexOf(event.target);
            const nextIndex = event.key === 'Home' ? 0
                : event.key === 'End' ? tabs.length - 1
                    : (currentIndex + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length;

            event.preventDefault();
            tabs[nextIndex]?.click();
            tabs[nextIndex]?.focus();
        },

        insertPlaceholder(token) {
            const field = document.getElementById(`template-${this.selected}-body`);
            if (! field || ! token) return;

            const start = field.selectionStart ?? this.editor.body.length;
            const end = field.selectionEnd ?? start;
            this.editor.body = `${this.editor.body.slice(0, start)}${token}${this.editor.body.slice(end)}`;
            this.statusMessage = `${token} inserted into the message.`;

            this.$nextTick(() => {
                field.focus();
                const cursor = start + token.length;
                field.setSelectionRange(cursor, cursor);
            });
        },

        async copyHtml() {
            try {
                await navigator.clipboard.writeText(this.htmlPreview);
                this.statusMessage = 'HTML preview copied.';
            } catch (_) {
                this.statusMessage = 'Clipboard access is unavailable. Select and copy the HTML from the HTML tab.';
                this.view = 'html';
            }
        },

        download(format) {
            const isHtml = format === 'html';
            const content = isHtml ? this.htmlPreview : this.textPreview;
            const blob = new Blob([content], { type: isHtml ? 'text/html;charset=utf-8' : 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${this.selected || 'interview-message'}.${isHtml ? 'html' : 'txt'}`;
            document.body.append(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
            this.statusMessage = `${isHtml ? 'HTML' : 'Text'} preview downloaded.`;
        },
    };
}

export function registerOrganizationEmailTemplateStudio(Alpine) {
    Alpine.data('organizationEmailTemplateStudio', (options) => createOrganizationEmailTemplateStudio(options));
}
