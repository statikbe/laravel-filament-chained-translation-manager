<x-filament::page>
    @foreach($translations as $translation)
        <livewire:translation-edit-form wire:key="{{ $translation['id'] }}" :group="$translation['group']" :translation-key="$translation['key']" :translations="$translation['translations']" :locales="$locales"/>
    @endforeach
</x-filament::page>
