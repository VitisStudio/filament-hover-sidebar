<div
    x-data="{}"
    x-cloak
    class="fhs-pin-btn-ctn"
>
    <x-filament::icon-button
        color="gray"
        icon="heroicon-o-lock-open"
        icon-size="lg"
        :label="__('filament-hover-sidebar::hover-sidebar.pin')"
        x-show="! $store.fhs.pinned"
        x-on:click="$store.fhs.togglePin()"
        aria-controls="fi-main-sidebar"
    />

    <x-filament::icon-button
        color="gray"
        icon="heroicon-s-lock-closed"
        icon-size="lg"
        :label="__('filament-hover-sidebar::hover-sidebar.unpin')"
        x-show="$store.fhs.pinned"
        x-on:click="$store.fhs.togglePin()"
        aria-controls="fi-main-sidebar"
    />
</div>
