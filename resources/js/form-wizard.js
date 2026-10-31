// Navigation is driven by successful Next actions, never by input events.
export function createFormWizard() {
    return {
        ready: false,
        step: 0,
        steps: [],
        review: [],

        initializeWizard() {
            this.steps = [...this.$refs.form.querySelectorAll('[data-wizard-step]')].map((panel, index) => ({
                index,
                title: panel.dataset.stepTitle || `Step ${index + 1}`,
                description: panel.dataset.stepDescription || '',
            }));
            this.ready = true;
        },
        isLast() { return this.step === this.steps.length - 1; },
        completionPercentage() { return this.steps.length > 1 ? Math.round(this.step / (this.steps.length - 1) * 100) : 0; },
        panels() { return [...this.$refs.form.querySelectorAll('[data-wizard-step]')]; },
        stepFields(index) { return [...(this.panels()[index]?.querySelectorAll('[data-field]') || [])]; },
        stepBusy() {
            return Boolean(this.panels()[this.step]?.querySelector('[data-location-field]') && (this.loadingStates || this.loadingCities));
        },
        next() {
            if (this.submitting || this.isLastStep || this.stepBusy()) return false;
            if (!this.validate(this.step)) {
                this.focusErrors();
                return false;
            }
            this.moveTo(this.step + 1);
            return true;
        },
        back() {
            if (!this.submitting && this.step > 0) this.moveTo(this.step - 1);
        },
        editStep(index) {
            if (!this.submitting && Number.isInteger(index) && index >= 0 && index < this.step) this.moveTo(index);
        },
        moveTo(index, focus = true) {
            this.step = Math.max(0, Math.min(index, this.steps.length - 1));
            if (this.isLastStep) this.review = this.buildReview();
            if (focus) this.$nextTick(() => this.panels()[this.step]?.querySelector('[data-wizard-heading]')?.focus());
        },
        buildReview() {
            return this.steps.slice(0, -1).map((section) => ({
                title: section.title,
                step: section.index,
                entries: this.stepFields(section.index).map((field) => {
                    const controls = [...field.querySelectorAll('input, select, textarea')].filter((control) => !control.disabled && control.type !== 'hidden');
                    if (!controls.length) return null;
                    const first = this.controlFor?.(field) || controls[0];
                    let value;
                    if (first.hasAttribute?.('data-phone-input')) value = first.dataset.e164 || first.value;
                    else if (first.type === 'file') value = [...first.files].map((file) => file.name).join(', ');
                    else if (['radio', 'checkbox'].includes(first.type)) {
                        value = controls.filter((control) => control.checked).map((control) => control.closest('label')?.textContent.trim() || control.value).join(', ');
                    } else if (first.tagName === 'SELECT') value = first.value ? first.selectedOptions[0]?.textContent.trim() : '';
                    else value = first.value.trim();
                    return value ? { label: field.dataset.label || first.name, value: value.length > 1000 ? `${value.slice(0, 1000)}…` : value } : null;
                }).filter(Boolean),
            })).filter((section) => section.entries.length);
        },
    };
}
