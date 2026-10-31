<div class="nw-wizard-review">
    <template x-for="section in review" x-bind:key="section.step">
        <section class="nw-wizard-review__section">
            <header><h3 x-text="section.title"></h3><button type="button" class="nw-link nw-link--small" x-on:click="editStep(section.step)" x-bind:aria-label="'Edit ' + section.title">{{ __('Edit') }} <x-icon name="pencil" /></button></header>
            <dl>
                <template x-for="(entry, index) in section.entries" x-bind:key="index">
                    <div><dt x-text="entry.label"></dt><dd x-text="entry.value"></dd></div>
                </template>
            </dl>
        </section>
    </template>
</div>
