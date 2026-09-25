const ready = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });

        return;
    }

    callback();
};

ready(() => {
    const form = document.querySelector('[data-application-wizard]');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const wizardContainer = form.closest('[data-application-wizard-container]') ?? form;
    const steps = Array.from(form.querySelectorAll('[data-wizard-step]'));
    const status = wizardContainer.querySelector('[data-progress-status]');
    const progress = wizardContainer.querySelector('[data-overall-progress]');
    const progressBar = wizardContainer.querySelector('[data-overall-bar]');
    const progressPercent = wizardContainer.querySelector('[data-overall-percent]');
    const progressSteps = Array.from(wizardContainer.querySelectorAll('[data-progress-step]'));
    const validationSummary = form.querySelector('[data-validation-summary]');
    const stateSelect = form.querySelector('[data-state-of-origin]');
    const lgaSelect = form.querySelector('[data-local-government-area]');
    const identificationTypeInputs = Array.from(form.querySelectorAll('[data-identification-type]'))
        .filter((input) => input instanceof HTMLInputElement);
    const identificationDocumentInput = form.querySelector('[data-identification-document]');
    const identificationDocumentDropzone = form.querySelector('[data-identification-document-dropzone]');
    const identificationDocumentLabel = form.querySelector('[data-identification-document-label]');
    const identificationDocumentHelp = form.querySelector('[data-identification-document-help]');
    const identificationDocumentPrompt = form.querySelector('[data-identification-document-prompt]');
    const documentsContainer = form.querySelector('[data-education-documents]');
    const addDocumentButton = form.querySelector('[data-add-document]');
    const documentTemplate = document.querySelector('#education-document-template');
    const profileImageInput = form.querySelector('#profile-image-input');
    const profileImagePreview = form.querySelector('#profile-image-preview');
    const profilePreviewFrame = form.querySelector('[data-profile-preview-frame]');
    const profilePreviewStatus = form.querySelector('[data-profile-preview-status]');
    const profileImageTrigger = form.querySelector('[data-profile-image-trigger]');
    const removeProfileImageButton = form.querySelector('[data-remove-profile-image]');
    const submitButton = form.querySelector('[data-wizard-submit]');
    const submitSpinner = form.querySelector('[data-submit-spinner]');

    if (steps.length === 0) {
        return;
    }

    const namePattern = /^[\p{L}\s'-]+$/u;
    const phonePattern = /^\+?[0-9\s().-]{7,20}$/;
    const zipcodePattern = /^[A-Za-z0-9\s-]{3,20}$/;
    const parsedInitialStep = Number.parseInt(form.dataset.initialStep ?? '0', 10);
    let currentStep = Number.isInteger(parsedInitialStep)
        ? Math.min(Math.max(parsedInitialStep, 0), steps.length - 1)
        : 0;
    let profilePreviewUrl = null;
    let lgaRequest = null;

    const fieldsFor = (step) => {
        const radioGroups = new Set();

        return Array.from(step.querySelectorAll('input, select, textarea')).filter((field) => {
            if (!(field instanceof HTMLInputElement) || field.type !== 'radio') {
                return true;
            }

            if (radioGroups.has(field.name)) {
                return false;
            }

            radioGroups.add(field.name);

            return true;
        });
    };

    const normalizeLabel = (value) => value.replace(/\*/g, '').replace(/\s+/g, ' ').trim();

    const labelFor = (field) => {
        if (field instanceof HTMLInputElement && field.type === 'radio') {
            const legend = field.closest('[data-field]')?.querySelector('legend');

            if (legend) {
                return normalizeLabel(legend.textContent ?? '');
            }
        }

        if (field.id) {
            const label = form.querySelector(`label[for="${CSS.escape(field.id)}"]`);

            if (label) {
                return normalizeLabel(label.textContent ?? '');
            }
        }

        return normalizeLabel(field.closest('[data-field]')?.querySelector('legend, label')?.textContent ?? '') || 'This field';
    };

    const fieldContainerFor = (field) => field.closest('[data-field]');

    const errorFor = (field) => fieldContainerFor(field)?.querySelector('[data-validation-message]');

    const dropzoneFor = (field) => field.closest('[data-document-dropzone]');

    const radioGroupFor = (field) => Array.from(form.querySelectorAll('input[type="radio"]'))
        .filter((input) => input.name === field.name);

    const setError = (field, message) => {
        field.classList.add('border-red-500');
        field.setAttribute('aria-invalid', 'true');
        dropzoneFor(field)?.classList.add('border-red-500', 'bg-red-50/50', 'dark:bg-red-500/5');

        if (field instanceof HTMLInputElement && field.type === 'radio') {
            fieldContainerFor(field)?.classList.add('border-red-500');
        }

        const error = errorFor(field);

        if (error) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
    };

    const clearError = (field) => {
        const fields = field instanceof HTMLInputElement && field.type === 'radio'
            ? radioGroupFor(field)
            : [field];

        fields.forEach((errorField) => {
            errorField.classList.remove('border-red-500');
            errorField.removeAttribute('aria-invalid');
            dropzoneFor(errorField)?.classList.remove('border-red-500', 'bg-red-50/50', 'dark:bg-red-500/5');
        });

        if (field instanceof HTMLInputElement && field.type === 'radio') {
            fieldContainerFor(field)?.classList.remove('border-red-500');
        }

        const error = errorFor(field);

        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }
    };

    const showSummary = (message) => {
        if (!(validationSummary instanceof HTMLElement)) {
            return;
        }

        validationSummary.textContent = message;
        validationSummary.classList.remove('hidden');
        validationSummary.focus({ preventScroll: true });
    };

    const hideSummary = () => validationSummary?.classList.add('hidden');

    const allowedTypesFor = (field) => {
        try {
            const types = JSON.parse(field.dataset.allowedTypes ?? '[]');

            return Array.isArray(types) ? types.map((type) => String(type).toLowerCase()) : [];
        } catch {
            return [];
        }
    };

    const extensionFor = (file) => file.name.split('.').pop()?.toLowerCase() ?? '';

    const readImageDimensions = (file) => new Promise((resolve, reject) => {
        const image = new Image();
        const imageUrl = URL.createObjectURL(file);

        image.onload = () => {
            URL.revokeObjectURL(imageUrl);
            resolve({ width: image.naturalWidth, height: image.naturalHeight });
        };

        image.onerror = () => {
            URL.revokeObjectURL(imageUrl);
            reject(new Error('Unable to read the image.'));
        };

        image.src = imageUrl;
    });

    const setProfileImageControls = (hasSelection) => {
        if (profileImageTrigger instanceof HTMLElement) {
            profileImageTrigger.textContent = hasSelection ? 'Change photo' : 'Choose photo';
        }

        if (removeProfileImageButton instanceof HTMLButtonElement) {
            removeProfileImageButton.hidden = !hasSelection;
        }
    };

    const defaultProfilePreviewMessage = () => profileImageInput instanceof HTMLInputElement && !profileImageInput.required
        ? 'Choose a photo to replace your current profile photo.'
        : 'Choose a photo to preview it before submission.';

    const resetProfilePreview = (message = defaultProfilePreviewMessage()) => {
        if (profilePreviewUrl) {
            URL.revokeObjectURL(profilePreviewUrl);
            profilePreviewUrl = null;
        }

        if (profileImagePreview instanceof HTMLImageElement) {
            profileImagePreview.src = profileImagePreview.dataset.defaultSrc ?? profileImagePreview.src;
        }

        profilePreviewFrame?.classList.remove('ring-2', 'ring-brand-500', 'ring-offset-2', 'dark:ring-offset-gray-900');
        setProfileImageControls(false);

        if (profilePreviewStatus instanceof HTMLElement) {
            profilePreviewStatus.textContent = message;
        }
    };

    const updateProfilePreview = (file, dimensions) => {
        if (!(profileImagePreview instanceof HTMLImageElement)) {
            return;
        }

        if (profilePreviewUrl) {
            URL.revokeObjectURL(profilePreviewUrl);
        }

        profilePreviewUrl = URL.createObjectURL(file);
        profileImagePreview.src = profilePreviewUrl;
        profilePreviewFrame?.classList.add('ring-2', 'ring-brand-500', 'ring-offset-2', 'dark:ring-offset-gray-900');
        setProfileImageControls(true);

        if (profilePreviewStatus instanceof HTMLElement) {
            profilePreviewStatus.textContent = `${file.name} selected - ${dimensions.width}x${dimensions.height}px.`;
        }
    };

    const updateDropzone = (field) => {
        const dropzone = dropzoneFor(field);
        const fileName = dropzone?.querySelector('[data-document-file-name]');
        const file = field.files?.[0];

        if (!(fileName instanceof HTMLElement)) {
            return;
        }

        fileName.textContent = file ? `${file.name} selected` : '';
        fileName.classList.toggle('hidden', !file);
    };

    const updateIdentificationMethod = ({ clearDocument = false } = {}) => {
        const selectedInput = identificationTypeInputs.find((input) => input.checked) ?? null;
        const selectedLabel = selectedInput?.dataset.identificationLabel ?? '';
        const hasSelection = selectedInput instanceof HTMLInputElement;
        const maximumMegabytes = Number(identificationDocumentInput?.dataset.maxKb ?? 0) / 1024;
        const fileHelp = `PDF, JPG, or PNG; up to ${maximumMegabytes || 5}MB.`;

        identificationTypeInputs.forEach((input) => {
            const option = input.closest('[data-identification-option]');
            const isSelected = input === selectedInput;

            option?.classList.toggle('border-brand-500', isSelected);
            option?.classList.toggle('bg-brand-50', isSelected);
            option?.classList.toggle('dark:border-brand-400', isSelected);
            option?.classList.toggle('dark:bg-brand-500/10', isSelected);
            option?.classList.toggle('border-gray-200', !isSelected);
            option?.classList.toggle('bg-white', !isSelected);
            option?.classList.toggle('dark:border-gray-800', !isSelected);
            option?.classList.toggle('dark:bg-white/[0.02]', !isSelected);
        });

        if (identificationDocumentInput instanceof HTMLInputElement) {
            if (clearDocument && identificationDocumentInput.files?.length) {
                identificationDocumentInput.value = '';
                updateDropzone(identificationDocumentInput);
                clearError(identificationDocumentInput);
            }

            identificationDocumentInput.disabled = !hasSelection;
        }

        if (identificationDocumentLabel instanceof HTMLElement) {
            identificationDocumentLabel.textContent = selectedLabel || 'Identification document';
        }

        if (identificationDocumentHelp instanceof HTMLElement) {
            identificationDocumentHelp.textContent = hasSelection
                ? `Upload your ${selectedLabel}. ${fileHelp}`
                : `Select an identification method before choosing its document. ${fileHelp}`;
        }

        if (identificationDocumentPrompt instanceof HTMLElement) {
            identificationDocumentPrompt.textContent = hasSelection
                ? `Choose or drop your ${selectedLabel}`
                : 'Choose or drop a document';
        }

        if (identificationDocumentDropzone instanceof HTMLElement) {
            identificationDocumentDropzone.tabIndex = hasSelection ? 0 : -1;
            identificationDocumentDropzone.setAttribute('aria-disabled', String(!hasSelection));
            identificationDocumentDropzone.setAttribute('aria-label', hasSelection
                ? `Choose ${selectedLabel}`
                : 'Select an identification method first');
            identificationDocumentDropzone.classList.toggle('cursor-not-allowed', !hasSelection);
            identificationDocumentDropzone.classList.toggle('opacity-60', !hasSelection);
        }
    };

    const promptForIdentificationMethod = () => {
        const firstInput = identificationTypeInputs[0];

        if (!(firstInput instanceof HTMLInputElement)) {
            return;
        }

        setError(firstInput, 'Select an identification method before choosing or dropping a document.');
        firstInput.focus({ preventScroll: false });
    };

    const validateFile = async (field) => {
        const file = field.files?.[0];
        const resetInvalidProfileImage = () => {
            if (field.dataset.fileKind !== 'profile-image') {
                return;
            }

            field.value = '';
            resetProfilePreview();
        };

        if (!file) {
            if (field.dataset.fileKind === 'profile-image') {
                resetProfilePreview();
            }

            if (field.required) {
                setError(field, `${labelFor(field)} is required.`);

                return false;
            }

            clearError(field);

            return true;
        }

        const allowedTypes = allowedTypesFor(field);
        const maximumKilobytes = Number(field.dataset.maxKb ?? 0);
        const extension = extensionFor(file);

        if (allowedTypes.length > 0 && !allowedTypes.includes(extension)) {
            resetInvalidProfileImage();
            setError(field, `${labelFor(field)} must be a ${allowedTypes.map((type) => type.toUpperCase()).join(', ')} file.`);

            return false;
        }

        if (maximumKilobytes > 0 && file.size > maximumKilobytes * 1024) {
            resetInvalidProfileImage();
            setError(field, `${labelFor(field)} must not be larger than ${maximumKilobytes / 1024}MB.`);

            return false;
        }

        if (field.dataset.fileKind === 'profile-image') {
            try {
                const dimensions = await readImageDimensions(file);
                const minimumWidth = Number(field.dataset.minWidth ?? 0);
                const minimumHeight = Number(field.dataset.minHeight ?? 0);
                const maximumWidth = Number(field.dataset.maxWidth ?? Infinity);
                const maximumHeight = Number(field.dataset.maxHeight ?? Infinity);

                if (
                    dimensions.width < minimumWidth ||
                    dimensions.height < minimumHeight ||
                    dimensions.width > maximumWidth ||
                    dimensions.height > maximumHeight
                ) {
                    resetInvalidProfileImage();
                    setError(field, `${labelFor(field)} must be between ${minimumWidth}x${minimumHeight} and ${maximumWidth}x${maximumHeight} pixels.`);

                    return false;
                }

                updateProfilePreview(file, dimensions);
            } catch {
                resetInvalidProfileImage();
                setError(field, 'Choose a valid image that can be previewed.');

                return false;
            }
        }

        clearError(field);

        return true;
    };

    const validateStandardField = (field) => {
        if (field instanceof HTMLInputElement && field.type === 'radio') {
            const radioGroup = radioGroupFor(field);
            const selectedInput = radioGroup.find((input) => input.checked);

            if (radioGroup.some((input) => input.required) && !selectedInput) {
                setError(field, `Select ${labelFor(field).toLowerCase()}.`);

                return false;
            }

            clearError(field);

            return true;
        }

        if (field.disabled) {
            if (field === lgaSelect && stateSelect?.value) {
                setError(field, 'Wait for local government areas to load before continuing.');

                return false;
            }

            return true;
        }

        const value = field.value.trim();

        if (field.required && value === '') {
            setError(field, `${labelFor(field)} is required.`);

            return false;
        }

        if (value === '') {
            clearError(field);

            return true;
        }

        if (['first_name', 'middle_name', 'last_name', 'nationality'].includes(field.name) && !namePattern.test(value)) {
            setError(field, `${labelFor(field)} may only contain letters, spaces, hyphens, and apostrophes.`);

            return false;
        }

        if (field.name === 'phone' && !phonePattern.test(value)) {
            setError(field, 'Enter a valid phone number using 7 to 20 digits, with an optional leading plus sign.');

            return false;
        }

        if (field.name === 'zipcode' && !zipcodePattern.test(value)) {
            setError(field, 'The zipcode may only contain letters, numbers, spaces, and hyphens.');

            return false;
        }

        if (!field.checkValidity()) {
            setError(field, field.validationMessage || `${labelFor(field)} is invalid.`);

            return false;
        }

        clearError(field);

        return true;
    };

    const validateField = (field) => field.type === 'file'
        ? validateFile(field)
        : Promise.resolve(validateStandardField(field));

    const validateFields = async (fields) => {
        let firstInvalidField = null;

        for (const field of fields) {
            if (!await validateField(field) && !firstInvalidField) {
                firstInvalidField = field;
            }
        }

        if (firstInvalidField) {
            firstInvalidField.focus({ preventScroll: false });

            return false;
        }

        return true;
    };

    const valueFor = (name) => form.elements.namedItem(name)?.value?.trim() ?? '';

    const setSummaryValue = (selector, value) => {
        const target = form.querySelector(selector);

        if (target) {
            target.textContent = value || 'Not provided';
        }
    };

    const updateSummary = () => {
        const applicantName = [valueFor('first_name'), valueFor('last_name')].filter(Boolean).join(' ');
        const origin = [valueFor('local_government_area'), valueFor('state_of_origin')].filter(Boolean).join(', ');
        const documentCount = documentsContainer?.querySelectorAll('[data-education-document]').length ?? 0;

        setSummaryValue('[data-summary-full-name]', applicantName);
        setSummaryValue('[data-summary-contact]', valueFor('phone'));
        setSummaryValue('[data-summary-origin]', origin);
        setSummaryValue('[data-summary-nationality]', valueFor('nationality'));
        setSummaryValue('[data-summary-documents]', String(documentCount));
    };

    const updateProgress = () => {
        const lastStepIndex = Math.max(steps.length - 1, 1);
        const percentage = Math.round((currentStep / lastStepIndex) * 100);

        if (progressBar instanceof HTMLElement) {
            progressBar.style.width = `${percentage}%`;
        }

        if (progressPercent instanceof HTMLElement) {
            progressPercent.textContent = `${percentage}%`;
        }

        if (status instanceof HTMLElement) {
            status.textContent = `Step ${currentStep + 1} of ${steps.length}`;
        }

        progress?.setAttribute('aria-valuenow', String(percentage));
        progress?.setAttribute('aria-valuetext', `Step ${currentStep + 1} of ${steps.length}, ${percentage}% complete`);

        progressSteps.forEach((step, index) => {
            const marker = step.querySelector('[data-progress-marker]');
            const label = step.querySelector('[data-progress-label]');
            const isComplete = index < currentStep;
            const isCurrent = index === currentStep;

            if (isCurrent) {
                step.setAttribute('aria-current', 'step');
            } else {
                step.removeAttribute('aria-current');
            }

            marker?.classList.toggle('border-success-500', isComplete);
            marker?.classList.toggle('bg-success-500', isComplete);
            marker?.classList.toggle('border-brand-500', isCurrent && !isComplete);
            marker?.classList.toggle('bg-brand-500', isCurrent && !isComplete);
            marker?.classList.toggle('border-gray-300', !isComplete && !isCurrent);
            marker?.classList.toggle('bg-white', !isComplete && !isCurrent);
            marker?.classList.toggle('text-white', isComplete || isCurrent);
            marker?.classList.toggle('text-gray-500', !isComplete && !isCurrent);
            marker?.classList.toggle('dark:border-gray-700', !isComplete && !isCurrent);
            marker?.classList.toggle('dark:bg-gray-900', !isComplete && !isCurrent);
            marker?.classList.toggle('dark:text-gray-400', !isComplete && !isCurrent);

            label?.classList.toggle('font-semibold', isComplete || isCurrent);
            label?.classList.toggle('text-success-700', isComplete);
            label?.classList.toggle('dark:text-success-400', isComplete);
            label?.classList.toggle('text-brand-700', isCurrent && !isComplete);
            label?.classList.toggle('dark:text-brand-300', isCurrent && !isComplete);
            label?.classList.toggle('text-gray-500', !isComplete && !isCurrent);
            label?.classList.toggle('dark:text-gray-400', !isComplete && !isCurrent);
        });
    };

    const showStep = (index) => {
        currentStep = Math.min(Math.max(index, 0), steps.length - 1);

        steps.forEach((step, stepIndex) => {
            step.hidden = stepIndex !== currentStep;
        });

        hideSummary();
        updateSummary();
        updateProgress();
    };

    const bindDropzone = (dropzone, onUnavailable = null) => {
        if (!(dropzone instanceof HTMLElement) || dropzone.dataset.bound === 'true') {
            return;
        }

        const input = dropzone.querySelector('[data-document-file]');

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const assignFile = (files) => {
            if (!files?.length) {
                return;
            }

            if (typeof DataTransfer === 'undefined') {
                setError(input, 'Your browser cannot add a dropped file. Choose the file instead.');

                return;
            }

            try {
                const transfer = new DataTransfer();
                transfer.items.add(files[0]);
                input.files = transfer.files;
            } catch {
                setError(input, 'Your browser cannot add a dropped file. Choose the file instead.');

                return;
            }

            input.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const inputIsUnavailable = () => input.disabled || dropzone.getAttribute('aria-disabled') === 'true';

        dropzone.dataset.bound = 'true';
        dropzone.addEventListener('click', (event) => {
            if (inputIsUnavailable()) {
                event.preventDefault();
                onUnavailable?.();

                return;
            }

            if (event.target !== input) {
                input.click();
            }
        });
        dropzone.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();

            if (inputIsUnavailable()) {
                onUnavailable?.();

                return;
            }

            input.click();
        });
        ['dragenter', 'dragover'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();

            if (inputIsUnavailable()) {
                return;
            }

            dropzone.classList.add('border-brand-500', 'bg-brand-50/60', 'dark:bg-brand-500/10');
        }));
        ['dragleave', 'drop'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('border-brand-500', 'bg-brand-50/60', 'dark:bg-brand-500/10');
        }));
        dropzone.addEventListener('drop', (event) => {
            if (inputIsUnavailable()) {
                onUnavailable?.();

                return;
            }

            assignFile(event.dataTransfer?.files);
        });
    };

    const refreshEducationRows = () => {
        if (!(documentsContainer instanceof HTMLElement)) {
            return;
        }

        const rows = Array.from(documentsContainer.querySelectorAll('[data-education-document]'));

        rows.forEach((row, index) => {
            const type = row.querySelector('[data-document-type]');
            const file = row.querySelector('[data-document-file]');
            const typeLabel = row.querySelector('[data-document-type-label]');
            const fileLabel = row.querySelector('[data-document-file-label]');
            const title = row.querySelector('[data-document-title]');
            const removeButton = row.querySelector('[data-remove-document]');
            const dropzone = row.querySelector('[data-document-dropzone]');
            const typeError = type?.closest('[data-field]')?.querySelector('[data-validation-message]');
            const fileError = file?.closest('[data-field]')?.querySelector('[data-validation-message]');
            const fileHelp = row.querySelector('[data-document-file-help]');

            if (title) {
                title.textContent = `Qualification document ${index + 1}`;
            }

            if (type instanceof HTMLSelectElement) {
                type.name = `education_documents[${index}][type]`;
                type.id = `education-document-type-${index}`;
                type.setAttribute('aria-describedby', `education-document-type-error-${index}`);
            }

            if (file instanceof HTMLInputElement) {
                file.name = `education_documents[${index}][file]`;
                file.id = `education-document-file-${index}`;
                file.setAttribute('aria-describedby', `education-document-file-help-${index} education-document-file-error-${index}`);
                updateDropzone(file);
            }

            if (typeLabel instanceof HTMLLabelElement && type instanceof HTMLSelectElement) {
                typeLabel.htmlFor = type.id;
            }

            if (fileLabel instanceof HTMLLabelElement && file instanceof HTMLInputElement) {
                fileLabel.htmlFor = file.id;
            }

            if (typeError instanceof HTMLElement) {
                typeError.id = `education-document-type-error-${index}`;
            }

            if (fileError instanceof HTMLElement) {
                fileError.id = `education-document-file-error-${index}`;
            }

            if (fileHelp instanceof HTMLElement) {
                fileHelp.id = `education-document-file-help-${index}`;
            }

            if (removeButton instanceof HTMLButtonElement) {
                removeButton.hidden = rows.length === 1;
            }

            bindDropzone(dropzone);
        });

        if (addDocumentButton instanceof HTMLButtonElement) {
            addDocumentButton.disabled = rows.length >= 10;
        }

        updateSummary();
    };

    const populateLgas = async (selectedLga = '') => {
        if (!(stateSelect instanceof HTMLSelectElement) || !(lgaSelect instanceof HTMLSelectElement)) {
            return;
        }

        const lgaUrl = stateSelect.selectedOptions[0]?.dataset.lgaUrl;

        if (lgaRequest) {
            lgaRequest.abort();
        }

        lgaSelect.innerHTML = '';
        lgaSelect.disabled = true;
        lgaSelect.dataset.loading = lgaUrl ? 'true' : 'false';
        lgaSelect.append(new Option(lgaUrl ? 'Loading LGAs...' : 'Select state first', ''));

        if (!lgaUrl) {
            clearError(lgaSelect);
            updateSummary();

            return;
        }

        const request = new AbortController();
        lgaRequest = request;

        try {
            const response = await fetch(lgaUrl, {
                signal: request.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load local government areas.');
            }

            const payload = await response.json();
            const localGovernmentAreas = Array.isArray(payload.data) ? payload.data : [];

            if (lgaRequest !== request) {
                return;
            }

            lgaSelect.innerHTML = '';
            lgaSelect.append(new Option(localGovernmentAreas.length ? 'Select LGA' : 'No LGAs available', ''));

            localGovernmentAreas.forEach((lga) => {
                lgaSelect.append(new Option(lga.name, lga.name, false, lga.name === selectedLga));
            });

            lgaSelect.disabled = localGovernmentAreas.length === 0;
            clearError(lgaSelect);
        } catch (error) {
            if (error?.name === 'AbortError' || lgaRequest !== request) {
                return;
            }

            lgaSelect.innerHTML = '';
            lgaSelect.append(new Option('Unable to load LGAs', ''));
            lgaSelect.disabled = true;
            setError(lgaSelect, 'Unable to load local government areas. Please select the state again.');
        } finally {
            if (lgaRequest === request) {
                lgaSelect.dataset.loading = 'false';
                lgaRequest = null;
                updateSummary();
            }
        }
    };

    const nextStep = async () => {
        const isValid = await validateFields(fieldsFor(steps[currentStep]));

        if (!isValid) {
            showSummary('Please correct the highlighted fields in this step before continuing.');

            return;
        }

        showStep(currentStep + 1);
    };

    const submitApplication = async () => {
        const allFields = steps.flatMap(fieldsFor);

        if (!await validateFields(allFields)) {
            const invalidStep = steps.findIndex((step) => step.querySelector('[aria-invalid="true"]'));
            showStep(invalidStep >= 0 ? invalidStep : 0);
            showSummary('Please correct the highlighted fields before submitting your application.');

            return;
        }

        const confirmation = {
            icon: 'question',
            title: form.dataset.confirmTitle || 'Submit application?',
            text: form.dataset.confirmText || 'Please confirm your information before submitting.',
            showCancelButton: true,
            confirmButtonText: form.dataset.confirmButton || 'Submit application',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true,
        };
        const result = window.Swal?.fire
            ? await window.Swal.fire(confirmation)
            : { isConfirmed: window.confirm(confirmation.text) };

        if (!result.isConfirmed) {
            return;
        }

        form.dataset.submitting = 'true';

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = true;
        }

        submitSpinner?.classList.remove('hidden');
        HTMLFormElement.prototype.submit.call(form);
    };

    form.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const nextButton = event.target.closest('[data-wizard-next]');
        const previousButton = event.target.closest('[data-wizard-previous]');

        if (nextButton) {
            event.preventDefault();
            await nextStep();
        }

        if (previousButton) {
            event.preventDefault();
            showStep(currentStep - 1);
        }
    });

    form.addEventListener('input', (event) => {
        if (!(event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement || event.target instanceof HTMLTextAreaElement)) {
            return;
        }

        if (event.target.type !== 'file') {
            void validateField(event.target);
        }

        updateSummary();
    });

    form.addEventListener('change', async (event) => {
        if (!(event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement || event.target instanceof HTMLTextAreaElement)) {
            return;
        }

        if (event.target === stateSelect) {
            await populateLgas();
        }

        if (event.target.matches('[data-identification-type]')) {
            updateIdentificationMethod({ clearDocument: true });
        }

        if (event.target.matches('[data-document-file]')) {
            updateDropzone(event.target);
        }

        await validateField(event.target);
        updateSummary();
    });

    removeProfileImageButton?.addEventListener('click', () => {
        if (!(profileImageInput instanceof HTMLInputElement)) {
            return;
        }

        profileImageInput.value = '';
        clearError(profileImageInput);
        resetProfilePreview('Selected photo removed. Choose a photo to preview it before submission.');
    });

    addDocumentButton?.addEventListener('click', () => {
        if (!(documentTemplate instanceof HTMLTemplateElement) || !(documentsContainer instanceof HTMLElement)) {
            return;
        }

        if (documentsContainer.querySelectorAll('[data-education-document]').length >= 10) {
            return;
        }

        documentsContainer.append(documentTemplate.content.firstElementChild.cloneNode(true));
        refreshEducationRows();
        documentsContainer.lastElementChild?.querySelector('[data-document-type]')?.focus();
    });

    documentsContainer?.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const removeButton = event.target.closest('[data-remove-document]');

        if (!removeButton || documentsContainer.querySelectorAll('[data-education-document]').length <= 1) {
            return;
        }

        removeButton.closest('[data-education-document]')?.remove();
        refreshEducationRows();
    });

    form.addEventListener('submit', async (event) => {
        if (form.dataset.submitting === 'true') {
            return;
        }

        event.preventDefault();
        await submitApplication();
    });

    window.addEventListener('beforeunload', () => {
        if (profilePreviewUrl) {
            URL.revokeObjectURL(profilePreviewUrl);
        }
    }, { once: true });

    resetProfilePreview();
    bindDropzone(identificationDocumentDropzone, promptForIdentificationMethod);
    updateIdentificationMethod();
    refreshEducationRows();
    void populateLgas(lgaSelect?.dataset.selectedLga ?? '');
    showStep(currentStep);
});
