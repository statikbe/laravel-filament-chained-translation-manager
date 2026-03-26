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
        <x-filament::button
            size="sm"
            type="button"
            @click.stop="save()"
            x-bind:disabled="saving || aiLoading"
        >
            <span class="inline-flex items-center gap-1.5">
                <x-filament::loading-indicator x-show="saving" x-cloak class="w-3.5 h-3.5" />
                <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <span x-text="saving ? '…' : '{{ $saveBtnLabel }}'"></span>
                <span x-show="hasChanges && !saving" x-cloak class="w-1.5 h-1.5 rounded-full bg-white/60 shrink-0"></span>
            </span>
        </x-filament::button>

        {{-- Cancel --}}
        <x-filament::button
            size="sm"
            color="gray"
            type="button"
            @click.stop="cancel()"
            x-bind:disabled="saving || aiLoading"
        >{{ $cancelBtnLabel }}</x-filament::button>

        {{-- AI Fill --}}
        @if ($hasAi)
            <span class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5 shrink-0"></span>
            <x-filament::button
                size="sm"
                color="warning"
                type="button"
                tabindex="-1"
                @click.stop="aiTranslate()"
                x-data="{ get canAi() {
                    return !!translations[sourceLocale] &&
                        Object.entries(translations).some(([l, v]) => l !== sourceLocale && !v);
                }}"
                x-bind:disabled="saving || aiLoading || !canAi"
                x-bind:title="!translations[sourceLocale]
                    ? '{{ $aiNoSourceTitle }}'
                    : '{{ $aiTooltip }}'"
            >
                <span class="inline-flex items-center gap-1.5">
                    <x-filament::loading-indicator x-show="aiLoading" x-cloak class="w-3.5 h-3.5" />
                    <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                    </svg>
                    <span x-text="aiLoading ? '…' : '{{ $aiBtnLabel }}'"></span>
                </span>
            </x-filament::button>
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
