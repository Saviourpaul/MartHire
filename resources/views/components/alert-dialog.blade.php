<dialog class="nw-alert-dialog" data-alert-dialog aria-labelledby="nw-alert-title" aria-describedby="nw-alert-message">
    <div class="nw-alert-dialog__body">
        <button type="button" class="nw-alert-dialog__dismiss" data-alert-dismiss aria-label="{{ __('Close alert') }}"><x-icon name="close" /></button>
        <div class="nw-alert-dialog__icon" aria-hidden="true">
            @foreach(['success' => 'check-circle', 'error' => 'circle-x', 'warning' => 'alert-triangle', 'info' => 'info'] as $state => $icon)<span data-alert-icon="{{ $state }}" hidden><x-icon :name="$icon" /></span>@endforeach
        </div>
        <h2 id="nw-alert-title" data-alert-title></h2>
        <p id="nw-alert-message" data-alert-message></p>
        <div class="nw-alert-dialog__actions"><button type="button" class="nw-button nw-button--outline" data-alert-cancel>{{ __('Cancel') }}</button><button type="button" class="nw-button nw-button--primary" data-alert-confirm>{{ __('Confirm') }}</button></div>
    </div>
</dialog>
