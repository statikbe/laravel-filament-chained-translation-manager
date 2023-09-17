<x-filament::card>
    <p class="font-bold">{{ $group . ' - '. $translationKey }}</p>
    <div class="text-base grid">
        @foreach($locales as $locale)
            @php($id = $group.$translationKey.'translations'.$locale)
            <div
                class="flex items-center"
                x-data="{
                    editing: false,
                    openForm(){
                        $dispatch('close-forms');
                        this.editing = true;
                        $nextTick(() => {
                            setTimeout(() => {
                                $refs.input.focus();
                            }, 50); //Adding a small delay makes this way more consistent
                        });
                    },
                    closeWithSave(){
                        if (this.editing){
                            this.closeEdit();
                            $wire.save(this.locale);
                        }
                    },
                    closeWithCancel(){
                        if (this.editing){
                            this.closeEdit();
                            $wire.cancel();
                        }
                    },
                    closeEdit() {
                        this.editing = false;
                    },
                    locale: '{{ $locale }}'
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
                    class="block w-full flex items-center space-x-2"
                    x-show="editing"
                >
                    <form @submit.prevent="closeWithSave" class="w-full">
                        <input
                            wire:model.defer="translations.{{ $locale }}"
                            class="{{
                                'block w-full transition duration-75 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 border-gray-300 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 '
                            }}"
                            id="{{ $id }}"
                            type="text"
                            x-ref="input"
                        >
                    </form>
                    <div class="flex items-center align-center">
                        <button @click="closeWithCancel">
                            <x-heroicon-o-x-mark class="w-5 h-5 text-danger-500"/>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament::card>
