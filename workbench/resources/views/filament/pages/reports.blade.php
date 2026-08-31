<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Reflow probe</x-slot>
        <x-slot name="description">
            Hover the sidebar. This table must not re-lay-out and the left edge below must not move.
        </x-slot>

        <div
            x-data="{ left: null }"
            x-init="left = document.querySelector('.fi-main-ctn').getBoundingClientRect().left"
            class="mb-4 font-mono text-sm"
        >
            <span class="text-gray-500 dark:text-gray-400">.fi-main-ctn left on load:</span>
            <span x-text="left"></span>
            <span class="text-gray-500 dark:text-gray-400">— now:</span>
            <span
                x-text="Math.round(document.querySelector('.fi-main-ctn').getBoundingClientRect().left)"
                x-effect="$store.fhs.peeking; $nextTick(() => {})"
            ></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead class="border-b border-gray-200 dark:border-white/10">
                    <tr>
                        @foreach (['Account', 'Owner', 'Stage', 'Value', 'Close date'] as $heading)
                            <th class="px-3 py-2 text-start font-medium">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(1, 12) as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-3 py-2">Northwind Logistics {{ $row }}</td>
                            <td class="px-3 py-2">Dana Whitfield</td>
                            <td class="px-3 py-2">Negotiation</td>
                            <td class="px-3 py-2 tabular-nums">${{ number_format($row * 4285) }}</td>
                            <td class="px-3 py-2 tabular-nums">2026-{{ str_pad((string) (($row % 12) + 1), 2, '0', STR_PAD_LEFT) }}-14</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
