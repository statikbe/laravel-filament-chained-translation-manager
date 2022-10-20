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

    <div id="pagination">
    @if($pageCounter > 1)
        <button wire:click="previousPage">@lang('filament-translation-manager::messages.previous_page')</button>
    @endif

    @if($totalFilteredTranslations > $pagedTranslations)
        <button wire:click="nextPage">@lang('filament-translation-manager::messages.next_page')</button>
    @endif
    </div>
</x-filament::page>
