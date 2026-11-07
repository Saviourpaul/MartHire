export function createDeferredPhoneInitializer({ Alpine, loadPhoneSupport, onError = () => {} }) {
    async function initialize(root) {
        const fields = [...(root?.querySelectorAll?.('[data-phone-field][data-phone-deferred]') || [])];
        if (!fields.length) return true;

        try {
            await loadPhoneSupport();
        } catch (error) {
            onError(error);
            return false;
        }

        fields.forEach((field) => {
            if (!field.isConnected || !field.hasAttribute('data-phone-deferred')) return;

            const input = field.querySelector('[data-phone-input]');
            const countrySelect = field.querySelector('.nw-phone__fallback');
            const currentValue = input?.value;
            const currentCountry = countrySelect?.value;

            // x-ignore kept this editor-only widget out of Alpine's first page
            // walk. Clear its ignored state before initializing it on demand.
            Alpine.destroyTree(field);
            field.removeAttribute('x-ignore');
            field.removeAttribute('data-phone-deferred');
            Alpine.initTree(field);

            // The native controls remain usable while the module downloads.
            // Carry any values entered during that time into the enhanced field.
            const phone = Alpine.$data?.(field);
            if (phone && typeof currentValue === 'string') {
                if (currentCountry) phone.phoneCountry = currentCountry;
                phone.formatPhone(currentValue, false);
            }
        });

        return true;
    }

    return { initialize };
}
