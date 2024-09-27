<x-filament::widget class="filament-filament-info-widget">
    <x-filament::card class="relative">
        <div class="relative h-12 flex flex-col space-y-2">
            <div @class([
                'flex items-center space-x-2 rtl:space-x-reverse text-sm font-medium text-gray-500',
                'dark:text-gray-200' => config('filament.dark_mode'),
            ])>
                <span>Translation manager</span>
            </div>
            <div class="text-sm flex space-x-2 rtl:space-x-reverse">
                Missing translations: {{$missingTranslations}}
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
