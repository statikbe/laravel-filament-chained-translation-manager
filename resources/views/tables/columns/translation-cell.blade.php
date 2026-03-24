@php
    $state = $getState();
@endphp

<livewire:filament-translation-cell-editor
    :group="$state['group']"
    :translation-key="$state['translation_key']"
    :translations="$state['translations'] ?? []"
    :locales="$state['locales']"
    :source-locale="$state['source_locale']"
    :has-ai="$state['has_ai'] ?? false"
    :ai-driver="$state['ai_driver'] ?? null"
    :wire:key="$state['group'] . '.' . $state['translation_key']"
/>
