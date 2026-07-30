<x-filament-panels::page>
    {{-- The translation key column contains long, unbroken strings (e.g. "group.some_long_translation_key").
         Filament's table-auto layout doesn't shrink the column to fit before wrapping the text (it grows the
         column to the unwrapped content width instead), so we force fixed layout for this table specifically. --}}
    <style>
        .fi-ta-table:has(.fi-ta-cell-translations) {
            table-layout: fixed;
        }

        {{-- Tab-focusing any control inside a translation row highlights it the same way
             clicking it open does, since the row's own focus outline is suppressed. --}}
        [data-translation-id]:has(:focus-visible) {
            background-color: var(--color-gray-100, #f3f4f6);
        }

        .dark [data-translation-id]:has(:focus-visible) {
            background-color: rgb(255 255 255 / 0.03);
        }
    </style>

    {{ $this->table }}
</x-filament-panels::page>
