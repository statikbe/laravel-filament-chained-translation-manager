<x-filament::page>
    <div @class([
            "p-4 space-y-2 bg-white rounded-xl shadow",
            'dark:bg-gray-700' => config('filament.dark_mode'),
        ])>
        <form wire:submit.prevent="submitFilters">
            <div class="flex items-start">
                <div class="grow">
                    {{ $this->form }}
                </div>

                <x-filament::button type="submit"
                                    icon="heroicon-o-filter"
                                    class="flex-0 ml-4">
                    @lang('filament-translation-manager::messages.filter_action')
                </x-filament::button>
            </div>
        </form>
    </div>

    <div>
        @lang('filament-translation-manager::messages.filter_results', ['filtered' => $totalFilteredTranslations, 'total' => $totalTranslations])
    </div>

    @forelse($filteredTranslations as $translation)
        <livewire:translation-edit-form
            wire:key="{{ $translation['title'] }}.{{ implode('-', $selectedLocales) }}"
            :group="$translation['group']"
            :translation-key="$translation['translation-key']"
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
                icon="heroicon-o-chevron-left"
                class="ml-4 -mr-1"
                wire:click="previousPage" />
        @endif

        @if($totalFilteredTranslations > $pagedTranslations)
            <x-filament::icon-button
                :label="__('filament-translation-manager::messages.next_page')"
                icon="heroicon-o-chevron-right"
                class="ml-4 -mr-1"
                wire:click="nextPage" />
        @endif
    </div>
</x-filament::page>
