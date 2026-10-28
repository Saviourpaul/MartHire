@php $isEdit = $job !== null; @endphp
<form method="POST"
      action="{{ $isEdit ? route('organisations.jobs.update', $job) : route('organisations.jobs.store') }}"
      enctype="multipart/form-data"
      class="nw-form-stack"
      x-data="jobForm({ maxLength: {{ \App\Support\JobDescription::MAX_LENGTH }}, storageKey: '{{ $isEdit ? 'job-edit-'.$job->slug : 'job-create' }}' })"
      x-on:submit="allowSubmit()">
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="title" :value="__('Job title')" />
            <x-text-input id="title" name="title" :value="old('title', $job?->title)" required autofocus />
            <x-input-error :messages="$errors->get('title')" />
        </div>
        <div>
            <x-input-label for="company" :value="__('Company')" />
            <x-text-input id="company" name="company" :value="old('company', $job?->company)" required />
            <x-input-error :messages="$errors->get('company')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="category" :value="__('Category')" />
            <x-text-input id="category" name="category" list="job-categories" :value="old('category', $job?->category)" />
            <datalist id="job-categories">
                @foreach ($categories as $categoryOption)<option value="{{ $categoryOption }}"></option>@endforeach
            </datalist>
            <x-input-error :messages="$errors->get('category')" />
        </div>
        <div>
            <x-input-label for="location" :value="__('Location')" />
            <x-text-input id="location" name="location" :value="old('location', $job?->location)" required />
            <x-input-error :messages="$errors->get('location')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="employment_type" :value="__('Employment type')" />
            <select id="employment_type" name="employment_type" class="nw-select" required>
                @foreach ($employmentTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('employment_type', $job?->employment_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('employment_type')" />
        </div>
        <div x-data="{ preview: null }">
            <x-input-label for="logo" :value="__('Logo (optional)')" />
            <input type="file" id="logo" name="logo" accept="image/*" class="nw-input"
                   x-on:change="const f=$event.target.files[0]; if(f){const r=new FileReader(); r.onload=e=>preview=e.target.result; r.readAsDataURL(f);}">
            <template x-if="preview">
                <img :src="preview" alt="" class="nw-logo-preview">
            </template>
            <x-input-error :messages="$errors->get('logo')" />
        </div>
    </div>

    <div class="nw-form-row nw-form-row--fields">
        <div>
            <x-input-label for="start_date" :value="__('Start date')" />
            <x-text-input id="start_date" name="start_date" type="date" :value="old('start_date', $job?->start_date?->toDateString())" required />
            <x-input-error :messages="$errors->get('start_date')" />
        </div>
        <div>
            <x-input-label for="due_date" :value="__('Application deadline')" />
            <x-text-input id="due_date" name="due_date" type="date" :value="old('due_date', $job?->due_date?->toDateString())" required />
            <x-input-error :messages="$errors->get('due_date')" />
        </div>
    </div>

    <div class="nw-editor-field">
        <x-input-label for="description" :value="__('Job description')" />
        <div x-ref="editor" class="nw-editor"></div>
        <textarea name="description" x-ref="input" class="nw-input nw-editor__fallback" rows="10">{{ old('description', $job?->description) }}</textarea>
        <p class="nw-form-hint"><span x-text="count">0</span> / {{ \App\Support\JobDescription::MAX_LENGTH }} {{ __('characters') }}</p>
        <x-input-error :messages="$errors->get('description')" />
    </div>

    <x-form.actions>
        <x-slot:cancel>
            <button type="submit" name="action" value="save_draft" class="nw-button nw-button--outline">{{ __('Save as draft') }}</button>
        </x-slot:cancel>
        <button type="submit" name="action" value="publish" class="nw-button nw-button--primary">{{ __('Publish') }}</button>
    </x-form.actions>
</form>
