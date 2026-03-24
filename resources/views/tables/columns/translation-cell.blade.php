@php
    use Illuminate\Support\Str;

    $state          = $getState();
    $locales        = $state['locales'];
    $group          = $state['group'];
    $translationKey = $state['translation_key'];
    $translations   = $state['translations'];
    $sourceLocale   = $state['source_locale'];
    $hasAi          = $state['has_ai'] ?? false;
    $aiDriver       = $state['ai_driver'] ?? null;
    $editorId       = $group . '.' . $translationKey;
    $sourceText     = $translations[$sourceLocale] ?? '';

    $initial = collect($locales)
        ->mapWithKeys(fn ($locale) => [$locale => $translations[$locale] ?? ''])
        ->all();

    $translatedCount = count(array_filter($initial, fn ($v) => filled($v)));
    $totalCount      = count($locales);
    $allDone         = $translatedCount === $totalCount;
    $noneDone        = $translatedCount === 0;
@endphp

{{--
    The outer div owns the open-state background so both the summary bar and
    the expanded content share the same background — making the row feel like
    one expanding unit rather than a button + floating card.
--}}
<div
    x-data="{
        open:      false,
        saving:    false,
        aiLoading: false,
        translations: @js($initial),
        initial:      @js($initial),
        id:           @js($editorId),

        toggle() {
            if (this.open) {
                this.cancel();
            } else {
                this.open = true;
                $dispatch('open-translation-editor', { id: this.id });
                $nextTick(() => {
                    const first = $el.querySelector('textarea');
                    if (first) first.focus();
                });
            }
        },

        async save() {
            const changed = Object.fromEntries(
                Object.entries(this.translations).filter(
                    ([locale, val]) => val !== this.initial[locale]
                )
            );

            if (Object.keys(changed).length === 0) {
                this.open = false;
                return;
            }

            this.saving = true;
            try {
                await $wire.saveInlineTranslation(@js($group), @js($translationKey), changed);
                this.initial = { ...this.translations };
                this.open = false;
            } finally {
                this.saving = false;
            }
        },

        cancel() {
            this.translations = { ...this.initial };
            this.open = false;
        },

        async aiTranslate() {
            // Skip the source locale — only fill missing translator locales.
            const missing = Object.entries(this.translations)
                .filter(([locale, val]) => locale !== @js($sourceLocale) && !val)
                .map(([locale]) => locale);

            if (missing.length === 0) return;

            this.aiLoading = true;
            try {
                const filled = await $wire.aiTranslateMissingInline(
                    @js($sourceText),
                    @js($sourceLocale),
                    missing,
                    @js($aiDriver),
                );
                Object.assign(this.translations, filled);
            } finally {
                this.aiLoading = false;
            }
        },

        navigate(direction, focusTarget = 'first') {
            const all = Array.from(document.querySelectorAll('[data-translation-id]'));
            const currentIndex = all.findIndex(el => el.dataset.translationId === this.id);
            if (currentIndex === -1) return;

            const nextIndex = direction === 'next'
                ? Math.min(currentIndex + 1, all.length - 1)
                : Math.max(currentIndex - 1, 0);

            if (nextIndex === currentIndex) return;

            this.cancel();
            $nextTick(() => {
                const target = all[nextIndex];
                const btn = target?.querySelector('[data-toggle]');
                if (btn) {
                    btn.click();
                    target.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
                // Wait for x-collapse animation before focusing
                setTimeout(() => {
                    const textareas = target?.querySelectorAll('textarea');
                    if (textareas?.length) {
                        const ta = focusTarget === 'last'
                            ? textareas[textareas.length - 1]
                            : textareas[0];
                        ta?.focus();
                    }
                }, 200);
            });
        },
    }"
    data-translation-id="{{ $editorId }}"
    x-on:open-translation-editor.window="if ($event.detail.id !== id) { translations = { ...initial }; open = false; }"
    x-on:keydown.escape.window="if (open) cancel()"
    class="w-full -mx-2 px-2 rounded-lg transition-colors duration-150"
    :class="open ? 'bg-gray-50 dark:bg-white/[0.03]' : 'hover:bg-gray-50/70 dark:hover:bg-white/[0.02]'"
