<x-filament::page>
    <div @class([
            "p-4 space-y-2 bg-white rounded-xl shadow dark:bg-gray-700",
        ])>
        <form wire:submit.prevent="submitFilters">
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="w-full">
                    {{ $this->form }}
                </div>
                <div class="flex flex-col justify-end">
                    <x-filament::button type="submit"
                                             alias="filament-chained-translation-manager::filter-translations"
                                             icon="heroicon-o-funnel">
                        @lang('filament-translation-manager::messages.filter_action')
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>
    <div class="flex">
        <span>
            <x-filament::icon
                alias="filament-chained-translation-manager::filter-translations"
                icon="heroicon-o-funnel"
                class="h-6 w-5 pt-1 mr-2"/>
        </span>
        <span>@lang('filament-translation-manager::messages.filter_results', ['filtered' => $totalFilteredTranslations, 'total' => $totalTranslations])</span>
        @if($totalFilteredTranslations > 0)
            <span>
                <x-filament::icon
                    alias="filament-chained-translation-manager::missing-translations"
                    icon="heroicon-o-exclamation-circle"
                    class="h-6 w-5 pt-1 mr-2 ml-2"/>
            </span>
            <span>@lang('filament-translation-manager::messages.filter_results_missing_translations', ['missing' => $totalMissingFilteredTranslations,
                'percent' => number_format(($totalMissingFilteredTranslations / $totalFilteredTranslations) * 100, 0)])</span>
        @endif
    </div>
    @forelse($filteredTranslations as $translation)
        <livewire:translation-edit-form
            wire:key="{{ $translation['title'] }}.{{ implode('-', $selectedLocales) }}"
            :group="$translation['group']"
            :translation_key="$translation['translation_key']"
            :translations="$translation['translations']"
            :locales="$selectedLocales"
        />
    @empty
        @if(empty($translations))
            <div>@lang('filament-translation-manager::messages.error_no_translations_for_filters')</div>
        @else
            <div>@lang('filament-translation-manager::messages.error_no_translations_for_filters')</div>
        @endif
    @endforelse
    <div id="pagination" class="flex justify-end">
        @if($pageCounter > 1)
            <x-filament::icon-button
                :label="__('filament-translation-manager::messages.previous_page')"
                alias="filament-chained-translation-manager::next-previous"
                icon="heroicon-o-chevron-left"
                class="ml-4 -mr-1"
                wire:click="previousPage" />
        @endif

        @if($totalFilteredTranslations > $pagedTranslations)
            <x-filament::button
                :label="__('filament-translation-manager::messages.next_page')"
                alias="filament-chained-translation-manager::next-page"
                icon="heroicon-o-chevron-right"
                class="ml-4 -mr-1"
                wire:click="nextPage" />
        @endif
    </div>
</x-filament::page>
