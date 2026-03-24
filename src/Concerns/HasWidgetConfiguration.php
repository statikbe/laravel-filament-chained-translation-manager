<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Concerns;

trait HasWidgetConfiguration
{
    protected bool $widgetEnabled = false;

    protected ?string $widgetGate = null;

    protected ?int $widgetSort = null;

    public function enableWidget(?string $gate = null, ?int $sort = null): static
    {
        $this->widgetEnabled = true;
        $this->widgetGate = $gate;
        $this->widgetSort = $sort;

        return $this;
    }

    public function disableWidget(): static
    {
        $this->widgetEnabled = false;

        return $this;
    }

    public function isWidgetEnabled(): bool
    {
        return $this->widgetEnabled;
    }

    public function getWidgetGate(): ?string
    {
        return $this->widgetGate;
    }

    public function getWidgetSort(): ?int
    {
        return $this->widgetSort;
    }
}
