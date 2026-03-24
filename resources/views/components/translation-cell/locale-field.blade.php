@props([
    'locale',
    'isFirst'            => false,
    'isLast'             => false,
    'hasAi'              => false,
    'setAsSourceLabel',
    'translatedLabel',
    'missingLabel',
    'missingPlaceholder',
])
<div class="group/field pl-2">
    <div class="flex items-center gap-2 mb-0.5">

        {{-- Locale badge — click to set as source locale --}}
        <button
            type="button"
            tabindex="-1"
            @click="sourceLocale = '{{ $locale }}'"
            class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 font-mono text-[0.65rem] font-bold uppercase tracking-wider transition-all"
            :class="sourceLocale === '{{ $locale }}'
                ? 'bg-primary-500 dark:bg-primary-600 text-white shadow-sm'
                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 cursor-pointer hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/30 dark:hover:text-primary-400'"
            :title="sourceLocale === '{{ $locale }}' ? '' : '{{ $setAsSourceLabel }}'"
        >
            {{-- Arrow icon — nudges non-source locales to be obviously clickable --}}
            <svg
                x-show="sourceLocale !== '{{ $locale }}'"
                class="w-2.5 h-2.5 opacity-0 group-hover/field:opacity-60 transition-opacity"
                fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 3L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
            </svg>
            {{ $locale }}
        </button>

        {{-- SOURCE pill --}}
        <span
            x-show="sourceLocale === '{{ $locale }}'"
            class="inline-flex items-center rounded-full bg-primary-50 dark:bg-primary-900/30 px-2 py-0.5
                   text-[0.6rem] font-semibold uppercase tracking-widest
                   text-primary-600 dark:text-primary-400
                   ring-1 ring-primary-200 dark:ring-primary-800/40"
        >{{ trans('filament-translation-manager::messages.source_locale_label') }}</span>

        {{-- Translated/missing status swaps with "set as source" hint on hover --}}
        <span x-show="sourceLocale !== '{{ $locale }}'">
            <span
                class="group-hover/field:hidden text-[0.65rem] font-medium transition-colors"
                :class="translations['{{ $locale }}'] && translations['{{ $locale }}'].trim()
                    ? 'text-success-600 dark:text-success-400'
                    : 'text-gray-400 dark:text-gray-500'"
                x-text="translations['{{ $locale }}'] && translations['{{ $locale }}'].trim()
                    ? '{{ $translatedLabel }}'
                    : '{{ $missingLabel }}'"
            ></span>
            <span class="hidden group-hover/field:inline text-[0.6rem] text-gray-400 dark:text-gray-500 select-none">
                {{ $setAsSourceLabel }}
            </span>
        </span>
    </div>

    <div class="relative">
        <textarea
            x-model="translations['{{ $locale }}']"
            @keydown.meta.enter.prevent="save()"
            @keydown.ctrl.enter.prevent="save()"
            @keydown.meta.shift.a.prevent="aiTranslate()"
            @keydown.ctrl.shift.a.prevent="aiTranslate()"
            @if ($isFirst) @keydown.shift.tab.prevent="navigate('prev', 'last')" @endif
            @if ($isLast) @keydown.tab="if (!$event.shiftKey) { $event.preventDefault(); navigate('next', 'first'); }" @endif
            rows="2"
            class="block w-full rounded-lg text-sm shadow-sm transition-colors resize-y
                   bg-white dark:bg-gray-900
                   focus:outline-none focus:ring-2 focus:ring-primary-500/25 focus:border-primary-400
                   dark:text-white dark:placeholder-gray-600 dark:focus:border-primary-500"
            :class="translations['{{ $locale }}'] && translations['{{ $locale }}'].trim()
                ? 'border border-gray-200 dark:border-gray-700'
                : 'border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50'"
            placeholder="{{ $missingPlaceholder }}"
        ></textarea>

        @if ($hasAi)
            <button
                type="button"
                tabindex="-1"
                x-show="sourceLocale !== '{{ $locale }}'"
                x-cloak
                @click.stop="aiTranslateLocale('{{ $locale }}')"
                :disabled="aiLoading || !translations[sourceLocale]"
                :class="!aiLoading && translations[sourceLocale]
                    ? 'text-warning-500 dark:text-warning-400 opacity-0 group-hover/field:opacity-100 hover:bg-warning-50 dark:hover:bg-warning-900/20'
                    : 'text-gray-300 dark:text-gray-600 opacity-0 group-hover/field:opacity-60 cursor-not-allowed'"
                class="absolute bottom-1.5 right-1.5 p-1 rounded transition-all"
                title="{{ trans('filament-translation-manager::messages.ai_translate_row_action') }}"
            >
                <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                </svg>
                <x-tcm::spinner x-show="aiLoading" x-cloak />
            </button>
        @endif
    </div>
</div>
