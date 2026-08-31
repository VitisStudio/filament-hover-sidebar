<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Store probe</x-slot>
        <x-slot name="description">
            `pinned` is this plugin's persisted preference. `isOpen` is core's presentation flag,
            driven from `pinned || peeking`.
        </x-slot>

        <dl class="grid grid-cols-2 gap-y-2 font-mono text-sm sm:grid-cols-4">
            @foreach ([
                '$store.fhs.pinned' => '$store.fhs.pinned',
                '$store.fhs.peeking' => '$store.fhs.peeking',
                '$store.sidebar.isOpen' => '$store.sidebar.isOpen',
                'body.fhs-pinned' => "document.body.classList.contains('fhs-pinned')",
            ] as $label => $expression)
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd x-text="JSON.stringify({{ $expression }})"></dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">What to check</x-slot>

        <ul class="list-inside list-disc space-y-1 text-sm">
            <li>Hover the rail — it widens <em>over</em> this content after ~90ms.</li>
            <li>Leave it — it collapses after ~180ms.</li>
            <li>Pin it from the topbar — the layout switches to core's push behaviour.</li>
            <li>Reload — the pin survives; the hover state does not.</li>
            <li>Collapsed, hover the <strong>Pipeline</strong> group icon — core's dropdown flyout.</li>
            <li>Tab into the nav from the topbar — focus expands it too.</li>
            <li>Navigate between pages — SPA nav replaces the sidebar node; hover must still work.</li>
            <li>Narrow the window below 1024px — everything reverts to core's mobile drawer.</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
