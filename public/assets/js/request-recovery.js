(function () {
    'use strict';

    async function handle(response, suppliedPayload) {
        if (!response || ![401, 403, 410, 419].includes(response.status)) {
            return null;
        }

        const rawPayload = suppliedPayload ?? (typeof response.clone === 'function'
            ? await response.clone().json().catch(() => ({}))
            : {});
        const payload = rawPayload && typeof rawPayload === 'object' ? rawPayload : {};

        if (response.status === 403 && !['verification_invalid', 'signed_invalid'].includes(payload.code)) {
            return null;
        }

        const banner = document.getElementById('request-recovery');

        if (!banner || typeof payload.recovery_url !== 'string') {
            return null;
        }

        let url;

        try {
            url = new URL(payload.recovery_url, window.location.origin);
        } catch (error) {
            return null;
        }

        if (url.origin !== window.location.origin || !['https:', 'http:'].includes(url.protocol)) {
            return null;
        }

        const message = typeof payload.message === 'string'
            ? payload.message
            : 'Your session or link is unavailable. Open a fresh page to continue.';
        banner.querySelector('[data-recovery-message]').textContent = message;
        const action = banner.querySelector('[data-recovery-action]');
        action.href = url.href;
        action.textContent = typeof payload.recovery_label === 'string' ? payload.recovery_label : 'Continue';
        banner.hidden = false;
        banner.classList.remove('hidden');
        banner.querySelector('[data-recovery-dismiss]').onclick = function () {
            banner.hidden = true;
            banner.classList.add('hidden');
        };

        return message;
    }

    window.MarthireRecovery = { handle };
})();
