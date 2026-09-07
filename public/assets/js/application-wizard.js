(() => {
                const form = document.querySelector('[data-application-wizard]');
                if (!form) return;
                const steps = [...form.querySelectorAll('[data-wizard-step]')];
                const previous = form.querySelector('[data-wizard-previous]'),
                    next = form.querySelector('[data-wizard-next]'),
                    submit = form.querySelector('[data-wizard-submit]'),
                    spinner = form.querySelector('[data-submit-spinner]'),
                    status = form.querySelector('[data-progress-status]'),
                    progress = form.querySelector('[data-overall-progress]'),
                    overallBar = form.querySelector('[data-overall-bar]'),
                    overallPercent = form.querySelector('[data-overall-percent]'),
                    progressSteps = [...form.querySelectorAll('[data-progress-step]')],
                    summaryBox = form.querySelector('[data-validation-summary]'),
                    docs = form.querySelector('[data-education-documents]'),
                    addDoc = form.querySelector('[data-add-document]'),
                    template = document.getElementById('education-document-template'),
                    state = form.querySelector('[data-state-of-origin]'),
                    lga = form.querySelector('[data-local-government-area]'),
                    preview = document.getElementById('profile-image-preview'),
                    previewFrame = form.querySelector('[data-profile-preview-frame]'),
                    previewStatus = form.querySelector('[data-profile-preview-status]');
                const namePattern = /^[\p{L}\s'-]+$/u,
                    phonePattern = /^\+?[0-9 .()-]{7,20}$/,
                    zipcodePattern = /^[A-Za-z0-9 -]{3,20}$/;
                const initialStep = Number.parseInt(form.dataset.initialStep || '0', 10);
                let current = Number.isInteger(initialStep) ? initialStep : 0,
                    completedSteps = Number.parseInt(form.dataset.completedSteps || '0', 10),
                    previewUrl = null;
                completedSteps = Number.isInteger(completedSteps) ? Math.max(0, completedSteps) : 0;
                const fields = step => [...step.querySelectorAll('input, select, textarea')];
                const labelFor = field => (field.id ? form.querySelector(`label[for="${field.id}"]`) : field.closest('div')
                    ?.querySelector('label'))?.textContent?.replace('*', '').trim() || 'This field';
                const errorFor = field => field.closest('div')?.querySelector('[data-validation-message]');
                const showError = (field, message) => {
                    field.classList.add('border-error-500');
                    field.setAttribute('aria-invalid', 'true');
                    const error = errorFor(field);
                    if (error) {
                        error.textContent = message;
                        error.classList.remove('hidden');
                        error.classList.add('block');
                    }
                };
                const clearError = field => {
                    field.classList.remove('border-error-500');
                    field.removeAttribute('aria-invalid');
                    const error = errorFor(field);
                    if (error) {
                        error.textContent = '';
                        error.classList.add('hidden');
                        error.classList.remove('block');
                    }
                };
                const allowed = field => {
                    try {
                        return JSON.parse(field.dataset.allowedTypes || '[]').map(type => String(type).toLowerCase());
                    } catch {
                        return [];
                    }
                };
                const imageSize = file => new Promise((resolve, reject) => {
                    const image = new Image(),
                        url = URL.createObjectURL(file);
                    image.onload = () => {
                        URL.revokeObjectURL(url);
                        resolve({
                            width: image.naturalWidth,
                            height: image.naturalHeight
                        });
                    };
                    image.onerror = () => {
                        URL.revokeObjectURL(url);
                        reject();
                    };
                    image.src = url;
                });
                const resetProfilePreview = () => {
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                    previewUrl = null;
                    if (preview) preview.src = preview.dataset.defaultSrc || preview.src;
                    previewFrame?.classList.remove('ring-2', 'ring-brand-500', 'ring-offset-2', 'dark:ring-offset-gray-900');
                    if (previewStatus) previewStatus.textContent = 'Choose a photo to preview it before submission.';
                };
                const updateProfilePreview = (file, size) => {
                    if (!preview) return;
                    if (previewUrl) URL.revokeObjectURL(previewUrl);
                    previewUrl = URL.createObjectURL(file);
                    preview.src = previewUrl;
                    previewFrame?.classList.add('ring-2', 'ring-brand-500', 'ring-offset-2', 'dark:ring-offset-gray-900');
                    if (previewStatus) previewStatus.textContent = `${file.name} selected - ${size.width}x${size.height}px.`;
                };
                async function validateFile(field) {
                    const file = field.files?.[0];
                    if (!file) {
                        if (field.dataset.fileKind === 'profile-image') resetProfilePreview();
                        if (field.required) {
                            showError(field, `${labelFor(field)} is required.`);
                            return false;
                        }
                        clearError(field);
                        return true;
                    }
                    const ext = (file.name.split('.').pop() || '').toLowerCase(),
                        types = allowed(field),
                        maxKb = Number(field.dataset.maxKb || 0);
                    if (types.length && !types.includes(ext)) {
                        showError(field,
                            `${labelFor(field)} must be a ${types.map(type => type.toUpperCase()).join(', ')} file.`
                        );
                        return false;
                    }
                    if (maxKb && file.size > maxKb * 1024) {
                        showError(field, `${labelFor(field)} must not be larger than ${maxKb / 1024}MB.`);
                        return false;
                    }
                    if (field.dataset.fileKind === 'profile-image') {
                        try {
                            const size = await imageSize(file),
                                minW = Number(field.dataset.minWidth || 0),
                                minH = Number(field.dataset.minHeight || 0),
                                maxW = Number(field.dataset.maxWidth || Infinity),
                                maxH = Number(field.dataset.maxHeight || Infinity);
                            if (size.width < minW || size.height < minH || size.width > maxW || size.height > maxH) {
                                showError(field,
                                    `${labelFor(field)} must be between ${minW}x${minH} and ${maxW}x${maxH} pixels.`
                                );
                                return false;
                            }
                            updateProfilePreview(file, size);
                        } catch {
                            showError(field, 'Choose a valid image that can be previewed.');
                            return false;
                        }
                    }
                    clearError(field);
                    return true;
                }

                function validateStandard(field) {
                    const value = field.value.trim();
                    if (field.required && value === '') {
                        showError(field, `${labelFor(field)} is required.`);
                        return false;
                    }
                    if (value === '') {
                        clearError(field);
                        return true;
                    }
                    if (['first_name', 'middle_name', 'last_name', 'nationality'].includes(field.name) && !namePattern.test(
                            value)) {
                        showError(field, `${labelFor(field)} may only contain letters, spaces, hyphens, and apostrophes.`);
                        return false;
                    }
                    if (field.name === 'phone' && !phonePattern.test(value)) {
                        showError(field,
                            'Enter a valid phone number using 7 to 20 digits, with an optional leading plus sign.');
                        return false;
                    }
                    if (field.name === 'zipcode' && !zipcodePattern.test(value)) {
                        showError(field, 'The zipcode may only contain letters, numbers, spaces, and hyphens.');
                        return false;
                    }
                    if (!field.checkValidity()) {
                        showError(field, field.validationMessage || `${labelFor(field)} is invalid.`);
                        return false;
                    }
                    clearError(field);
                    return true;
                }
                const validateField = field => field.type === 'file' ? validateFile(field) : Promise.resolve(
                    validateStandard(field));
                async function validateList(list) {
                    let first = null;
                    for (const field of list)
                        if (!(await validateField(field)) && !first) first = field;
                    if (first) {
                        first.focus({
                            preventScroll: false
                        });
                        return false;
                    }
                    return true;
                }
                function updateSummary() {
                    const val = name => form.elements[name]?.value?.trim() || '',
                        set = (selector, text) => {
                            const node = form.querySelector(selector);
                            if (node) node.textContent = text || 'Not provided';
                        };
                    set('[data-summary-full-name]', [val('first_name'), val('last_name')].filter(Boolean).join(' '));
                    set('[data-summary-contact]', val('phone'));
                    set('[data-summary-origin]', [val('local_government_area'), val('state_of_origin')].filter(Boolean)
                        .join(', '));
                    set('[data-summary-documents]', String(docs?.querySelectorAll('[data-education-document]').length ||
                        0));
                }

                function updateProgress() {
                    const completableSteps = Math.max(steps.length - 1, 1),
                        overall = Math.round(Math.min(completedSteps, completableSteps) / completableSteps * 100);
                    overallBar.style.width = `${overall}%`;
                    overallPercent.textContent = `${overall}%`;
                    status.textContent = `Step ${current + 1} of ${steps.length}`;
                    progress?.setAttribute('aria-valuenow', String(overall));

                    progressSteps.forEach((step, index) => {
                        const marker = step.querySelector('[data-progress-marker]'),
                            label = step.querySelector('[data-progress-label]'),
                            isCompleted = index < completedSteps,
                            isCurrent = index === current;

                        step.toggleAttribute('aria-current', isCurrent);
                        marker?.classList.toggle('border-success-500', isCompleted);
                        marker?.classList.toggle('bg-success-500', isCompleted);
                        marker?.classList.toggle('text-white', isCompleted || isCurrent);
                        marker?.classList.toggle('border-brand-500', isCurrent);
                        marker?.classList.toggle('bg-brand-500', isCurrent);
                        marker?.classList.toggle('border-gray-300', !isCompleted && !isCurrent);
                        marker?.classList.toggle('bg-white', !isCompleted && !isCurrent);
                        marker?.classList.toggle('text-gray-500', !isCompleted && !isCurrent);
                        marker?.classList.toggle('dark:border-gray-700', !isCompleted && !isCurrent);
                        marker?.classList.toggle('dark:bg-gray-900', !isCompleted && !isCurrent);
                        marker?.classList.toggle('dark:text-gray-400', !isCompleted && !isCurrent);
                        label?.classList.toggle('text-success-700', isCompleted);
                        label?.classList.toggle('dark:text-success-400', isCompleted);
                        label?.classList.toggle('text-brand-700', isCurrent);
                        label?.classList.toggle('dark:text-brand-300', isCurrent);
                        label?.classList.toggle('font-semibold', isCompleted || isCurrent);
                        label?.classList.toggle('text-gray-500', !isCompleted && !isCurrent);
                        label?.classList.toggle('dark:text-gray-400', !isCompleted && !isCurrent);
                    });
                }

                function showStep(index) {
                    current = Math.max(0, Math.min(index, steps.length - 1));
                    steps.forEach((step, i) => step.classList.toggle('hidden', i !== current));
                    previous.disabled = current === 0;
                    next.classList.toggle('hidden', current === steps.length - 1);
                    submit.classList.toggle('hidden', current !== steps.length - 1);
                    submit.classList.toggle('inline-flex', current === steps.length - 1);
                    summaryBox.classList.add('hidden');
                    updateSummary();
                    updateProgress();
                }

                function refreshRows() {
                    const rows = [...docs.querySelectorAll('[data-education-document]')];
                    rows.forEach((row, index) => {
                        const type = row.querySelector('[data-document-type]'),
                            file = row.querySelector('[data-document-file]');
                        row.querySelector('[data-document-title]').textContent =
                            `Qualification Document ${index + 1}`;
                        type.name = `education_documents[${index}][type]`;
                        type.id = `education-document-type-${index}`;
                        file.name = `education_documents[${index}][file]`;
                        file.id = `education-document-file-${index}`;
                        row.querySelector('[data-document-type-label]')?.setAttribute('for', type.id);
                        row.querySelector('[data-document-file-label]')?.setAttribute('for', file.id);
                        row.querySelector('[data-remove-document]').hidden = rows.length === 1;
                    });
                    addDoc.disabled = rows.length >= 10;
                    updateSummary();
                }
                async function populateLgas(selected = '') {
                    const url = state?.selectedOptions[0]?.dataset.lgaUrl || '';
                    lga.innerHTML = '';
                    lga.append(new Option(url ? 'Loading LGAs...' : 'Select state first', ''));
                    lga.disabled = true;
                    if (!url) return;
                    try {
                        const res = await fetch(url, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw new Error();
                        const payload = await res.json(),
                            rows = Array.isArray(payload.data) ? payload.data : [];
                        lga.innerHTML = '';
                        lga.append(new Option(rows.length ? 'Select LGA' : 'No LGAs available', ''));
                        rows.forEach(row => lga.append(new Option(row.name, row.name, false, row.name === selected)));
                        lga.disabled = rows.length === 0;
                    } catch {
                        lga.innerHTML = '';
                        lga.append(new Option('Unable to load LGAs', ''));
                    }
                }
                next.addEventListener('click', async () => {
                    if (await validateList(fields(steps[current]))) {
                        completedSteps = Math.max(completedSteps, current + 1);
                        showStep(current + 1);
                    }
                    else {
                        summaryBox.textContent =
                            'Please correct the highlighted fields in this step before continuing.';
                        summaryBox.classList.remove('hidden');
                    }
                });
                previous.addEventListener('click', () => showStep(current - 1));
                form.addEventListener('input', event => {
                    delete form.dataset.validationPassed;
                    delete form.dataset.confirmed;
                    if (event.target.matches('input:not([type="file"]), select, textarea')) validateField(event
                        .target);
                    updateSummary();
                });
                form.addEventListener('change', async event => {
                    delete form.dataset.validationPassed;
                    delete form.dataset.confirmed;
                    if (event.target === state) populateLgas();
                    if (event.target.matches('input, select, textarea')) await validateField(event.target);
                    updateSummary();
                });
                addDoc?.addEventListener('click', () => {
                    if (docs.querySelectorAll('[data-education-document]').length >= 10) return;
                    docs.append(template.content.firstElementChild.cloneNode(true));
                    refreshRows();
                    docs.lastElementChild?.querySelector('select')?.focus();
                });
                docs?.addEventListener('click', event => {
                    const remove = event.target.closest('[data-remove-document]');
                    if (!remove || docs.querySelectorAll('[data-education-document]').length <= 1) return;
                    remove.closest('[data-education-document]').remove();
                    refreshRows();
                });
                form.addEventListener('submit', async event => {
                    if (form.dataset.confirmed === 'true') {
                        submit.disabled = true;
                        spinner.classList.remove('hidden');
                        return;
                    }
                    if (form.dataset.validationPassed === 'true') {
                        return;
                    }
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    if (!(await validateList(steps.flatMap(fields)))) {
                        const invalid = steps.findIndex(step => step.querySelector('[aria-invalid="true"]'));
                        showStep(invalid >= 0 ? invalid : 0);
                        summaryBox.textContent =
                            'Please correct the highlighted fields before submitting your application.';
                        summaryBox.classList.remove('hidden');
                        return;
                    }
                    form.dataset.validationPassed = 'true';
                    form.requestSubmit();
                });
                populateLgas(lga?.dataset.selectedLga || lga?.value || '');
                refreshRows();
                showStep(current);
            })();
