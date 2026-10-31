@props(['documents' => []])

<section class="nw-panel nw-review-documents" aria-labelledby="review-documents-heading">
    <header class="nw-panel__head"><div><span class="nw-eyebrow">{{ __('Application files') }}</span><h2 id="review-documents-heading">{{ __('Document preview') }}</h2></div><span class="nw-review-count">{{ count($documents) }}</span></header>
    @if (empty($documents))
        <div class="nw-review-document-empty"><x-icon name="file" /><h3>{{ __('No documents submitted') }}</h3><p>{{ __('Files submitted with this application will appear here.') }}</p></div>
    @else
        <div class="nw-review-documents__toolbar">
            <div class="nw-field"><label class="nw-label" for="review-document-choice">{{ __('Select document') }}</label>
                <select class="nw-select" id="review-document-choice" x-bind:value="documentId" x-on:change="selectDocument($event.target.value)">
                    @foreach ($documents as $document)<option value="{{ $document['id'] }}">{{ $document['label'] }}</option>@endforeach
                </select>
            </div>
            <template x-if="selectedDocument?.available"><a class="nw-button nw-button--outline nw-button--small" x-bind:href="selectedDocument.download_url" download><x-icon name="download" /><span>{{ __('Download') }}</span></a></template>
        </div>
        <div class="nw-review-documents__filename"><x-icon name="file" /><span x-text="selectedDocument?.label">{{ $documents[0]['label'] }}</span><span class="nw-review-private"><x-icon name="lock" />{{ __('Private') }}</span></div>
        <div class="nw-review-pdf-controls" role="group" aria-label="{{ __('PDF navigation and zoom') }}" x-show="documentState === 'ready' && documentMime === 'application/pdf'" x-cloak>
            <button type="button" class="nw-icon-button" x-on:click="changePdfPage(-1)" x-bind:disabled="pdfBusy || pdfPage <= 1" aria-label="{{ __('Previous page') }}"><x-icon name="arrow-left" /></button>
            <span role="status">{{ __('Page') }} <span x-text="pdfPage"></span> {{ __('of') }} <span x-text="pdfPages"></span></span>
            <button type="button" class="nw-icon-button" x-on:click="changePdfPage(1)" x-bind:disabled="pdfBusy || pdfPage >= pdfPages" aria-label="{{ __('Next page') }}"><x-icon name="arrow-right" /></button>
            <div class="nw-review-pdf-controls__zoom"><button type="button" class="nw-icon-button" x-on:click="zoomPdf(-.25)" x-bind:disabled="pdfBusy || pdfZoom <= .5" aria-label="{{ __('Zoom out') }}">−</button><button type="button" class="nw-button nw-button--small nw-button--outline" x-on:click="zoomPdf(0)" x-bind:disabled="pdfBusy">{{ __('Fit') }}</button><button type="button" class="nw-icon-button" x-on:click="zoomPdf(.25)" x-bind:disabled="pdfBusy || pdfZoom >= 2" aria-label="{{ __('Zoom in') }}">+</button></div>
        </div>
        <div class="nw-review-document-canvas" x-bind:aria-busy="documentState === 'loading' || pdfBusy ? 'true' : 'false'">
            <div class="nw-review-document-empty" x-show="documentState === 'loading'" role="status" x-cloak><span class="nw-review-spinner" aria-hidden="true"></span><p>{{ __('Loading document…') }}</p></div>
            <div class="nw-review-pdf-pages" x-show="documentState === 'ready' && documentMime === 'application/pdf'" x-on:resize.window.debounce.180ms="renderPdfPage()" x-cloak>
                <div x-ref="pdfContainer"><canvas x-ref="pdfCanvas" role="img" x-bind:aria-label="'Preview: ' + selectedDocument?.label + ', page ' + pdfPage"></canvas></div>
                <span class="nw-review-pdf-loading" x-show="pdfBusy" role="status">{{ __('Rendering page…') }}</span>
            </div>
            <template x-if="documentState === 'ready' && documentMime.startsWith('image/')">
                <div class="nw-review-document-image"><img x-bind:src="documentUrl" x-bind:alt="'Preview: ' + selectedDocument.label" x-on:error="documentRenderFailed()"></div>
            </template>
            <div class="nw-review-document-empty" x-show="documentState === 'unsupported'" x-cloak><x-icon name="file" /><h3>{{ __('Download to review this file') }}</h3><p>{{ __('PDF, JPG and PNG files can be previewed here. Word documents and other formats are available to download.') }}</p></div>
            <div class="nw-review-document-empty" x-show="documentState === 'unavailable'" role="status" x-cloak><x-icon name="file" /><h3>{{ __('Document unavailable') }}</h3><p>{{ __('The stored file could not be found. Please request a replacement from the candidate.') }}</p></div>
            <div class="nw-review-document-empty" x-show="documentState === 'error'" role="alert" x-cloak><x-icon name="alert-triangle" /><h3>{{ __('Preview unavailable') }}</h3><p x-text="documentError"></p><button type="button" class="nw-button nw-button--outline nw-button--small" x-on:click="loadDocument()">{{ __('Try again') }}</button></div>
            <noscript><div class="nw-review-document-empty"><h3>{{ __('Application documents') }}</h3><p>{{ __('Enable JavaScript to preview files in this workspace, or download a document below.') }}</p><ul>@foreach($documents as $document)<li><a class="nw-link" href="{{ $document['download_url'] }}">{{ $document['label'] }}</a></li>@endforeach</ul></div></noscript>
        </div>
        <details class="nw-review-pdf-text" x-show="documentState === 'ready' && documentMime === 'application/pdf' && pdfText" x-cloak><summary>{{ __('Accessible page text') }}</summary><p x-text="pdfText"></p></details>
        <p class="nw-review-documents__foot"><x-icon name="lock" />{{ __('Document previews stay within this workspace.') }}</p>
    @endif
</section>
