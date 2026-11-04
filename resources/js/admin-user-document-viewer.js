export function registerAdminUserDocumentViewer(Alpine) {
    Alpine.data("adminUserDocumentViewer", (documents = []) => ({
        documents,
        selectedId: null,

        get activeDocument() {
            return (
                this.documents.find(
                    (document) => document.id === this.selectedId,
                ) ?? null
            );
        },

        select(id) {
            this.selectedId = id;
        },
    }));
}
