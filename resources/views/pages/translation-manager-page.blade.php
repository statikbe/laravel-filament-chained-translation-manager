<x-filament-panels::page>
    {{-- The translation key column contains long, unbroken strings (e.g. "group.some_long_translation_key").
         Filament's table-auto layout doesn't shrink the column to fit before wrapping the text (it grows the
         column to the unwrapped content width instead), so we force fixed layout for this table specifically. --}}
    <style>
        .fi-ta-table:has(.fi-ta-cell-translations) {
            table-layout: fixed;
        }
    </style>

    {{ $this->table }}
</x-filament-panels::page>
