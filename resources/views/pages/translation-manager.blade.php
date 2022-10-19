<x-filament::page>
    <form wire:submit.prevent="filterTranslations">
        {{ $this->form }}

        <button type="submit">
            Filter
        </button>
    </form>

    @foreach($translations as $translation)
        <x-filament::card
            heading="{{ $translation['title'] }}"
        >
            @foreach($translation['translations'] as $locale => $translation)
                <p><strong>{{ $locale }}:</strong> {{ $translation }}</p>
            @endforeach
        </x-filament::card>
    @endforeach
</x-filament::page>
