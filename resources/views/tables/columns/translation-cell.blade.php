@php
    $state = $getState();
@endphp

<livewire:filament-translation-cell-editor
    :group="$state['group']"
    :translation-key="$state['translation_key']"
    :content="['translations' => $state['translations'] ?? [], 'locales' => $state['locales'], 'source_locale' => $state['source_locale']]"
    :ai="['enabled' => $state['has_ai'] ?? false, 'driver' => $state['ai_driver'] ?? null]"
    :wire:key="$state['group'] . '.' . $state['translation_key']"
/>
