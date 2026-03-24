@props([
    'hasAi',
    'saveBtnLabel',
    'cancelBtnLabel',
    'aiBtnLabel',
    'aiNoSourceTitle',
    'aiTooltip',
])
<div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800/80">
    <div class="flex items-center gap-2">

        {{-- Save --}}
        <button type="button" @click.stop="save()" :disabled="saving || aiLoading"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white
                   hover:bg-primary-500 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1
                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors dark:focus:ring-offset-gray-900">
            <x-tcm::spinner x-show="saving" x-cloak />
            <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <span x-text="saving ? '…' : '{{ $saveBtnLabel }}'"></span>
            <span x-show="hasChanges && !saving" x-cloak class="w-1.5 h-1.5 rounded-full bg-white/60 shrink-0"></span>
        </button>

        {{-- Cancel --}}
        <button type="button" @click.stop="cancel()" :disabled="saving || aiLoading"
            class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400
                   hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700
                   disabled:opacity-50 transition-colors">
            {{ $cancelBtnLabel }}
        </button>

        {{-- AI Fill --}}
        @if ($hasAi)
            <span class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5 shrink-0"></span>
            <button type="button" tabindex="-1" @click.stop="aiTranslate()"
                x-data="{ get canAi() {
                    return !!translations[sourceLocale] &&
                        Object.entries(translations).some(([l, v]) => l !== sourceLocale && !v);
                }}"
                :disabled="saving || aiLoading || !canAi"
                :title="!translations[sourceLocale]
                    ? '{{ $aiNoSourceTitle }}'
                    : '{{ $aiTooltip }}'"
                :class="canAi && !aiLoading
                    ? 'bg-warning-50 dark:bg-warning-950/30 text-warning-700 dark:text-warning-400 ring-1 ring-warning-200 dark:ring-warning-800/40 hover:bg-warning-100 dark:hover:bg-warning-950/50'
                    : 'text-gray-400 dark:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800'"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium
                       focus:outline-none focus:ring-2 focus:ring-warning-400
                       disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                <x-tcm::spinner x-show="aiLoading" x-cloak />
                <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                </svg>
                <span x-text="aiLoading ? '…' : '{{ $aiBtnLabel }}'"></span>
            </button>
        @endif
    </div>

    {{-- Keyboard hints --}}
    <span class="hidden sm:flex items-center gap-1 text-[0.62rem] text-gray-300 dark:text-gray-600 select-none">
        <x-tcm::kbd key="⌘↵" label="save" />
        <x-tcm::kbd key="Esc" label="cancel" :separator="true" />
        @if ($hasAi)
            <x-tcm::kbd key="⌘⇧A" label="AI fill" :separator="true" />
        @endif
        <x-tcm::kbd key="Tab" label="next" :separator="true" />
    </span>
</div>
