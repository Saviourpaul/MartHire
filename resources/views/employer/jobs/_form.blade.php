@php
    $inputClass =
        'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $labelClass = 'mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400';
@endphp

<div class="mt-5 grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 [&>div]:min-w-0">
    <div class="sm:col-span-2">
        <label class="{{ $labelClass }}" for="{{ $prefix }}_title">Job Title</label>
        <input id="{{ $prefix }}_title" type="text" name="title" value="{{ old('title', $job?->title) }}"
            class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="title"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_company">Company</label>
        <input id="{{ $prefix }}_company" type="text" name="company"
            value="{{ old('company', $job?->company) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="company"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_category">Category</label>
        <input id="{{ $prefix }}_category" type="text" name="category"
            value="{{ old('category', $job?->category) }}" placeholder="Healthcare, Finance, Engineering"
            class="{{ $inputClass }}">
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="category"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_location">Location</label>
        <input id="{{ $prefix }}_location" type="text" name="location"
            value="{{ old('location', $job?->location) }}" placeholder="City, state, country or remote"
            class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="location"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_employment_type">Employment Type</label>
        <select id="{{ $prefix }}_employment_type" name="employment_type" class="{{ $inputClass }}" required>
            <option value="">Select employment type</option>
            @foreach (\App\Models\Job::employmentTypeOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('employment_type', $job?->employment_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="employment_type"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_start_date">Start Date</label>
        <input id="{{ $prefix }}_start_date" type="date" name="start_date"
            value="{{ old('start_date', $job?->start_date?->format('Y-m-d')) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="start_date"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_due_date">Due Date</label>
        <input id="{{ $prefix }}_due_date" type="date" name="due_date"
            value="{{ old('due_date', $job?->due_date?->format('Y-m-d')) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="due_date"></p>
    </div>

    <div class="sm:col-span-2">
        <label class="{{ $labelClass }}" for="{{ $prefix }}_logo">Company Logo</label>
        <input id="{{ $prefix }}_logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.gif,image/*"
            class="{{ $inputClass }} file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-theme-xs file:font-medium file:text-gray-700 dark:file:bg-gray-800 dark:file:text-gray-300">
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="logo"></p>
        @if ($job?->logo)
            <div class="mt-3 flex items-center gap-3 rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                <img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo"
                    class="h-11 w-11 rounded-lg object-contain">
                <span class="text-theme-sm text-gray-500 dark:text-gray-400">Current logo</span>
            </div>
        @endif
    </div>

    <div class="min-w-0 sm:col-span-2" data-job-description-editor>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
            for="{{ $prefix }}_description">Job Description</label>
        <p id="{{ $prefix }}_description_hint" class="mb-3 text-sm leading-6 text-gray-500 dark:text-gray-400">
            Describe the role, responsibilities, and qualifications. Format your content using the toolbar.
            Images use HTTPS URLs.
        </p>
        <textarea id="{{ $prefix }}_description" name="description" rows="8" data-editor-source
            aria-describedby="{{ $prefix }}_description_hint {{ $prefix }}_description_error"
            aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}"
            placeholder="Tell candidates about the role, what they will do, and the experience they need."
            class="block min-h-[12rem] w-full resize-y rounded-lg border border-gray-300 bg-gray-50 p-3 text-base leading-relaxed text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 aria-[invalid=true]:border-red-500 aria-[invalid=true]:focus:ring-red-500/20 sm:p-4 sm:text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-blue-500 dark:aria-[invalid=true]:border-red-500"
            required>{{ old('description', $job?->description) }}</textarea>
        <template data-editor-initial-content>{!! app(\App\Support\JobDescription::class)->toHtml(old('description', $job?->description)) !!}</template>
        <div class="job-editor-shell rounded-lg border border-gray-300 bg-white shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900"
            data-editor-ui hidden>
            <div class="flex flex-wrap items-center gap-2 rounded-t-lg border-b border-gray-200 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-800"
                role="group" aria-label="Description typography">
                <label class="sr-only" for="{{ $prefix }}_block">Paragraph or heading</label>
                <select id="{{ $prefix }}_block" data-editor-select="block" class="job-editor-select">
                    <option value="paragraph">Paragraph</option>
                    @foreach (range(1, 6) as $level)
                        <option value="{{ $level }}">Heading {{ $level }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="{{ $prefix }}_font">Font family</label>
                <select id="{{ $prefix }}_font" data-editor-select="fontFamily" class="job-editor-select">
                    <option value="">Default font</option>
                    @foreach (['Arial', 'Georgia', 'Times New Roman', 'Verdana', 'Courier New'] as $font)
                        <option value="{{ $font }}">{{ $font }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="{{ $prefix }}_size">Font size</label>
                <select id="{{ $prefix }}_size" data-editor-select="fontSize" class="job-editor-select">
                    <option value="">Default size</option>
                    @foreach ([12, 14, 16, 18, 20, 24, 30, 36] as $size)
                        <option value="{{ $size }}px">{{ $size }} px</option>
                    @endforeach
                </select>
                <label class="sr-only" for="{{ $prefix }}_line_height">Line spacing</label>
                <select id="{{ $prefix }}_line_height" data-editor-select="lineHeight"
                    class="job-editor-select">
                    <option value="">Line spacing</option>
                    @foreach (['1', '1.25', '1.5', '1.75', '2'] as $height)
                        <option value="{{ $height }}">{{ $height }}</option>
                    @endforeach
                </select>
                <label class="job-editor-color" title="Text color">Text <input type="color" value="#111827"
                        data-editor-color="color" aria-label="Text color"></label>
                <label class="job-editor-color" title="Highlight color">Highlight <input type="color"
                        value="#fef08a" data-editor-color="highlight" aria-label="Highlight color"></label>
            </div>
            <div class="flex flex-wrap gap-1 border-b border-gray-200 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-800"
                role="group" aria-label="Description formatting">
                @foreach ([
        'bold' => ['B', 'Bold (Ctrl or Cmd+B)'],
        'italic' => ['I', 'Italic (Ctrl or Cmd+I)'],
        'underline' => ['U', 'Underline (Ctrl or Cmd+U)'],
        'strike' => ['S', 'Strikethrough'],
        'subscript' => ['x₂', 'Subscript'],
        'superscript' => ['x²', 'Superscript'],
        'code' => ['</>', 'Inline code'],
        'bulletList' => ['• List', 'Bullet list'],
        'orderedList' => ['1. List', 'Numbered list'],
        'blockquote' => ['Quote', 'Blockquote'],
        'codeBlock' => ['Code', 'Code block'],
    ] as $command => [$label, $title])
                    <button type="button" class="job-editor-button" data-editor-command="{{ $command }}"
                        title="{{ $title }}" aria-label="{{ $title }}"
                        aria-pressed="false">{{ $label }}</button>
                @endforeach
                <label class="sr-only" for="{{ $prefix }}_align">Text alignment</label>
                <select id="{{ $prefix }}_align" data-editor-select="textAlign" class="job-editor-select">
                    @foreach (['left', 'center', 'right', 'justify'] as $alignment)
                        <option value="{{ $alignment }}">{{ ucfirst($alignment) }}</option>
                    @endforeach
                </select>
                @foreach (['link' => 'Link', 'unlink' => 'Unlink', 'image' => 'Image', 'removeImage' => 'Remove image', 'horizontalRule' => 'Divider', 'undo' => 'Undo', 'redo' => 'Redo', 'clear' => 'Clear styles'] as $command => $label)
                    <button type="button" class="job-editor-button" data-editor-command="{{ $command }}"
                        title="{{ $label }}" aria-label="{{ $label }}">{{ $label }}</button>
                @endforeach
                <label class="sr-only" for="{{ $prefix }}_table">Table controls</label>
                <select id="{{ $prefix }}_table" data-editor-select="table" class="job-editor-select">
                    <option value="">Table</option>
                    @foreach (['insertTable' => 'Insert table', 'addRowBefore' => 'Row above', 'addRowAfter' => 'Row below', 'deleteRow' => 'Delete row', 'addColumnBefore' => 'Column before', 'addColumnAfter' => 'Column after', 'deleteColumn' => 'Delete column', 'mergeCells' => 'Merge cells', 'splitCell' => 'Split cell', 'toggleHeaderRow' => 'Toggle header row', 'toggleHeaderColumn' => 'Toggle header column', 'deleteTable' => 'Delete table'] as $command => $label)
                        <option value="{{ $command }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="border-b border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-800"
                data-editor-asset-panel hidden>
                <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="text-sm font-medium text-gray-700 sm:col-span-2 dark:text-gray-200">URL <input
                            type="url" maxlength="2048" data-editor-asset="url" class="job-editor-asset-input"
                            placeholder="https://example.com/image.jpg"></label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200"
                        data-editor-image-option>Alternative text <input type="text" maxlength="255"
                            data-editor-asset="alt" class="job-editor-asset-input"
                            placeholder="Describe the image"></label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200" data-editor-image-option>Image
                        title <input type="text" maxlength="255" data-editor-asset="title"
                            class="job-editor-asset-input"></label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200" data-editor-image-option>Image
                        width <select data-editor-asset="width" class="job-editor-asset-input">
                            <option value="100%">Full width</option>
                            <option value="75%">75%</option>
                            <option value="50%">50%</option>
                            <option value="25%">25%</option>
                        </select></label>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200" data-editor-image-option>Image
                        alignment <select data-editor-asset="align" class="job-editor-asset-input">
                            <option value="left">Left</option>
                            <option value="center">Center</option>
                            <option value="right">Right</option>
                        </select></label>
                </div>
                <p class="mt-2 text-sm text-red-600" role="status" data-editor-asset-error></p>
                <div class="mt-3 flex flex-wrap gap-2"><button type="button" class="job-editor-button"
                        data-editor-asset-apply>Apply</button><button type="button" class="job-editor-button"
                        data-editor-asset-cancel>Cancel</button></div>
            </div>
            <div class="job-description-content format format-sm format-gray max-w-none break-words p-3 sm:format-base sm:p-4 dark:format-invert"
                data-editor-surface></div>
            <div
                class="border-t border-gray-200 px-3 py-2 text-xs text-gray-500 dark:border-gray-600 dark:text-gray-400">
                <span data-editor-word-count>0 words</span> · Select an image, then choose Image to edit it.</div>
        </div>
        <p id="{{ $prefix }}_description_error"
            class="mt-2 text-sm text-red-600 dark:text-red-400 {{ $errors->has('description') ? '' : 'hidden' }}"
            data-field-error="description" aria-live="polite">
            @error('description')
                {{ $message }}
            @enderror
        </p>
    </div>
</div>

@pushOnce('page-scripts', 'job-description-editor')
    <script src="{{ asset('assets/js/flowbite.js') }}"
        data-editor-module="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/job-description-editor.js') }}" defer>
    </script>
@endPushOnce
@if ($job?->isSuspended())
    <div class="mb-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
        <p class="font-semibold">Suspension reason</p>
        <p class="mt-2 whitespace-pre-line break-words">{{ $job->suspension_reason ?: 'Update this job to meet platform requirements.' }}</p>
        <p class="mt-3">Correct the policy issue and save your changes. A successful update reopens this job for applications. Choose a current or future application deadline.</p>
    </div>
@endif
