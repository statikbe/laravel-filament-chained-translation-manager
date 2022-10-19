<x-filament::page>
    <form wire:submit.prevent="filterTranslations">
        {{ $this->form }}

        <button type="submit">
            Filter
        </button>
    </form>

    @foreach($translations as $translation)
        <livewire:translation-edit-form wire:key="{{ $translation['title'] }}" :group="$translation['group']" :translation-key="$translation['key']" :translations="$translation['translations']" :locales="$locales"/>
    @endforeach
</x-filament::page>
