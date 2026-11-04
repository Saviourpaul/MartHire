<section class="nw-admin-applicant-profile__section nw-admin-document-section" aria-labelledby="applicant-documents-{{ $managedUser->id }}-heading"
    x-data="adminUserDocumentViewer(@js($documents->values()))">
    <header class="nw-admin-document-section__header">
        <div>
            <h4 id="applicant-documents-{{ $managedUser->id }}-heading">{{ __('Documents') }}</h4>
        </div>
        <span class="nw-admin-document-section__count">{{ trans_choice(':count file|:count files', $documents->count(), ['count' => $documents->count()]) }}</span>
    </header>

    @if ($documents->isEmpty())
        <p class="nw-admin-applicant-profile__empty">{{ __('No documents uploaded.') }}</p>
    @else
        <div class="nw-admin-document-viewer">
            <div class="nw-admin-document-viewer__list" role="group" aria-label="{{ __('Applicant documents') }}">
                <template x-for="document in documents" :key="document.id">
                    <button type="button" class="nw-admin-document-viewer__item"
                        x-bind:class="{ 'is-active': selectedId === document.id }"
                        x-bind:aria-pressed="(selectedId === document.id).toString()"
                        x-on:click="select(document.id)">
                        <span class="nw-admin-document-viewer__item-title" x-text="document.label"></span>
                        <span class="nw-admin-document-viewer__item-meta" x-text="[document.filename, document.size].filter(Boolean).join(' · ')"></span>
                        <span class="nw-admin-document-viewer__item-context" x-text="document.contexts.join(' · ')"></span>
                        <span class="nw-admin-document-viewer__item-state" x-text="document.available ? (document.previewable ? @js(__('Preview available')) : @js(__('Download available'))) : @js(__('File unavailable'))"></span>
                    </button>
                </template>
            </div>

            <div class="nw-admin-document-viewer__preview" aria-live="polite">
                <template x-if="! activeDocument">
                    <p class="nw-admin-document-viewer__empty">{{ __('Select a document to view it here.') }}</p>
                </template>

                <template x-if="activeDocument">
                    <div class="nw-admin-document-viewer__selected">
                        <header class="nw-admin-document-viewer__toolbar">
                            <div>
                                <strong x-text="activeDocument.label"></strong>
                                <small x-text="activeDocument.filename"></small>
                            </div>
                            <a class="nw-button nw-button--outline nw-button--small"
                                x-show="activeDocument.available" x-bind:href="activeDocument.download_url">
                                {{ __('Download') }}
                            </a>
                        </header>

                        <template x-if="! activeDocument.available">
                            <p class="nw-admin-document-viewer__empty">{{ __('This file is no longer available.') }}</p>
                        </template>
                        <template x-if="activeDocument.available && ! activeDocument.previewable">
                            <p class="nw-admin-document-viewer__empty">{{ __('This file type cannot be previewed in the browser. Download it to view the document.') }}</p>
                        </template>
                        <template x-if="activeDocument.available && activeDocument.previewable">
                            <iframe class="nw-admin-document-viewer__frame" x-bind:src="activeDocument.preview_url"
                                x-bind:title="activeDocument.filename" loading="lazy" referrerpolicy="no-referrer"></iframe>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    @endif
</section>
