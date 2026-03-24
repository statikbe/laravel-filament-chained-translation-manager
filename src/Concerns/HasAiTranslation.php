<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Concerns;

use Statikbe\AiTranslation\AiTranslationService;

trait HasAiTranslation
{
    protected bool $aiEnabled = false;

    protected ?string $aiDriver = null;

    protected bool $aiRowAction = true;

    protected bool $aiModalAction = true;

    protected bool $aiHeaderAction = true;

    protected bool $aiBulkAction = true;

    /**
     * Enable AI translation features.
     *
     * Requires `statikbe/laravel-ai-translation` to be installed.
     *
     * @param  array<string, bool>  $options  Configuration options:
     *                                        - rowAction: Show per-row "AI Fill" action button (default: true)
     *                                        - modalAction: Show "AI Fill All" button inside the edit modal (default: true)
     *                                        - headerAction: Show "Translate All Missing" button in the table toolbar (default: true)
     *                                        - bulkAction: Show "AI Translate Selected" bulk action (default: true)
     */
    public function enableAiTranslation(array $options = []): static
    {
        $this->aiEnabled = true;
        $this->aiRowAction = $options['rowAction'] ?? true;
        $this->aiModalAction = $options['modalAction'] ?? true;
        $this->aiHeaderAction = $options['headerAction'] ?? true;
        $this->aiBulkAction = $options['bulkAction'] ?? true;

        return $this;
    }

    /**
     * Disable AI translation features.
     */
    public function disableAiTranslation(): static
    {
        $this->aiEnabled = false;

        return $this;
    }

    /**
     * Set the AI driver override (null = use ai-translation config default).
     */
    public function aiDriver(?string $driver): static
    {
        $this->aiDriver = $driver;

        return $this;
    }

    /**
     * Whether AI translation is enabled and the AI translation package is installed.
     */
    public function isAiEnabled(): bool
    {
        return $this->aiEnabled && class_exists(AiTranslationService::class);
    }

    public function getAiDriver(): ?string
    {
        return $this->aiDriver;
    }

    public function hasAiRowAction(): bool
    {
        return $this->isAiEnabled() && $this->aiRowAction;
    }

    public function hasAiModalAction(): bool
    {
        return $this->isAiEnabled() && $this->aiModalAction;
    }

    public function hasAiHeaderAction(): bool
    {
        return $this->isAiEnabled() && $this->aiHeaderAction;
    }

    public function hasAiBulkAction(): bool
    {
        return $this->isAiEnabled() && $this->aiBulkAction;
    }
}
