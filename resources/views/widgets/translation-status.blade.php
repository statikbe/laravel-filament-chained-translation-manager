<x-filament::widget class="filament-filament-info-widget">
    <x-filament::card class="relative">
        <div class="relative h-12 flex flex-col space-y-2">
            <div @class([
                'flex items-center space-x-2 rtl:space-x-reverse text-sm font-medium text-gray-500',
                'dark:text-gray-200' => config('filament.dark_mode'),
            ])>
                <x-dynamic-component :component="'heroicon-o-translate'" class="w-4 h-4" />

                <span>Translation manager</span>
            </div>
            <div class="filament-tables-icon-column filament-tables-icon-column-size-{$size}">
                <x-dynamic-component :component="'heroicon-o-check-circle'" class="w-6 h-6 text-primary-600"/>

            </div>

            <div class="text-sm flex space-x-2 rtl:space-x-reverse">
                <a
                    href="https://filamentphp.com/docs"
                    target="_blank"
                    rel="noopener noreferrer"
                    @class([
                        'text-gray-600 hover:text-primary-500 focus:outline-none focus:underline',
                        'dark:text-gray-300 dark:hover:text-primary-500' => config('filament.dark_mode'),
                    ])
                >
                    Missing translations: {{$missingTranslations}}
                </a>

                <span>
                    &bull;
                </span>

                <a
                    href="https://github.com/filamentphp/filament"
                    target="_blank"
                    rel="noopener noreferrer"
                    @class([
                        'text-gray-600 hover:text-primary-500 focus:outline-none focus:underline',
                        'dark:text-gray-300 dark:hover:text-primary-500' => config('filament.dark_mode'),
                    ])
                >
                    MANAGER
                </a>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
