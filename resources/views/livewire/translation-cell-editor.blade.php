@php
    use Illuminate\Support\Str;
    $editorId   = $group . '.' . $translationKey;
    $initial    = collect($locales)->mapWithKeys(fn($l) => [$l => $translations[$l] ?? ''])->all();
    $totalCount = count($locales);
    $sourceText = $translations[$sourceLocale] ?? '';
@endphp

<div
    x-data="{
        open: false, saving: false, aiLoading: false, copied: false,
        translations: @js($initial),
        initial:      @js($initial),
        id:           @js($editorId),
        sourceLocale: @js($sourceLocale),
        sourceText:   @js($sourceText),

        get translatedCount() {
            return Object.values(this.translations).filter(v => v && v.trim() !== '').length;
        },

        get hasChanges() {
            return JSON.stringify(this.translations) !== JSON.stringify(this.initial);
        },

        get canAi() {
            return !!this.translations[this.sourceLocale] &&
                Object.entries(this.translations).some(([l, v]) => l !== this.sourceLocale && !v);
        },

        toggle() {
            if (this.open) { this.cancel(); return; }
            this.open = true;
            $dispatch('open-translation-editor', { id: this.id });
            $nextTick(() => $el.querySelector('textarea')?.focus());
        },

        async save() {
            const changed = Object.fromEntries(
                Object.entries(this.translations).filter(([k, v]) => v !== this.initial[k])
            );
            if (!Object.keys(changed).length) { this.open = false; return; }
            this.saving = true;
            try {
                await $wire.save(changed);
                this.initial = { ...this.translations };
                this.open = false;
            } finally { this.saving = false; }
        },

        cancel() { this.translations = { ...this.initial }; this.open = false; },

        async aiTranslate() {
            const missing = Object.entries(this.translations)
                .filter(([l, v]) => l !== this.sourceLocale && !v).map(([l]) => l);
            if (!missing.length) return;
            this.aiLoading = true;
            try {
                Object.assign(this.translations,
                    await $wire.aiTranslateMissing(
                        this.translations[this.sourceLocale] || this.sourceText || '',
                        this.sourceLocale,
                        missing
                    )
                );
            } finally { this.aiLoading = false; }
        },

        async aiTranslateLocale(locale) {
            if (!this.translations[this.sourceLocale] && !this.sourceText) return;
            this.aiLoading = true;
            try {
                Object.assign(this.translations,
                    await $wire.aiTranslateMissing(
                        this.translations[this.sourceLocale] || this.sourceText,
                        this.sourceLocale,
                        [locale]
                    )
                );
            } finally { this.aiLoading = false; }
        },

        async copyKey() {
            try {
                await navigator.clipboard.writeText(this.id);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch {}
        },

        navigate(dir, focusTarget = 'first') {
            const all = [...document.querySelectorAll('[data-translation-id]')];
            const i   = all.findIndex(el => el.dataset.translationId === this.id);
            if (i === -1) return;
            const next = dir === 'next' ? Math.min(i + 1, all.length - 1) : Math.max(i - 1, 0);
            if (next === i) return;
            this.cancel();
            $nextTick(() => {
                const target = all[next];
                target?.querySelector('[data-toggle]')?.click();
                target?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                setTimeout(() => {
                    const tas = target?.querySelectorAll('textarea');
                    (focusTarget === 'last' ? tas?.[tas.length - 1] : tas?.[0])?.focus();
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

    {{-- ── Summary row (always visible, click to toggle) ─────────────── --}}
    <button type="button" data-toggle @click="toggle()"
        class="group w-full text-left focus:outline-none py-2"
    >
        {{-- Row 1: key + copy + chevron --}}
        <div class="flex items-start gap-2">
            <span class="flex-1 inline-flex items-start gap-1 font-mono text-[0.7rem] leading-snug break-all min-w-0">
                <span class="shrink-0 text-gray-400 dark:text-gray-600">{{ $group }}.</span><span
                    class="font-semibold text-gray-600 dark:text-gray-400">{{ $translationKey }}</span>
                <x-tcm::copy-button
                    :copy-label="trans('filament-translation-manager::messages.copy_key')"
                    :copied-label="trans('filament-translation-manager::messages.key_copied')"
                />
            </span>

            {{-- Chevron --}}
            <span class="shrink-0 mt-0.5 transition-transform duration-200" :class="open && 'rotate-180'">
                <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-500 transition-colors"
                    fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </span>
        </div>

        {{-- Row 2: source preview + locale pills + progress badge --}}
        <div class="flex items-center gap-2 mt-1">
            <span class="flex-1 min-w-0 text-sm text-gray-600 dark:text-gray-300 truncate leading-tight">
                @if (filled($sourceText))
                    {{ Str::limit($sourceText, 90) }}
                @else
                    <em class="text-gray-300 dark:text-gray-600 not-italic">—</em>
                @endif
            </span>

            <span class="hidden sm:flex items-center gap-1.5 shrink-0">
                @foreach ($locales as $locale)
                    <x-tcm::locale-pill
                        :locale="$locale"
                        :translated-label="trans('filament-translation-manager::messages.translated')"
                        :missing-label="trans('filament-translation-manager::messages.missing_translation')"
                    />
                @endforeach
            </span>

            <x-tcm::progress-badge :total="$totalCount" />
        </div>
    </button>

    {{-- ── Expandable editor ──────────────────────────────────────────── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="overflow-hidden"
    >
        <div class="h-px bg-gray-200/70 dark:bg-gray-700/60 mb-3"></div>

        <div class="pb-4 space-y-3">
            @foreach ($locales as $locale)
                <x-tcm::locale-field
                    :locale="$locale"
                    :is-first="$loop->first"
                    :is-last="$loop->last"
                    :has-ai="$hasAi"
                    :set-as-source-label="trans('filament-translation-manager::messages.set_as_source')"
                    :translated-label="trans('filament-translation-manager::messages.translated')"
                    :missing-label="trans('filament-translation-manager::messages.missing_translation')"
                    :missing-placeholder="trans('filament-translation-manager::messages.missing_translation')"
                />
            @endforeach

            <x-tcm::editor-footer
                :has-ai="$hasAi"
                :save-btn-label="trans('filament-translation-manager::messages.save_btn')"
                :cancel-btn-label="trans('filament-translation-manager::messages.cancel_translation_btn')"
                :ai-btn-label="trans('filament-translation-manager::messages.ai_translate_row_action')"
                :ai-no-source-title="trans('filament-translation-manager::messages.ai_translate_no_source')"
                :ai-tooltip="trans('filament-translation-manager::messages.ai_translate_row_action_tooltip')"
            />
        </div>
    </div>

</div>
