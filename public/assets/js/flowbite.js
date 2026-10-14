/* Flowbite WYSIWYG entry point. Tiptap is compiled locally by Vite. */
(() => {
    const script = document.currentScript;
    const moduleUrl = script?.dataset.editorModule;
    if (!moduleUrl) return;
    const initialize = async () => {
        try {
            const editor = await import(moduleUrl);
            editor.initializeJobDescriptionEditors();
        } catch (error) {
            // Keep the description textarea usable if the editor cannot load.
            console.error('The job description editor could not load.', error);
        }
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
