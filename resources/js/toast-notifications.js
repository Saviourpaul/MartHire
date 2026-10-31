export function registerToastNotifications(Alpine) {
    Alpine.data('toastNotification', ({ duration = 6500 } = {}) => {
        let timer = null;
        let startedAt = 0;
        let remaining = duration;

        return {
            open: true,
            hovered: false,
            focused: false,

            init() {
                this.resume();
            },

            pause() {
                if (timer === null) return;

                clearTimeout(timer);
                timer = null;
                remaining = Math.max(0, remaining - (performance.now() - startedAt));
            },

            resume() {
                if (timer !== null || !this.open || this.hovered || this.focused || duration === 0) return;

                startedAt = performance.now();
                timer = setTimeout(() => {
                    timer = null;
                    this.dismiss();
                }, remaining);
            },

            dismiss() {
                if (timer !== null) clearTimeout(timer);
                timer = null;
                this.open = false;
            },

            destroy() {
                if (timer !== null) clearTimeout(timer);
                timer = null;
            },
        };
    });
}
