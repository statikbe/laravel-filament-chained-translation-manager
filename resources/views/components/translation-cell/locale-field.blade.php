@props([
    'locale',
    'hasAi' => false,
])

@php
    $setAsSourceLabel = trans('filament-translation-manager::messages.set_as_source');
@endphp

<div class="group/field pl-2">
    <div class="group/btnWithLbl flex items-center gap-2 my-2">

        {{-- Locale badge — click to set as source locale. Only meaningful when AI translation
             is enabled, since nothing else reads the source locale; otherwise it's inert. --}}
        @if ($hasAi)
            <button
                type="button"
                @click="sourceLocale = {{ json_encode($locale) }}"
                class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 font-mono text-[0.65rem] font-bold uppercase tracking-wider transition-all"
                :class="sourceLocale === {{ json_encode($locale) }}
                    ? 'bg-primary-500 dark:bg-primary-600 text-white shadow-sm cursor-default'
                    : 'bg-primary-100 text-gray-500 border border-bg-primary-500 dark:bg-gray-800 dark:text-gray-200 cursor-pointer hover:bg-primary-300 hover:text-primary-600 dark:hover:bg-primary-900/30 dark:hover:text-primary-400'"
                :title="sourceLocale === {{ json_encode($locale) }} ? '' : {{ json_encode($setAsSourceLabel) }}"
            >
                {{-- Arrow icon — nudges non-source locales to be obviously clickable --}}
                <svg
                    x-show="sourceLocale !== {{ json_encode($locale) }}"
                    class="w-3 h-3 opacity-40 group-hover/btnWithLbl:opacity-80 transition-opacity"
                    fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 3L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                </svg>
                {{ $locale }}
            </button>
        @else
            <span
                class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 font-mono text-[0.65rem] font-bold uppercase tracking-wider"
                :class="sourceLocale === {{ json_encode($locale) }}
                    ? 'bg-primary-500 dark:bg-primary-600 text-white shadow-sm'
                    : 'bg-primary-100 text-gray-500 border border-bg-primary-500 dark:bg-gray-800 dark:text-gray-200'"
            >{{ $locale }}</span>
        @endif

        {{-- SOURCE pill --}}
        <span
            x-show="sourceLocale === {{ json_encode($locale) }}"
            class="inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-900/30 px-2 py-0.5
                   text-[0.6rem] font-semibold uppercase tracking-widest
                   text-primary-600 dark:text-primary-400
                   ring-1 ring-primary-200 dark:ring-primary-800/40"
        >{{ trans('filament-translation-manager::messages.source_locale_label') }}</span>

        {{-- Translated/missing status @if($hasAi) swaps with "set as source" hint on hover @endif --}}
        <span x-show="sourceLocale !== {{ json_encode($locale) }}"
              class="flex items-center"
        >
            <span
                class="@if ($hasAi) group-hover/btnWithLbl:hidden @endif text-xs font-medium transition-colors"
                :class="translations[{{ json_encode($locale) }}] && translations[{{ json_encode($locale) }}].trim()
                    ? 'text-success-600 dark:text-success-400'
                    : 'text-gray-400 dark:text-gray-500'"
                x-text="translations[{{ json_encode($locale) }}] && translations[{{ json_encode($locale) }}].trim()
                    ? {{ json_encode(trans('filament-translation-manager::messages.is_translated_for_locale')) }}
                    : {{ json_encode(trans('filament-translation-manager::messages.missing_translation')) }}"
            ></span>
            @if ($hasAi)
                <span class="hidden group-hover/btnWithLbl:inline text-xs text-gray-400 dark:text-gray-500 select-none">
                    {{ $setAsSourceLabel }}
                </span>
            @endif
        </span>
    </div>

    <div class="relative">
        <textarea
            x-model="translations[{{ json_encode($locale) }}]"
            @keydown.meta.enter.prevent="save()"
            @keydown.ctrl.enter.prevent="save()"
            @keydown.meta.shift.a.prevent="aiTranslate()"
            @keydown.ctrl.shift.a.prevent="aiTranslate()"
            rows="2"
            class="block w-full rounded-lg text-base shadow-sm transition-colors resize-y p-2
                   bg-white dark:bg-gray-900
                   focus:outline-none focus:ring-2 focus:ring-primary-500/25 focus:border-primary-400
                   dark:text-white dark:placeholder-gray-600 dark:focus:border-primary-500"
            :class="translations[{{ json_encode($locale) }}] && translations[{{ json_encode($locale) }}].trim()
                ? 'border border-gray-200 dark:border-gray-700'
                : 'border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50'"
            placeholder="{{ trans('filament-translation-manager::messages.missing_translation') }}"
        ></textarea>

        @if ($hasAi)
            <x-filament::icon-button
                type="button"
                x-show="sourceLocale !== {{ json_encode($locale) }} && !aiLoading"
                x-cloak
                @click.stop="aiTranslateLocale({{ json_encode($locale) }})"
                x-bind:disabled="aiLoading || !(translations[sourceLocale] || sourceText)"
                color="warning"
                size="xs"
                icon="heroicon-m-sparkles"
                label="{{ trans('filament-translation-manager::messages.ai_translate_row_action') }}"
                tooltip="{{ trans('filament-translation-manager::messages.ai_translate_row_action') }}"
                class="absolute top-1/2 -translate-y-1/2 right-1 opacity-0 group-hover/field:opacity-100 transition-all"
            />
            <x-filament::loading-indicator
                x-show="sourceLocale !== {{ json_encode($locale) }} && aiLoading"
                x-cloak
                class="absolute top-1/2 -translate-y-1/2 right-2 w-3.5 h-3.5 text-warning-500 dark:text-warning-400"
            />
        @endif
    </div>
</div>
