const LEGACY_PREFERENCE_KEY = 'marthire.notification-sound.enabled';

/** Account-backed notification sound preference with a same-browser sync mirror. */
export function initializeNotificationSound({
    window: win = window,
    document: doc = document,
    createAudio = (source) => new win.Audio(source),
} = {}) {
    const control = doc.querySelector?.('[data-notification-sound-toggle]') ?? null;
    const body = doc.body;
    const source = body?.dataset.notificationSoundUrl ?? '';
    const updateUrl = body?.dataset.notificationSoundUpdateUrl ?? '';
    const csrfToken = doc.querySelector?.('meta[name="csrf-token"]')?.content ?? '';
    let serverPreferenceConfigured = body?.dataset.notificationSoundPreferenceConfigured === 'true';
    let storage = null;

    try {
        storage = win.localStorage;
    } catch (_) {
        // The authenticated server preference remains available without browser storage.
    }

    let enabled = body?.dataset.notificationSoundEnabled === 'true';
    let audio = null;

    const syncControl = () => {
        control?.setAttribute?.('aria-checked', enabled ? 'true' : 'false');
    };

    const syncBody = (configured = true) => {
        if (!body?.dataset) return;
        body.dataset.notificationSoundEnabled = enabled ? 'true' : 'false';
        body.dataset.notificationSoundPreferenceConfigured = configured ? 'true' : 'false';
    };

    const mirrorPreference = () => {
        try {
            storage?.setItem(LEGACY_PREFERENCE_KEY, enabled ? 'true' : 'false');
        } catch (_) {
            // Cross-tab synchronization is optional; the server remains authoritative.
        }
    };

    const notify = (message, type) => win.Marthire?.notify?.(message, { type });

    const play = async () => {
        if (!enabled || !source) return false;

        try {
            if (!audio) {
                audio = createAudio(source);
                audio.preload = 'none';
                audio.volume = 0.65;
            }

            audio.currentTime = 0;
            await audio.play();

            return true;
        } catch (_) {
            // A blocked or unavailable sound must never interrupt notification handling.
            return false;
        }
    };

    const stop = () => {
        try {
            audio?.pause?.();
            if (audio) audio.currentTime = 0;
        } catch (_) {
            // Stopping an unavailable media element is also best-effort.
        }
    };

    const savePreference = async (next, { migration = false } = {}) => {
        try {
            if (!updateUrl || !csrfToken || typeof win.fetch !== 'function') {
                throw new Error('Notification sound settings are temporarily unavailable.');
            }

            const response = await win.fetch(updateUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ enabled: next }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok || typeof payload.enabled !== 'boolean') {
                throw new Error(payload.message || 'The notification sound preference could not be saved.');
            }

            enabled = payload.enabled;
            serverPreferenceConfigured = true;
            syncControl();
            syncBody(true);
            mirrorPreference();
            if (!migration) {
                notify(payload.message || 'Notification sound preference saved.', 'success');
            }

            return true;
        } catch (error) {
            if (!migration) {
                enabled = !next;
                syncControl();
                syncBody(serverPreferenceConfigured);
                if (!enabled) stop();
                notify(error?.message || 'Your notification sound preference could not be saved.', 'error');
            }

            return false;
        }
    };

    const onControlClick = async () => {
        if (!control || control.disabled) return;

        const previous = enabled;
        const next = !previous;
        enabled = next;
        syncControl();
        syncBody(serverPreferenceConfigured);
        control.disabled = true;
        control.setAttribute('aria-busy', 'true');

        // Start playback synchronously from the user click so browser autoplay
        // policies can recognize the user's gesture before the network request.
        if (next) void play();
        else stop();

        const saved = await savePreference(next);
        if (saved) {
            const status = doc.querySelector?.('[data-notification-sound-status]');
            if (status) status.textContent = enabled
                ? 'Notification sound is on.'
                : 'Notification sound is off.';
        } else {
            const status = doc.querySelector?.('[data-notification-sound-status]');
            if (status) status.textContent = 'Your notification sound preference could not be saved.';
        }

        control.disabled = false;
        control.removeAttribute('aria-busy');
    };

    const onStorage = (event) => {
        if (event.key !== LEGACY_PREFERENCE_KEY || event.newValue === null) return;

        enabled = event.newValue === 'true';
        syncControl();
        syncBody(serverPreferenceConfigured);
        if (!enabled) stop();
    };

    syncControl();
    control?.addEventListener?.('click', onControlClick);
    win.addEventListener?.('storage', onStorage);

    if (serverPreferenceConfigured) {
        mirrorPreference();
    } else {
        let legacyPreference = null;
        try {
            legacyPreference = storage?.getItem(LEGACY_PREFERENCE_KEY) ?? null;
        } catch (_) {
            // Ignore inaccessible browser storage.
        }

        if (legacyPreference === 'true' || legacyPreference === 'false') {
            enabled = legacyPreference === 'true';
            syncControl();
            syncBody(false);
            if (control) {
                control.disabled = true;
                control.setAttribute('aria-busy', 'true');
            }
            void savePreference(enabled, { migration: true }).finally(() => {
                if (control) {
                    control.disabled = false;
                    control.removeAttribute('aria-busy');
                }
            });
        }
    }

    return {
        play,
        isEnabled: () => enabled,
        destroy() {
            control?.removeEventListener?.('click', onControlClick);
            win.removeEventListener?.('storage', onStorage);
        },
    };
}
