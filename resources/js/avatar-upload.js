// Object URLs keep the preview local and are released when replaced or unmounted.
export function registerAvatarUpload(Alpine) {
    Alpine.data('avatarUpload', ({ savedUrl = null } = {}) => {
        let objectUrl = null;
        const release = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        };

        return {
            avatarUrl: savedUrl,
            avatarFailed: false,
            avatarFilename: '',
            previewAvatar(event) {
                release();
                const file = event.target.files?.[0];
                this.avatarFailed = false;
                this.avatarFilename = file?.name || '';
                objectUrl = file ? URL.createObjectURL(file) : null;
                this.avatarUrl = objectUrl || savedUrl;
            },
            destroy() { release(); },
        };
    });
}
