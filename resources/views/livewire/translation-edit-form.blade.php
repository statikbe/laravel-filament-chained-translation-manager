<x-filament::section>
    <x-filament::section.heading>
        {{ $group . ' - '. $translationKey }}
    </x-filament::section.heading>
    <div class="text-base grid">
        @foreach($locales as $locale)
            @php($id = $group.$translationKey.'translations'.$locale)
            <div
                class="flex items-center"
                x-data="{
                    editing: false,
                    multiLineMode: {{ substr_count($translations[$locale] ?? '', "\n") > 0 ? 'true' : 'false' }},
                    openForm() {
                        $dispatch('close-forms');
                        this.editing = true;
                        $nextTick(() => {
                            setTimeout(() => {
                                if (this.multiLineMode) {
                                    $refs.multilineInput.focus();
                                } else {
                                    $refs.input.focus();
                                }
                            }, 50); //Adding a small delay makes this way more consistent
                        });
                    },
                    closeWithSave() {
                        if (this.editing){
                            this.closeEdit();
                            $wire.save(this.locale);
                        }
                    },
                    closeWithCancel() {
                        if (this.editing){
                            this.closeEdit();
                            $wire.cancel();
                        }
                    },
                    closeEdit() {
                        this.editing = false;
                    },
                    toggleMultiLine() {
                        this.multiLineMode = !this.multiLineMode;
                    },
                    locale: '{{ $locale }}',
                }"
                @click.outside="closeWithSave"
                @close-forms.window="closeWithSave()"
            >
                <label
                    class="w-16 font-bold"
                    for="{{ $id }}"
                    x-ref="label"
                    x-bind:tabindex="!editing && '0'"
                    @focus="openForm"
                >
                    {{ $locale }}:
                </label>
                <x-filament::link x-show="!editing" @click.prevent="openForm" class="w-full cursor-pointer">
                    <div class="w-full p-2">
                        @if(isset($translations[$locale]) && !empty(trim($translations[$locale])))
                            {{ $translations[$locale] }}
                        @else
                            <span class="text-gray-400 decoration-gray-400">
                                @lang('filament-translation-manager::messages.missing_translation')
                            </span>
                        @endif
                    </div>
                </x-filament::link>
                <div
                    class="w-full flex items-center space-x-2"
                    x-show="editing">
                    <form @submit.prevent="closeWithSave" class="w-full">
                        <input
                            wire:model.defer="translations.{{ $locale }}"
                            class="block w-full p-2 transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 border-gray-300 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500"
                            id="{{ $id }}"
                            type="text"
                            x-ref="input"
                            x-show="!multiLineMode"
                            x-bind:disabled="multiLineMode"
                        >
                        <textarea
                            wire:model.defer="translations.{{ $locale }}"
                            class="block w-full p-2 transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 border-gray-300 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500"
                            id="{{ $id }}"
                            rows="3"
                            x-ref="multilineInput"
                            x-show="multiLineMode"
                            x-bind:disabled="!multiLineMode"></textarea>
                    </form>
                    <div class="flex items-center align-center">
                        <button @click="toggleMultiLine"
                                title="@lang('filament-translation-manager::messages.toggle_multi_line_btn')"
                                aria-label="@lang('filament-translation-manager::messages.toggle_multi_line_btn')">
                            <x-filament::icon
                                x-show="!multiLineMode"
                                alias="filament-chained-translation-manager::enable-multi-line"
                                icon="heroicon-o-bars-arrow-down"
                                class="w-5 h-5 text-primary-500"/>
                            <x-filament::icon
                                x-show="multiLineMode"
                                alias="filament-chained-translation-manager::disable-multi-line"
                                icon="heroicon-o-bars-arrow-up"
                                class="w-5 h-5 text-primary-500"/>
                        </button>
                    </div>
                    <div class="flex items-center align-center">
                        <button @click="closeWithCancel"
                                title="@lang('filament-translation-manager::messages.cancel_translation_btn')"
                                aria-label="@lang('filament-translation-manager::messages.cancel_translation_btn')">
                            <x-filament::icon
                                alias="filament-chained-translation-manager::cancel-translation"
                                icon="heroicon-o-x-mark"
                                class="w-5 h-5 text-danger-500"/>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament::section>
