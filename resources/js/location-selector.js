const instances = new WeakMap();

export function initLocationSelector(form) {
    if (instances.has(form)) return instances.get(form);
    const fields = ["country_code", "state", "city"];
    const selects = Object.fromEntries(
        fields.map((name) => [
            name,
            form.querySelector(`[data-location-${name}]`),
        ]),
    );
    if (!fields.every((name) => selects[name] instanceof HTMLSelectElement))
        return null;
    const controllers = new Map();
    let revision = 0;
    const required = form.dataset.locationRequired !== "false";
    const notify = () =>
        form.dispatchEvent(new CustomEvent("location:updated"));
    const status = (field, message, retry = false) => {
        const text = form.querySelector(`[data-location-status="${field}"]`);
        if (text) text.textContent = message;
        const button = form.querySelector(`[data-location-retry="${field}"]`);
        if (button) button.hidden = !retry;
    };
    const reset = (field, placeholder) => {
        controllers.get(field)?.abort();
        controllers.delete(field);
        selects[field].replaceChildren(new Option(placeholder, ""));
        selects[field].disabled = true;
        selects[field].required = false;
        selects[field].dataset.loading = "false";
        selects[field].dataset.unavailable = "false";
        selects[field].setCustomValidity("");
        const requiredMark = form.querySelector(
            `[data-location-required-mark="${field}"]`,
        );
        if (requiredMark) requiredMark.hidden = true;
        status(field, "");
    };
    async function load(field, selected = "") {
        const currentRevision = revision;
        const select = selects[field];
        const endpoint =
            field === "country_code"
                ? "countries"
                : field === "state"
                  ? "states"
                  : "cities";
        const country = selects.country_code.value;
        const state = selects.state.value;
        if (field !== "country_code" && !country) return;
        if (field === "city" && !state) return;
        reset(field, `Loading ${endpoint}…`);
        select.dataset.loading = "true";
        status(field, `Loading ${endpoint}…`);
        const request = new AbortController();
        controllers.set(field, request);
        const url = new URL(
            form.dataset[
                `locations${endpoint[0].toUpperCase()}${endpoint.slice(1)}`
            ],
            window.location.origin,
        );
        if (field !== "country_code") url.searchParams.set("country", country);
        if (field === "city") url.searchParams.set("state", state);
        try {
            const response = await fetch(url, {
                signal: request.signal,
                credentials: "omit",
                headers: { Accept: "application/json" },
            });
            const payload = await response.json();
            if (
                !response.ok ||
                payload.meta?.available !== true ||
                !Array.isArray(payload.data) ||
                !payload.data.every(
                    (row) =>
                        typeof row.value === "string" &&
                        typeof row.label === "string",
                )
            ) {
                throw new Error("Location unavailable");
            }
            if (
                currentRevision !== revision ||
                controllers.get(field) !== request
            )
                return;
            const empty = payload.data.length === 0;
            if (field === "country_code" && empty)
                throw new Error("No countries available");
            select.replaceChildren(
                new Option(
                    empty
                        ? `No ${endpoint} listed`
                        : `Select ${field === "country_code" ? "country" : field}`,
                    "",
                ),
            );
            payload.data.forEach((row) =>
                select.append(
                    new Option(
                        row.label,
                        row.value,
                        false,
                        row.value === selected,
                    ),
                ),
            );
            select.disabled = empty;
            select.required = required && !empty;
            const requiredMark = form.querySelector(
                `[data-location-required-mark="${field}"]`,
            );
            if (requiredMark) requiredMark.hidden = !select.required;
            select.dataset.unavailable = "false";
            select.setCustomValidity("");
            status(
                field,
                empty
                    ? `No ${endpoint} are listed for this selection. You may continue without a ${field}.`
                    : selected &&
                        !payload.data.some((row) => row.value === selected)
                      ? "Your previous selection is no longer listed. Please choose again."
                      : ["snapshot", "stale"].includes(payload.meta.source)
                        ? "Using saved location data."
                        : "",
            );
            return true;
        } catch (error) {
            if (
                error.name === "AbortError" ||
                currentRevision !== revision ||
                controllers.get(field) !== request
            )
                return;
            select.replaceChildren(
                new Option(`Unable to load ${endpoint}`, ""),
            );
            // Keep failure controls enabled: native validation must not silently skip them.
            select.disabled = false;
            select.required = required;
            const requiredMark = form.querySelector(
                `[data-location-required-mark="${field}"]`,
            );
            if (requiredMark) requiredMark.hidden = !required;
            select.dataset.unavailable = "true";
            select.setCustomValidity(
                "Location data is unavailable. Please retry before continuing.",
            );
            status(field, "Unable to load this list. Please retry.", true);
        } finally {
            if (
                currentRevision === revision &&
                controllers.get(field) === request
            ) {
                select.dataset.loading = "false";
                controllers.delete(field);
                notify();
            }
        }
        return false;
    }
    async function restore(values = {}) {
        revision++;
        fields.forEach((field) =>
            reset(
                field,
                field === "country_code"
                    ? "Select country"
                    : "Select parent first",
            ),
        );
        const requestRevision = revision;
        if (
            !(await load("country_code", values.country_code || "")) ||
            requestRevision !== revision
        )
            return;
        if (!selects.country_code.value) return;
        if (
            !(await load("state", values.state || "")) ||
            requestRevision !== revision
        )
            return;
        if (selects.state.value) await load("city", values.city || "");
    }
    selects.country_code.addEventListener("change", () => {
        revision++;
        reset("state", "Select country first");
        reset("city", "Select state first");
        notify();
        load("state");
    });
    selects.state.addEventListener("change", () => {
        revision++;
        reset("city", "Select state first");
        notify();
        load("city");
    });
    fields.forEach((field) =>
        form
            .querySelector(`[data-location-retry="${field}"]`)
            ?.addEventListener("click", () => load(field)),
    );
    form.addEventListener("location:reset", (event) => restore(event.detail));
    form.addEventListener("submit", (event) => {
        if (
            fields.some(
                (field) =>
                    selects[field].dataset.loading === "true" ||
                    selects[field].dataset.unavailable === "true",
            )
        ) {
            event.preventDefault();
            status(
                "country_code",
                "Wait for the location lists to load, or retry, before saving.",
            );
        }
    });
    const instance = { restore, ready: null };
    instances.set(form, instance);
    instance.ready = restore(
        Object.fromEntries(
            fields.map((name) => [name, selects[name].dataset.selected]),
        ),
    );
    return instance;
}

const boot = () =>
    document
        .querySelectorAll("[data-location-selector]")
        .forEach(initLocationSelector);
if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", boot, { once: true });
else boot();
