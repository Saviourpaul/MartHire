export function registerTableActionMenu(Alpine) {
    Alpine.data('tableActionMenu', () => ({
        open: false,
        menuStyle: 'top: 0; left: 0;',
        toggle() {
            if (this.open) {
                this.close();
                return;
            }

            this.openMenu();
        },
        openMenu(last = false) {
            this.open = true;
            this.$nextTick(() => {
                this.reposition();
                const items = this.menuItems();
                (last ? items.at(-1) : items[0])?.focus();
            });
        },
        reposition() {
            if (! this.open || ! this.$refs.menu || ! this.$refs.trigger) return;

            const trigger = this.$refs.trigger.getBoundingClientRect();
            const menu = this.$refs.menu.getBoundingClientRect();
            const margin = 8;
            const gap = 6;
            const viewportWidth = document.documentElement.clientWidth;
            const left = Math.max(margin, Math.min(trigger.right - menu.width, viewportWidth - menu.width - margin));
            const below = trigger.bottom + gap + menu.height <= window.innerHeight - margin;
            const top = below ? trigger.bottom + gap : Math.max(margin, trigger.top - menu.height - gap);

            this.menuStyle = `top: ${top}px; left: ${left}px;`;
        },
        close() {
            this.open = false;
        },
        closeAndFocus() {
            this.close();
            this.$nextTick(() => this.$refs.trigger?.focus({ preventScroll: true }));
        },
        closeOnScroll() {
            if (this.open) this.closeAndFocus();
        },
        tabOut(event) {
            const focusable = [...document.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                .filter((element) => element.getClientRects().length > 0 && ! this.$refs.menu.contains(element));
            const triggerIndex = focusable.indexOf(this.$refs.trigger);
            const target = focusable[triggerIndex + (event.shiftKey ? -1 : 1)];

            this.close();
            if (target) {
                event.preventDefault();
                this.$nextTick(() => target.focus());
            }
        },
        menuItems() {
            return [...this.$refs.menu.querySelectorAll('[role="menuitem"]')]
                .filter((item) => ! item.hasAttribute('disabled') && item.getClientRects().length > 0);
        },
        focusNext(direction) {
            const items = this.menuItems();
            if (! items.length) return;

            const currentIndex = items.indexOf(document.activeElement);
            const nextIndex = currentIndex < 0
                ? (direction > 0 ? 0 : items.length - 1)
                : (currentIndex + direction + items.length) % items.length;

            items[nextIndex].focus();
        },
        focusEdge(edge) {
            const items = this.menuItems();
            (edge === 'last' ? items.at(-1) : items[0])?.focus();
        },
        openModal(name, focusTarget = null) {
            this.close();
            this.$nextTick(() => {
                this.$refs.trigger?.focus();
                window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
                if (focusTarget) {
                    this.$nextTick(() => document.getElementById(focusTarget)?.focus());
                }
            });
        },
    }));
}
