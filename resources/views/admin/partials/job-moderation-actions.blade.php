@php
    $hasModerationErrors =
        $errors->any() && is_scalar(old('moderation_job')) && (string) old('moderation_job') === (string) $job->id;
    $oldReason = $hasModerationErrors && is_string(old('suspension_reason')) ? old('suspension_reason') : '';
    $dialogClass =
        'm-auto w-[calc(100%_-_2rem)] max-w-xl max-h-[calc(100dvh_-_2rem)] overflow-y-auto rounded-xl border border-gray-200 bg-white p-5 text-gray-900 shadow-xl [overflow-wrap:anywhere] backdrop:bg-gray-900/60 sm:p-6 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp
<div x-data="{ submitting: false }" class="flex w-full min-w-0 flex-wrap gap-2 sm:w-auto"
    @if ($hasModerationErrors) x-init="$nextTick(() => $refs.{{ old('moderation_action') === 'delete' ? 'deleteDialog' : 'suspendDialog' }}.showModal())" @endif>
    @unless ($job->isSuspended())
        <button type="button" aria-haspopup="dialog" aria-controls="suspend-job-dialog-{{ $job->id }}" @click="$refs.suspendDialog.showModal()"
            class="min-h-[44px] flex-1 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600 sm:flex-none dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">Suspend</button>
    @endunless
    <button type="button" aria-haspopup="dialog" aria-controls="delete-job-dialog-{{ $job->id }}" @click="$refs.deleteDialog.showModal()"
        class="min-h-[44px] flex-1 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600 sm:flex-none dark:border-red-800 dark:bg-red-950 dark:text-red-200">Delete</button>

    <dialog id="suspend-job-dialog-{{ $job->id }}" x-ref="suspendDialog" aria-labelledby="suspend-job-title-{{ $job->id }}" class="{{ $dialogClass }}">
        <form method="POST" action="{{ route('admin.jobs.suspend', $job) }}" @submit="submitting = true">
            @csrf
            @method('PATCH')
            <input type="hidden" name="moderation_job" value="{{ $job->id }}">
            <input type="hidden" name="moderation_action" value="suspend">
            <h2 id="suspend-job-title-{{ $job->id }}" class="text-lg font-semibold">Suspend this job?</h2>
            <p class="mt-3 break-words text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $job->title }} will be
                hidden from public listings and restricted from new applications. The employer can correct and resubmit the content to reopen it. The employer's content and
                existing applications are preserved.</p>
            <label for="suspension-reason-{{ $job->id }}" class="mt-4 block text-sm font-medium">Suspension reason
                <span class="text-red-600">*</span></label>
            <textarea id="suspension-reason-{{ $job->id }}" name="suspension_reason" rows="4" maxlength="2000" required
                aria-describedby="suspension-reason-help-{{ $job->id }} suspension-reason-error-{{ $job->id }}"
                aria-invalid="{{ $hasModerationErrors && $errors->has('suspension_reason') ? 'true' : 'false' }}"
                class="mt-2 block w-full rounded-lg border-gray-300 bg-white text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">{{ $oldReason }}</textarea>
            <p id="suspension-reason-help-{{ $job->id }}" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Explain the content or policy issue clearly. This reason is visible to the employer. Maximum 2,000
                characters.</p>
            <p id="suspension-reason-error-{{ $job->id }}" role="alert"
                class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $hasModerationErrors ? $errors->first('suspension_reason') : '' }}</p>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$refs.suspendDialog.close()"
                    class="min-h-[44px] rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium dark:border-gray-600">Cancel</button>
                <button type="submit" :disabled="submitting"
                    class="min-h-[44px] rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50">Confirm suspension</button>
            </div>
        </form>
    </dialog>

    <dialog id="delete-job-dialog-{{ $job->id }}" x-ref="deleteDialog" aria-labelledby="delete-job-title-{{ $job->id }}" class="{{ $dialogClass }}">
        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" @submit="submitting = true">
            @csrf
            @method('DELETE')
            <input type="hidden" name="moderation_job" value="{{ $job->id }}">
            <input type="hidden" name="moderation_action" value="delete">
            <h2 id="delete-job-title-{{ $job->id }}" class="text-lg font-semibold">Permanently delete this job?
            </h2>
            <p class="mt-3 break-words text-sm leading-6 text-gray-600 dark:text-gray-300">Delete {{ $job->title }}
                for a platform policy violation. This also removes its application records and cannot be undone.</p>
            <label class="mt-4 flex items-start gap-3 text-sm leading-6">
                <input type="checkbox" name="confirm_deletion" value="1" required
                    class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
                <span>I understand that this permanently deletes the job and its applications.</span>
            </label>
            @if ($hasModerationErrors)
                <p role="alert" class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ $errors->first('confirm_deletion') }}</p>
            @endif
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$refs.deleteDialog.close()"
                    class="min-h-[44px] rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium dark:border-gray-600">Cancel</button>
                <button type="submit" :disabled="submitting"
                    class="min-h-[44px] rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">Delete
                    permanently</button>
            </div>
        </form>
    </dialog>
</div>
