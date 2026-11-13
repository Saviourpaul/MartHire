@props(['label', 'id' => null])

@php
    $menuId = $id ?: 'row-actions-'.\Illuminate\Support\Str::uuid();
@endphp

<div class="nw-row-actions" x-data="tableActionMenu" x-on:click.outside="close()" x-on:keydown.escape.stop.prevent="closeAndFocus()" x-on:resize.window="reposition()" x-on:scroll.capture.window="closeOnScroll()">
    <button
        type="button"
        class="nw-row-actions__toggle"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="{{ $menuId }}"
        aria-label="{{ __('Actions for :record', ['record' => $label]) }}"
        title="{{ __('Actions') }}"
        x-ref="trigger"
        x-bind:aria-expanded="open.toString()"
        x-on:click="toggle()"
        x-on:keydown.arrow-down.prevent="openMenu()"
        x-on:keydown.arrow-up.prevent="openMenu(true)"
    >
        <x-icon name="more-horizontal" />
    </button>

    <div
        id="{{ $menuId }}"
        class="nw-row-actions__menu"
        role="menu"
        aria-label="{{ __('Actions for :record', ['record' => $label]) }}"
        x-ref="menu"
        x-cloak
        x-show="open"
        x-bind:style="menuStyle"
        x-transition.origin.top.right
        x-on:click="close()"
        x-on:keydown.arrow-down.prevent="focusNext(1)"
        x-on:keydown.arrow-up.prevent="focusNext(-1)"
        x-on:keydown.home.prevent="focusEdge('first')"
        x-on:keydown.end.prevent="focusEdge('last')"
        x-on:keydown.tab="tabOut($event)"
    >
        {{ $slot }}
    </div>
</div>
