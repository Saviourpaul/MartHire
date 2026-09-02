@php
    $inputClass = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $labelClass = 'mb-1.5 block text-theme-xs font-medium text-gray-700 dark:text-gray-400';
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="{{ $labelClass }}" for="{{ $prefix }}_title">Job Title</label>
        <input id="{{ $prefix }}_title" type="text" name="title" value="{{ old('title', $job?->title) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="title"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_company">Company</label>
        <input id="{{ $prefix }}_company" type="text" name="company" value="{{ old('company', $job?->company) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="company"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_category">Category</label>
        <input id="{{ $prefix }}_category" type="text" name="category" value="{{ old('category', $job?->category) }}" placeholder="Healthcare, Finance, Engineering" class="{{ $inputClass }}">
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="category"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_location">Location</label>
        <input id="{{ $prefix }}_location" type="text" name="location" value="{{ old('location', $job?->location) }}" placeholder="City, state, country or remote" class="{{ $inputClass }}" required>
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
        <input id="{{ $prefix }}_start_date" type="date" name="start_date" value="{{ old('start_date', $job?->start_date?->format('Y-m-d')) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="start_date"></p>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $prefix }}_due_date">Due Date</label>
        <input id="{{ $prefix }}_due_date" type="date" name="due_date" value="{{ old('due_date', $job?->due_date?->format('Y-m-d')) }}" class="{{ $inputClass }}" required>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="due_date"></p>
    </div>

    <div class="sm:col-span-2">
        <label class="{{ $labelClass }}" for="{{ $prefix }}_logo">Company Logo</label>
        <input id="{{ $prefix }}_logo" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" class="{{ $inputClass }} file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-theme-xs file:font-medium file:text-gray-700 dark:file:bg-gray-800 dark:file:text-gray-300">
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="logo"></p>
        @if ($job?->logo)
            <div class="mt-3 flex items-center gap-3 rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                <img src="{{ $job->logoUrl() }}" alt="{{ $job->company }} logo" class="h-11 w-11 rounded-lg object-contain">
                <span class="text-theme-sm text-gray-500 dark:text-gray-400">Current logo</span>
            </div>
        @endif
    </div>

    <div class="sm:col-span-2">
        <label class="{{ $labelClass }}" for="{{ $prefix }}_description">Description</label>
        <textarea id="{{ $prefix }}_description" name="description" rows="7" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" required>{{ old('description', $job?->description) }}</textarea>
        <p class="mt-1 hidden text-theme-xs text-error-500" data-field-error="description"></p>
    </div>
</div>

