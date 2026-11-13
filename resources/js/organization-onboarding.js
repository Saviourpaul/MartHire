import { organizationImageUploadState } from "./organization-image-upload";

export function registerOrganizationOnboarding(Alpine) {
    Alpine.data("organizationOnboarding", (initial = {}) => ({
        companyName: initial.companyName || "",
        about: initial.about || "",
        industry: initial.industry || "",
        size: initial.size || "",
        primary: /^#[\da-f]{6}$/i.test(initial.primary || "")
            ? initial.primary
            : "#006b43",
        accent: /^#[\da-f]{6}$/i.test(initial.accent || "")
            ? initial.accent
            : "#c9f542",
        ...organizationImageUploadState({
            savedUrl: initial.logo || "",
            removeSavedImage: initial.removeSavedImage || false,
            maxBytes: 2 * 1024 * 1024,
            allowedTypes: ["image/jpeg", "image/png", "image/webp"],
            allowedExtensions: ["jpg", "jpeg", "png", "webp"],
            messages: initial.imageMessages,
        }),
        invitees: (Array.isArray(initial.invitees) && initial.invitees.length
            ? initial.invitees
            : [{ email: "", role: "recruiter" }]
        ).map((invitee, index) => ({
            key: index + 1,
            email: invitee.email || "",
            role: invitee.role || "recruiter",
        })),
        nextKey:
            (Array.isArray(initial.invitees) ? initial.invitees.length : 0) + 1,
        replaceInvitees(invitees) {
            const rows = Array.isArray(invitees) && invitees.length
                ? invitees
                : [{ email: "", role: "recruiter" }];

            this.invitees = rows.map((invitee, index) => ({
                key: index + 1,
                email: invitee.email || "",
                role: invitee.role || "recruiter",
            }));
            this.nextKey = this.invitees.length + 1;
        },
        addInvitee() {
            if (this.invitees.length < 10)
                this.invitees.push({
                    key: this.nextKey++,
                    email: "",
                    role: "recruiter",
                });
        },
        removeInvitee(index) {
            if (this.invitees.length > 1) this.invitees.splice(index, 1);
        },
    }));
}

const jsonHeaders = {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
};

function notify(message, type = "info") {
    if (message) window.Marthire?.notify?.(message, { type });
}

function inputNameFromValidationKey(key) {
    return key.replace(/\.(\d+)\.(\w+)/g, "[$1][$2]").replace(/\.(\w+)/g, "[$1]");
}

function clearValidation(root) {
    root.querySelectorAll("[data-onboarding-async-error]").forEach((error) => error.remove());
    root.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute("aria-invalid"));

    const feedback = root.querySelector("[data-onboarding-feedback]");
    const list = feedback?.querySelector("[data-onboarding-feedback-list]");
    if (list) list.replaceChildren();
    if (feedback) feedback.hidden = true;
}

function showValidationErrors(root, form, errors) {
    clearValidation(root);

    const feedback = root.querySelector("[data-onboarding-feedback]");
    const title = feedback?.querySelector("[data-onboarding-feedback-title]");
    const list = feedback?.querySelector("[data-onboarding-feedback-list]");
    const entries = Object.entries(errors || {}).filter(([, messages]) => messages?.length);
    if (!feedback || !title || !list || !entries.length) return;

    title.textContent = root.dataset.validationTitle || "Please review the highlighted fields.";

    Object.entries(errors || {}).forEach(([key, fieldMessages]) => {
        const name = inputNameFromValidationKey(key);
        (Array.isArray(fieldMessages) ? fieldMessages : [fieldMessages]).filter(Boolean).forEach((message) => {
            const item = document.createElement("li");
            item.dataset.onboardingErrorField = name;
            item.textContent = message;
            list.append(item);
        });

        const field = Array.from(form.elements).find((element) => element.name === name);
        if (!field) return;

        field.setAttribute("aria-invalid", "true");
        const error = document.createElement("p");
        error.className = "nw-field__error";
        error.dataset.onboardingAsyncError = name;
        error.setAttribute("role", "alert");
        error.textContent = Array.isArray(fieldMessages) ? fieldMessages[0] : String(fieldMessages);

        const container = field.closest(".nw-field") || field.closest("fieldset") || field.parentElement;
        container?.append(error);
    });

    feedback.hidden = false;
    feedback.focus({ preventScroll: false });
}

function renderInviteResults(root, results) {
    const list = root.querySelector("[data-invite-results]");
    if (!list) return;

    list.replaceChildren();
    (Array.isArray(results) ? results : []).forEach((result) => {
        const item = document.createElement("li");
        item.className = result.success ? "is-success" : "is-error";

        const email = document.createElement("strong");
        email.textContent = result.email || "";
        const message = document.createElement("span");
        message.textContent = result.message || "";
        item.append(email, message);
        list.append(item);
    });

    list.hidden = list.childElementCount === 0;
}

function setBusy(root, form, busy) {
    root.setAttribute("aria-busy", busy ? "true" : "false");
    const pending = root.querySelector("[data-onboarding-pending]");
    if (pending) pending.textContent = busy ? (root.dataset.savingMessage || "Saving your progress…") : "";

    root.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        if (busy) {
            button.dataset.onboardingWasDisabled = button.disabled ? "true" : "false";
            button.disabled = true;
        } else if (button.dataset.onboardingWasDisabled !== undefined) {
            button.disabled = button.dataset.onboardingWasDisabled === "true";
            delete button.dataset.onboardingWasDisabled;
        }
    });

    if (form) form.setAttribute("aria-busy", busy ? "true" : "false");
}

