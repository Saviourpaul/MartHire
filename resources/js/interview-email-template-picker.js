const invitationTypes = new Set(['interview_online', 'interview_onsite']);

export function createInterviewEmailTemplatePicker({
    mode = 'online',
    context = 'schedule',
    templates = [],
    subject = '',
    body = '',
    modeChangedMessage = '',
} = {}) {
    const safeTemplates = Array.isArray(templates)
        ? templates.filter((template) => template
            && typeof template.type === 'string'
            && typeof template.label === 'string'
            && typeof template.subject === 'string'
            && typeof template.body === 'string')
        : [];

    return {
        interviewMode: mode === 'onsite' ? 'onsite' : 'online',
        templateContext: context === 'reschedule' ? 'reschedule' : 'schedule',
        templates: safeTemplates,
        emailSubject: typeof subject === 'string' ? subject : '',
        emailBody: typeof body === 'string' ? body : '',
        modeChangedMessage,
        selectedTemplateType: '',
        selectedTemplateLabel: '',
        selectedTemplateSubject: '',
        selectedTemplateBody: '',
        lastSelectedTemplateType: '',
        modeChangeNotice: '',

        get availableTemplates() {
            return this.templateContext === 'reschedule'
                ? this.templates.filter((template) => template.type === 'interview_reschedule')
                : this.templates.filter((template) => invitationTypes.has(template.type));
        },

        init() {
            this.$watch('interviewMode', (mode) => this.handleModeChange(mode));
        },

        openTemplatePicker() {
            const dialog = this.$refs.templateDialog;
            if (!dialog || dialog.open || this.availableTemplates.length === 0) return;

            dialog.showModal();
            this.$nextTick(() => dialog.querySelector('[data-template-option]')?.focus());
        },

        closeTemplatePicker() {
            const dialog = this.$refs.templateDialog;
            if (dialog?.open) dialog.close();
        },

        selectTemplate(template) {
            const selected = this.availableTemplates.find((option) => option.type === template?.type);
            if (!selected) return;

            // An online or onsite template also chooses its matching interview
            // mode, keeping required details and server-side validation aligned.
            if (this.templateContext === 'schedule') {
                this.interviewMode = selected.type === 'interview_onsite' ? 'onsite' : 'online';
            }

            this.emailSubject = selected.subject;
            this.emailBody = selected.body;
            this.selectedTemplateType = selected.type;
            this.selectedTemplateLabel = selected.label;
            this.selectedTemplateSubject = selected.subject;
            this.selectedTemplateBody = selected.body;
            this.lastSelectedTemplateType = selected.type;
            this.modeChangeNotice = '';
            this.closeTemplatePicker();
        },

        handleModeChange(mode) {
            if (this.templateContext !== 'schedule' || !this.selectedTemplateType) return;

            const selectedMode = this.selectedTemplateType === 'interview_onsite' ? 'onsite' : 'online';
            if (selectedMode === mode) return;

            const fieldsUnchanged = this.emailSubject === this.selectedTemplateSubject
                && this.emailBody === this.selectedTemplateBody;

            // If the template is still untouched, blank fields restore the
            // server's effective organization default for the newly chosen mode.
            if (fieldsUnchanged) {
                this.emailSubject = '';
                this.emailBody = '';
            } else {
                this.modeChangeNotice = this.modeChangedMessage;
            }

            this.selectedTemplateType = '';
            this.selectedTemplateLabel = '';
            this.selectedTemplateSubject = '';
            this.selectedTemplateBody = '';
        },
    };
}

export function registerInterviewEmailTemplatePicker(Alpine) {
    Alpine.data('interviewEmailTemplatePicker', (options) => createInterviewEmailTemplatePicker(options));
}
