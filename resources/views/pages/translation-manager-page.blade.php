<x-filament::page>
    <form wire:submit.prevent="filterTranslations">
        {{ $this->form }}

        <button type="submit">
            Filter
        </button>
    </form>

    @forelse($filteredTranslations as $translation)
        <livewire:translation-edit-form wire:key="{{ $translation['title'] }}" :group="$translation['group']" :translation-key="$translation['key']" :translations="$translation['translations']" :locales="$selectedLanguages"/>
    @empty
        @if(empty($translations))
            <div>@lang('filament-translation-manager::messages.error_no_translations_for_filters')</div>
        @else
            <div>@lang('filament-translation-manager::messages.error_no_translations_for_filters')</div>
        @endif
    @endforelse

    @dump($pageCounter)
    @if($pageCounter > 1)
        <div>@lang('filament-translation-manager::messages.previous_page')</div>
    @endif
    @dump($totalFilteredTranslations)

    @if($totalFilteredTranslations > $pagedTranslations)
        <div>@lang('filament-translation-manager::messages.next_page')</div>
    @endif

</x-filament::page>