async function readJson(response) {
    try {
        return await response.json();
    } catch {
        return {};
    }
}

async function fetchCurrentStage(url) {
    const response = await fetch(url, {
        method: "GET",
        credentials: "same-origin",
        cache: "no-store",
        headers: jsonHeaders,
    });
    const payload = await readJson(response);

    if (payload.redirectUrl) {
        window.location.assign(payload.redirectUrl);
        return null;
    }
    if (!response.ok || !payload.stageHtml) {
        throw new Error(payload.message || "The current onboarding step could not be loaded.");
    }

    return payload;
}

function updateProgress(payload) {
    const progress = document.querySelector("[data-onboarding-progress]");
    if (!progress) return;

    const label = progress.querySelector("[data-onboarding-progress-label]");
    const fill = progress.querySelector("[data-onboarding-progress-fill]");
    if (label) label.textContent = payload.progressLabel || "";
    if (fill) fill.style.width = `${Math.max(0, Math.min(100, Number(payload.progressPercent) || 0))}%`;
    progress.setAttribute("aria-valuenow", String(payload.progressValue || 1));
    progress.setAttribute("aria-valuemax", String(payload.progressMax || 1));
}

function replaceStage(payload) {
    const current = document.querySelector("[data-organization-onboarding]");
    if (!current || typeof payload.stageHtml !== "string") {
        throw new Error("The onboarding step response was incomplete.");
    }

    const parsed = new DOMParser().parseFromString(payload.stageHtml, "text/html");
    const next = parsed.querySelector("[data-organization-onboarding]");
    if (!next) throw new Error("The onboarding step could not be displayed.");

    next.classList.add("is-entering");
    current.replaceWith(next);
    updateProgress(payload);

    const heading = next.querySelector("[data-onboarding-heading]");
    heading?.focus({ preventScroll: false });
}

function showInviteResults(root, payload) {
    renderInviteResults(root, payload.inviteResults);
    if (Array.isArray(payload.retryInvitees)) {
        window.dispatchEvent(new CustomEvent("organization-onboarding-retry-invitees", {
            detail: payload.retryInvitees,
        }));
    }
    notify(payload.message, payload.type || "warning");
}

export function initializeOrganizationOnboardingTransitions() {
    if (!window.fetch || !window.DOMParser) return;

    document.addEventListener("submit", async (event) => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        const root = form?.closest("[data-organization-onboarding]");
        if (!form || !root || event.defaultPrevented || !form.checkValidity()) return;

        event.preventDefault();
        if (root.getAttribute("aria-busy") === "true") return;

        clearValidation(root);
        setBusy(root, form, true);

        const data = new FormData(form);
        if (event.submitter?.name) data.set(event.submitter.name, event.submitter.value);

        try {
            const response = await fetch(form.action, {
                method: (form.method || "POST").toUpperCase(),
                body: data,
                credentials: "same-origin",
                cache: "no-store",
                headers: jsonHeaders,
            });
            const payload = await readJson(response);

            if (response.status === 422) {
                showValidationErrors(root, form, payload.errors || {});
                return;
            }

            if (response.status === 409) {
                const stage = await fetchCurrentStage(root.dataset.showUrl || window.location.href);
                if (stage) replaceStage(stage);
                notify(payload.message || "Your onboarding step was updated elsewhere. The latest step is now shown.", "warning");
                return;
            }

            if (!response.ok) throw new Error(payload.message || root.dataset.requestError || "The request failed.");
            if (payload.redirectUrl) {
                window.location.assign(payload.redirectUrl);
                return;
            }

            if (Array.isArray(payload.inviteResults) && !payload.transitioned) {
                showInviteResults(root, payload);
                return;
            }

            if (!payload.showUrl) throw new Error(payload.message || "The onboarding response was incomplete.");
            const stage = await fetchCurrentStage(payload.showUrl);
            if (stage) replaceStage(stage);
            notify(payload.message, payload.type || "success");
        } catch (error) {
            notify(error?.message || root.dataset.requestError || "We could not save this step.", "error");
        } finally {
            if (root.isConnected) setBusy(root, form, false);
        }
    });

    const clearFieldError = (event) => {
        const root = event.target.closest?.("[data-organization-onboarding]");
        const fieldName = event.target.name;
        if (!root || !fieldName) return;

        const invalidFields = Array.from(root.querySelectorAll('[aria-invalid="true"]'))
            .filter((field) => field.name === fieldName);
        const inlineErrors = Array.from(root.querySelectorAll("[data-onboarding-async-error]"))
            .filter((error) => error.dataset.onboardingAsyncError === fieldName);
        const hasSummaryError = Array.from(root.querySelectorAll("[data-onboarding-error-field]"))
            .some((error) => error.dataset.onboardingErrorField === fieldName);
        if (!invalidFields.length && !inlineErrors.length && !hasSummaryError) return;

        invalidFields.forEach((field) => field.removeAttribute("aria-invalid"));
        inlineErrors.forEach((error) => error.remove());

        const feedback = root.querySelector("[data-onboarding-feedback]");
        feedback?.querySelectorAll("[data-onboarding-error-field]")
            .forEach((error) => { if (error.dataset.onboardingErrorField === fieldName) error.remove(); });
        if (feedback && !feedback.querySelector("[data-onboarding-feedback-list]")?.childElementCount) {
            feedback.hidden = true;
        }
    };

    document.addEventListener("input", clearFieldError);
    document.addEventListener("change", clearFieldError);
}