>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- SUMMARY BAR — two-line layout, key always fully visible            --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <button
        type="button"
        data-toggle
        @click="toggle()"
        class="group w-full text-left focus:outline-none py-2"
    >
        {{-- Line 1: full translation key + chevron --}}
        <div class="flex items-start gap-2">
            <span class="flex-1 font-mono text-[0.7rem] font-semibold text-gray-500 dark:text-gray-400 break-all leading-snug">{{ $editorId }}</span>
            <span class="shrink-0 mt-0.5 transition-transform duration-200" :class="open && 'rotate-180'">
                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
        </div>

        {{-- Line 2: source snippet + locale pills + progress badge --}}
        <div class="flex items-center gap-2 mt-1">
            <span class="flex-1 min-w-0 text-sm text-gray-600 dark:text-gray-300 truncate leading-tight">
                @if (filled($sourceText))
                    {{ Str::limit($sourceText, 90) }}
                @else
                    <em class="text-gray-300 dark:text-gray-600 not-italic">—</em>
                @endif
            </span>

            {{-- Per-locale status pills --}}
            <span class="hidden sm:flex items-center gap-1.5 shrink-0">
                @foreach ($locales as $locale)
                    @php $done = filled($initial[$locale]); @endphp
                    <span
                        @class([
                            'inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[0.6rem] font-mono font-bold uppercase leading-none',
                            'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-400 ring-1 ring-success-200 dark:ring-success-800/40' => $done,
                            'bg-danger-50 text-danger-400 dark:bg-danger-900/20 dark:text-danger-500 ring-1 ring-danger-100 dark:ring-danger-800/30' => !$done,
                        ])
                        title="{{ strtoupper($locale) }}: {{ $done ? trans('filament-translation-manager::messages.translated') : trans('filament-translation-manager::messages.missing_translation') }}"
                    >
                        <span @class(['w-1.5 h-1.5 rounded-full', 'bg-success-500' => $done, 'bg-danger-300 dark:bg-danger-600' => !$done])></span>
                        {{ strtoupper($locale) }}
                    </span>
                @endforeach
            </span>

            {{-- Progress badge --}}
            <span
                @class([
                    'shrink-0 tabular-nums text-[0.7rem] font-semibold px-2 py-0.5 rounded-full ring-1 leading-tight',
                    'bg-success-50 text-success-700 ring-success-200 dark:bg-success-900/30 dark:text-success-400 dark:ring-success-800/40' => $allDone,
                    'bg-warning-50 text-warning-700 ring-warning-200 dark:bg-warning-900/30 dark:text-warning-400 dark:ring-warning-800/40' => !$allDone && !$noneDone,
                    'bg-danger-50 text-danger-600 ring-danger-200 dark:bg-danger-900/30 dark:text-danger-400 dark:ring-danger-800/40' => $noneDone,
                ])
            >{{ $translatedCount }}/{{ $totalCount }}</span>
        </div>
    </button>

    {{-- ════════════════════════════════════════════════════════════════════ --}}
    {{-- EXPANDED EDITOR — collapses smoothly                               --}}
    {{-- ════════════════════════════════════════════════════════════════════ --}}
    <div x-show="open" x-collapse class="overflow-hidden">

        {{-- Thin rule separating summary bar from editing fields --}}
        <div class="h-px bg-gray-200/70 dark:bg-gray-700/60 mb-3"></div>

        <div class="pb-4 space-y-3">

            {{-- ── Locale text fields (source locale is first in the list) ── --}}
            @foreach ($locales as $locale)
                @php $isSource = $locale === $sourceLocale; @endphp
                <div class="group/field">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span
                            class="inline-flex items-center rounded px-1.5 py-0.5 font-mono text-[0.65rem] font-bold uppercase tracking-wider transition-colors"
                            :class="translations['{{ $locale }}']
                                ? 'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-400'
                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
                        >{{ $locale }}</span>

                        @if ($isSource)
                            <span class="text-[0.6rem] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">source</span>
                        @endif

                        <span
                            class="text-[0.65rem] font-medium transition-colors"
                            :class="translations['{{ $locale }}']
                                ? 'text-success-600 dark:text-success-400'
                                : 'text-gray-400 dark:text-gray-500'"
                            x-text="translations['{{ $locale }}']
                                ? '{{ trans('filament-translation-manager::messages.translated') }}'
                                : '{{ trans('filament-translation-manager::messages.missing_translation') }}'"
                        ></span>
                    </div>

                    <textarea
                        x-model="translations['{{ $locale }}']"
                        @keydown.meta.enter.prevent="save()"
                        @keydown.ctrl.enter.prevent="save()"
                        @keydown.meta.shift.a.prevent="aiTranslate()"
                        @keydown.ctrl.shift.a.prevent="aiTranslate()"
                        @if ($loop->first) @keydown.shift.tab.prevent="navigate('prev', 'last')" @endif
                        @if ($loop->last) @keydown.tab="if (!$event.shiftKey) { $event.preventDefault(); navigate('next', 'first'); }" @endif
                        rows="2"
                        class="block w-full rounded-lg text-sm shadow-sm transition-colors resize-y
                               bg-white dark:bg-gray-900
                               focus:outline-none focus:ring-2 focus:ring-primary-500/25 focus:border-primary-400
                               dark:text-white dark:placeholder-gray-600 dark:focus:border-primary-500"
                        :class="translations['{{ $locale }}']
                            ? 'border border-gray-200 dark:border-gray-700'
                            : 'border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50'"
                        placeholder="{{ trans('filament-translation-manager::messages.missing_translation') }}"
                    ></textarea>
                </div>
            @endforeach

            {{-- ── Footer: save / cancel / AI fill + keyboard hints ── --}}
            <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800/80">
                <div class="flex items-center gap-2">
                    {{-- Save --}}
                    <button
                        type="button"
                        tabindex="-1"
                        @click.stop="save()"
                        :disabled="saving || aiLoading"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white
                               hover:bg-primary-500 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1
                               disabled:opacity-50 disabled:cursor-not-allowed transition-colors dark:focus:ring-offset-gray-900"
                    >
                        <svg x-show="saving" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span x-text="saving ? '…' : '{{ trans('filament-translation-manager::messages.save_btn') }}'"></span>
                    </button>

                    {{-- Cancel --}}
                    <button
                        type="button"
                        tabindex="-1"
                        @click.stop="cancel()"
                        :disabled="saving || aiLoading"
                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400
                               hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700
                               disabled:opacity-50 transition-colors"
                    >
                        {{ trans('filament-translation-manager::messages.cancel_translation_btn') }}
                    </button>

                    {{-- AI Fill — only rendered when AI is enabled for this plugin --}}
                    @if ($hasAi && filled($sourceText))
                        <span class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5 shrink-0"></span>

                        <button
                            type="button"
                            tabindex="-1"
                            @click.stop="aiTranslate()"
                            :disabled="saving || aiLoading"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-warning-600 dark:text-warning-400
                                   hover:bg-warning-50 dark:hover:bg-warning-950/30 focus:outline-none focus:ring-2 focus:ring-warning-400
                                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            title="{{ trans('filament-translation-manager::messages.ai_translate_row_action_tooltip') }}"
                        >
                            <svg x-show="aiLoading" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!aiLoading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                            </svg>
                            <span x-text="aiLoading ? '…' : '{{ trans('filament-translation-manager::messages.ai_translate_row_action') }}'"></span>
                        </button>
                    @endif
                </div>

                {{-- Keyboard hints --}}
                <span class="hidden sm:flex items-center gap-1 text-[0.62rem] text-gray-300 dark:text-gray-600 select-none">
                    <kbd class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-1 py-0.5 font-mono text-[0.6rem]">⌘↵</kbd>
                    <span>save</span>
                    <span class="mx-0.5">·</span>
                    <kbd class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-1 py-0.5 font-mono text-[0.6rem]">Esc</kbd>
                    <span>cancel</span>
                    @if ($hasAi && filled($sourceText))
                        <span class="mx-0.5">·</span>
                        <kbd class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-1 py-0.5 font-mono text-[0.6rem]">⌘⇧A</kbd>
                        <span>AI fill</span>
                    @endif
                    <span class="mx-0.5">·</span>
                    <kbd class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-1 py-0.5 font-mono text-[0.6rem]">Tab</kbd>
                    <span>next</span>
                </span>
            </div>

        </div>
    </div>

</div>
