const defaultImageTypes = ["image/jpeg", "image/png", "image/webp"];
const defaultImageExtensions = ["jpg", "jpeg", "png", "webp"];

export function organizationImageUploadState(initial = {}) {
    const savedUrl = initial.savedUrl || "";
    const maxBytes = Number(initial.maxBytes) || 2 * 1024 * 1024;
    const allowedTypes = new Set(initial.allowedTypes || defaultImageTypes);
    const allowedExtensions = new Set(
        initial.allowedExtensions || defaultImageExtensions,
    );
    const messages = initial.messages || {};
    let previewObjectUrl = null;

    const releasePreview = () => {
        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
    };

    return {
        savedUrl,
        previewUrl: initial.removeSavedImage ? "" : savedUrl,
        hasSelectedImage: false,
        selectedImageName: "",
        removeSavedImage: Boolean(initial.removeSavedImage),
        imageError: "",

        previewImage(event) {
            const input = event.currentTarget;
            const file = input.files?.[0];

            if (!file) {
                this.clearSelectedImage(input);
                return;
            }

            const extension = file.name.split(".").pop()?.toLowerCase() || "";
            const supportedType =
                allowedTypes.has(file.type) || allowedExtensions.has(extension);

            if (!supportedType) {
                this.clearSelectedImage(input);
                this.imageError =
                    messages.invalidType ||
                    "Choose an image in one of the supported formats.";
                return;
            }

            if (file.size > maxBytes) {
                this.clearSelectedImage(input);
                this.imageError =
                    messages.tooLarge || "Choose an image smaller than 2 MB.";
                return;
            }

            releasePreview();

            try {
                previewObjectUrl = URL.createObjectURL(file);
                this.previewUrl = previewObjectUrl;
                this.hasSelectedImage = true;
                this.selectedImageName = file.name;
                this.removeSavedImage = false;
                this.imageError = "";
            } catch {
                this.clearSelectedImage(input);
                this.imageError =
                    messages.previewFailed ||
                    "This image could not be previewed. Choose another file.";
            }
        },

        clearSelectedImage(input) {
            if (input) input.value = "";
            releasePreview();
            this.hasSelectedImage = false;
            this.selectedImageName = "";
            this.previewUrl = this.removeSavedImage ? "" : savedUrl;
            this.imageError = "";
        },

        imagePreviewFailed(input) {
            if (!this.hasSelectedImage) return;

            this.clearSelectedImage(input);
            this.imageError =
                messages.previewFailed ||
                "This image could not be previewed. Choose another file.";
        },

        toggleSavedImageRemoval(event, input) {
            this.removeSavedImage = Boolean(event.currentTarget.checked);

            if (this.removeSavedImage) {
                if (this.hasSelectedImage) this.clearSelectedImage(input);
                this.previewUrl = "";
            } else if (!this.hasSelectedImage) {
                this.previewUrl = savedUrl;
            }
        },

        destroy() {
            releasePreview();
        },
    };
}

export function registerOrganizationImageUpload(Alpine) {
    Alpine.data("organizationImageUpload", (initial = {}) =>
        organizationImageUploadState(initial),
    );
}
