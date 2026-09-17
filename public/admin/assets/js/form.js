  (function() {
                const form = document.querySelector('[data-application-wizard]');

                if (!form) {
                    return;
                }

                const steps = Array.from(form.querySelectorAll('[data-application-wizard-step]'));
                const progressItems = Array.from(document.querySelectorAll('[data-application-wizard-progress-item]'));
                const validationSummary = form.querySelector('[data-validation-summary]');
                const profileImageInput = document.getElementById('profile-image-input');
                const profileImagePreview = document.getElementById('profile-image-preview');
                const profilePreviewStatus = document.getElementById('profile-image-preview-status');
                const namePattern = /^[\p{L}\s'-]+$/u;
                const phonePattern = /^\+?[0-9\s().-]{7,20}$/;
                const zipcodePattern = /^[A-Za-z0-9\s-]{3,20}$/;
                let profilePreviewUrl = null;
                let currentStep = 0;

                const formatMegabytes = (kilobytes) => `${Number(kilobytes / 1024).toFixed(kilobytes % 1024 === 0 ? 0 : 1)}MB`;

                const parseAllowedTypes = (field) => {
                    try {
                        return JSON.parse(field.dataset.allowedTypes || '[]');
                    } catch (error) {
                        return [];
                    }
                };

                const extensionFor = (file) => {
                    const parts = file.name.toLowerCase().split('.');

                    return parts.length > 1 ? parts.pop() : '';
                };

                const labelFor = (field) => {
                    if (field.id) {
                        const explicitLabel = form.querySelector(`label[for="${CSS.escape(field.id)}"]`);

                        if (explicitLabel) {
                            return explicitLabel.textContent.trim();
                        }
                    }

                    return field.closest('.mb-3, .upload-files')?.querySelector('label')?.textContent.trim() ||
                        field.name.replace(/[_\[\].]/g, ' ');
                };

                const feedbackFor = (field) => {
                    const wrapper = field.closest('.mb-3, .upload-files') || field.parentElement;
                    let feedback = wrapper?.querySelector('[data-validation-message]');

                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'validation-feedback';
                        feedback.dataset.validationMessage = '';
                        feedback.setAttribute('aria-live', 'polite');
                        field.insertAdjacentElement('afterend', feedback);
                    }

                    if (!feedback.id && field.id) {
                        feedback.id = `${field.id}-feedback`;
                    }

                    if (feedback.id) {
                        const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/)
                            .filter(Boolean));
                        descriptions.add(feedback.id);
                        field.setAttribute('aria-describedby', Array.from(descriptions).join(' '));
                    }

                    return feedback;
                };

                const setFieldError = (field, message) => {
                    const feedback = feedbackFor(field);

                    field.classList.add('is-invalid');
                    field.setAttribute('aria-invalid', 'true');
                    feedback.textContent = message;
                    feedback.classList.add('is-visible');
                };

                const clearFieldError = (field) => {
                    const feedback = feedbackFor(field);

                    field.classList.remove('is-invalid');
                    field.removeAttribute('aria-invalid');
                    feedback.textContent = '';
                    feedback.classList.remove('is-visible');
                };

                const showValidationSummary = (message) => {
                    if (!validationSummary) {
                        return;
                    }

                    validationSummary.textContent = message || 'Please correct the highlighted fields before continuing.';
                    validationSummary.classList.remove('d-none');
                    validationSummary.focus({ preventScroll: true });
                };

                const hideValidationSummary = () => {
                    validationSummary?.classList.add('d-none');
                };

                const loadImageDimensions = (file) => new Promise((resolve, reject) => {
                    const objectUrl = URL.createObjectURL(file);
                    const image = new Image();

                    image.onload = () => {
                        const dimensions = {
                            width: image.naturalWidth,
                            height: image.naturalHeight,
                        };

                        URL.revokeObjectURL(objectUrl);
                        resolve(dimensions);
                    };

                    image.onerror = () => {
                        URL.revokeObjectURL(objectUrl);
                        reject(new Error('Unable to read image dimensions.'));
                    };

                    image.src = objectUrl;
                });

                const setProfilePreviewStatus = (message, state = '') => {
                    if (!profilePreviewStatus) {
                        return;
                    }

                    profilePreviewStatus.textContent = message;
                    profilePreviewStatus.classList.toggle('is-valid', state === 'valid');
                    profilePreviewStatus.classList.toggle('is-invalid', state === 'invalid');
                };

                const validateFileField = async (field) => {
                    const label = labelFor(field);
                    const file = field.files?.[0] || null;
                    const allowedTypes = parseAllowedTypes(field);
                    const maxKb = Number(field.dataset.maxKb || 0);

                    if (!file) {
                        if (field.required) {
                            setFieldError(field, `${label} is required.`);
                            return false;
                        }

                        clearFieldError(field);
                        return true;
                    }

                    const extension = extensionFor(file);

                    if (allowedTypes.length > 0 && !allowedTypes.includes(extension)) {
                        setFieldError(field, `${label} must be a ${allowedTypes.map((type) => type.toUpperCase()).join(', ')} file.`);
                        return false;
                    }

                    if (maxKb > 0 && file.size > maxKb * 1024) {
                        setFieldError(field, `${label} must not be larger than ${formatMegabytes(maxKb)}.`);
                        return false;
                    }

                    if (field.dataset.fileKind === 'profile-image') {
                        try {
                            const dimensions = await loadImageDimensions(file);
                            const minWidth = Number(field.dataset.minWidth || 0);
                            const minHeight = Number(field.dataset.minHeight || 0);
                            const maxWidth = Number(field.dataset.maxWidth || Infinity);
                            const maxHeight = Number(field.dataset.maxHeight || Infinity);

                            if (
                                dimensions.width < minWidth ||
                                dimensions.height < minHeight ||
                                dimensions.width > maxWidth ||
                                dimensions.height > maxHeight
                            ) {
                                setFieldError(
                                    field,
                                    `${label} must be between ${minWidth}x${minHeight} and ${maxWidth}x${maxHeight} pixels.`
                                );
                                setProfilePreviewStatus('The selected image dimensions are not allowed.', 'invalid');
                                return false;
                            }

                            if (profileImagePreview) {
                                if (profilePreviewUrl) {
                                    URL.revokeObjectURL(profilePreviewUrl);
                                }

                                profilePreviewUrl = URL.createObjectURL(file);
                                profileImagePreview.src = profilePreviewUrl;
                            }

                            setProfilePreviewStatus(
                                `${file.name} selected - ${dimensions.width}x${dimensions.height}px, ${(file.size / 1024 / 1024).toFixed(2)}MB.`,
                                'valid'
                            );
                        } catch (error) {
                            setFieldError(field, 'Choose a valid image that can be previewed.');
                            setProfilePreviewStatus('The selected file could not be previewed.', 'invalid');
                            return false;
                        }
                    }

                    clearFieldError(field);
                    return true;
                };

                const validateStandardField = (field) => {
                    const label = labelFor(field);
                    const value = field.value.trim();

                    if (field.required && value === '') {
                        setFieldError(field, `${label} is required.`);
                        return false;
                    }

                    if (value === '') {
                        clearFieldError(field);
                        return true;
                    }

                    if (field.name === 'first_name' || field.name === 'middle_name' || field.name === 'last_name' || field.name === 'nationality') {
                        if (!namePattern.test(value)) {
                            setFieldError(field, `${label} may only contain letters, spaces, hyphens, and apostrophes.`);
                            return false;
                        }
                    }

                    if (field.name === 'phone' && !phonePattern.test(value)) {
                        setFieldError(field, 'Enter a valid phone number using 7 to 20 digits, with an optional leading plus sign.');
                        return false;
                    }

                    if (field.name === 'zipcode' && !zipcodePattern.test(value)) {
                        setFieldError(field, 'The zipcode may only contain letters, numbers, spaces, and hyphens.');
                        return false;
                    }

                    if (!field.checkValidity()) {
                        setFieldError(field, field.validationMessage || `${label} is invalid.`);
                        return false;
                    }

                    clearFieldError(field);
                    return true;
                };

                const validateField = async (field) => {
                    if (field.disabled) {
                        return true;
                    }

                    return field.type === 'file' ? validateFileField(field) : validateStandardField(field);
                };

                const validateFields = async (fields) => {
                    let firstInvalidField = null;

                    for (const field of fields) {
                        const isValid = await validateField(field);

                        if (!isValid && !firstInvalidField) {
                            firstInvalidField = field;
                        }
                    }

                    if (firstInvalidField) {
                        firstInvalidField.focus({ preventScroll: false });
                        return false;
                    }

                    hideValidationSummary();
                    return true;
                };

                const showStep = (index) => {
                    currentStep = Math.max(0, Math.min(index, steps.length - 1));

                    steps.forEach((step, stepIndex) => {
                        step.hidden = stepIndex !== currentStep;
                    });

                    progressItems.forEach((item, itemIndex) => {
                        item.classList.toggle('is-active', itemIndex === currentStep);
                        item.classList.toggle('is-complete', itemIndex < currentStep);
                    });
                };

                const validateCurrentStep = async () => {
                    const fields = Array.from(steps[currentStep].querySelectorAll('input, select, textarea'));
                    const isValid = await validateFields(fields);

                    if (!isValid) {
                        showValidationSummary('Please correct the highlighted fields in this step before continuing.');
                    }

                    return isValid;
                };

                const stateSelect = form.querySelector('[data-state-of-origin]');
                const lgaSelect = form.querySelector('[data-local-government-area]');

                const resetLgaOptions = (placeholder = 'Select LGA') => {
                    if (!lgaSelect) {
                        return;
                    }

                    lgaSelect.innerHTML = '';
                    lgaSelect.append(new Option(placeholder, ''));
                };

                const populateLgas = async (selectedLga = '') => {
                    if (!stateSelect || !lgaSelect) {
                        return;
                    }

                    const selectedOption = stateSelect.selectedOptions[0];
                    const lgaUrl = selectedOption?.dataset.lgaUrl || '';

                    resetLgaOptions(lgaUrl ? 'Loading LGAs...' : 'Select state first');
                    lgaSelect.disabled = true;

                    if (!lgaUrl) {
                        return;
                    }

                    try {
                        const response = await fetch(lgaUrl, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Unable to load LGAs.');
                        }

                        const payload = await response.json();
                        const localGovernmentAreas = Array.isArray(payload.data) ? payload.data : [];

                        resetLgaOptions(localGovernmentAreas.length > 0 ? 'Select LGA' : 'No LGAs available');

                        localGovernmentAreas.forEach((lga) => {
                            const option = new Option(lga.name, lga.name, false, lga.name === selectedLga);
                            option.dataset.lgaId = lga.id;
                            option.dataset.lgaSlug = lga.slug;
                            lgaSelect.append(option);
                        });

                        lgaSelect.disabled = localGovernmentAreas.length === 0;
                    } catch (error) {
                        resetLgaOptions('Unable to load LGAs');
                        lgaSelect.disabled = true;
                        console.error(error);
                    } finally {
                        updateSummary();
                    }
                };

                if (stateSelect && lgaSelect) {
                    populateLgas(lgaSelect.dataset.selectedLga || lgaSelect.value || '');

                    stateSelect.addEventListener('change', () => {
                        lgaSelect.dataset.selectedLga = '';
                        populateLgas();
                        updateSummary();
                    });

                    lgaSelect.addEventListener('change', updateSummary);
                }

                const documentsContainer = form.querySelector('[data-education-documents]');
                const addDocumentButton = form.querySelector('[data-add-document]');
                const documentTemplate = document.getElementById('education-document-template');

                const refreshDocumentRows = () => {
                    if (!documentsContainer) {
                        return;
                    }

                    const rows = Array.from(documentsContainer.querySelectorAll('[data-education-document]'));

                    rows.forEach((row, index) => {
                        const number = index + 1;
                        const type = row.querySelector('[data-document-type]');
                        const file = row.querySelector('[data-document-file]');
                        const typeLabel = row.querySelector('[data-document-type-label]');
                        const fileLabel = row.querySelector('[data-document-file-label]');
                        const fileHelp = row.querySelector('[data-document-file-help]');
                        const fileFeedback = row.querySelector('[data-validation-message]');
                        const removeButton = row.querySelector('[data-remove-document]');
                        const title = row.querySelector('[data-document-title]');

                        if (title) {
                            title.textContent = `Qualification Document ${number}`;
                        }

                        if (type) {
                            type.name = `education_documents[${index}][type]`;
                            type.id = `education-document-type-${index}`;
                        }

                        if (file) {
                            file.name = `education_documents[${index}][file]`;
                            file.id = `education-document-file-${index}`;
                        }

                        if (typeLabel) {
                            typeLabel.setAttribute('for', `education-document-type-${index}`);
                        }

                        if (fileLabel) {
                            fileLabel.setAttribute('for', `education-document-file-${index}`);
                        }

                        if (fileHelp) {
                            fileHelp.id = `education-document-file-help-${index}`;
                        }

                        if (fileFeedback) {
                            fileFeedback.id = `education-document-file-feedback-${index}`;
                        }

                        if (file && fileHelp && fileFeedback) {
                            file.setAttribute('aria-describedby', `${fileHelp.id} ${fileFeedback.id}`);
                        }

                        if (removeButton) {
                            removeButton.hidden = rows.length === 1;
                        }
                    });

                    updateSummary();

                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                };

                addDocumentButton?.addEventListener('click', () => {
                    if (!documentTemplate || !documentsContainer) {
                        return;
                    }

                    const currentRows = documentsContainer.querySelectorAll('[data-education-document]').length;

                    if (currentRows >= 10) {
                        return;
                    }

                    documentsContainer.append(documentTemplate.content.firstElementChild.cloneNode(true));
                    refreshDocumentRows();
                    documentsContainer.lastElementChild?.querySelector('select')?.focus();
                });

                documentsContainer?.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-document]');

                    if (!removeButton) {
                        return;
                    }

                    const rows = documentsContainer.querySelectorAll('[data-education-document]');

                    if (rows.length <= 1) {
                        return;
                    }

                    removeButton.closest('[data-education-document]')?.remove();
                    refreshDocumentRows();
                });

                function fieldValue(name) {
                    return form.elements[name]?.value?.trim() || '';
                }

                function updateSummary() {
                    const fullName = [fieldValue('first_name'), fieldValue('last_name')].filter(Boolean).join(' ');
                    const origin = [fieldValue('local_government_area'), fieldValue('state_of_origin')].filter(Boolean)
                        .join(', ');
                    const documentCount = documentsContainer?.querySelectorAll('[data-education-document]').length || 0;

                    const summary = {
                        '[data-summary-full-name]': fullName,
                        '[data-summary-contact]': fieldValue('phone'),
                        '[data-summary-origin]': origin,
                        '[data-summary-nationality]': fieldValue('nationality'),
                        '[data-summary-documents]': String(documentCount),
                    };

                    Object.entries(summary).forEach(([selector, value]) => {
                        const target = document.querySelector(selector);

                        if (target) {
                            target.textContent = value || 'Not provided';
                        }
                    });
                }

                form.addEventListener('click', async (event) => {
                    if (event.target.closest('[data-wizard-next]')) {
                        if (await validateCurrentStep()) {
                            showStep(currentStep + 1);
                            updateSummary();
                        }
                    }

                    if (event.target.closest('[data-wizard-previous]')) {
                        showStep(currentStep - 1);
                        updateSummary();
                    }
                });

                form.addEventListener('input', (event) => {
                    delete form.dataset.validationPassed;
                    updateSummary();

                    if (event.target.matches('input:not([type="file"]), select, textarea')) {
                        validateField(event.target);
                    }
                });

                form.addEventListener('change', async (event) => {
                    delete form.dataset.validationPassed;
                    updateSummary();

                    if (event.target.matches('input, select, textarea')) {
                        await validateField(event.target);
                    }
                });

                profileImageInput?.addEventListener('change', async () => {
                    if (!profileImageInput.files?.length) {
                        setProfilePreviewStatus('Choose a photo to preview it before submission.');
                        return;
                    }

                    await validateFileField(profileImageInput);
                });

                form.addEventListener('submit', async (event) => {
                    if (form.dataset.confirmed === 'true' || form.dataset.validationPassed === 'true') {
                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();

                    const allFields = steps.flatMap((step) => Array.from(step.querySelectorAll('input, select, textarea')));
                    const isValid = await validateFields(allFields);

                    if (!isValid) {
                        const invalidStepIndex = steps.findIndex((step) => step.querySelector('.is-invalid'));

                        if (invalidStepIndex >= 0) {
                            showStep(invalidStepIndex);
                        }

                        showValidationSummary('Please correct the highlighted fields before submitting your application.');
                        return;
                    }

                    form.dataset.validationPassed = 'true';
                    form.requestSubmit();
                });

                refreshDocumentRows();
                updateSummary();
                showStep(0);
            })();
        