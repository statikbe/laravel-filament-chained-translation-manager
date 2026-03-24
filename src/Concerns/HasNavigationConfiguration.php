<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Concerns;

use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

trait HasNavigationConfiguration
{
    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected string|BackedEnum|Htmlable|null $navigationIcon = Heroicon::OutlinedLanguage;

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function navigationIcon(string|BackedEnum|Htmlable|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? trans('filament-translation-manager::messages.navigation_group');
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->navigationIcon;
    }
}
