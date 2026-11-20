<?php

namespace Statikbe\FilamentTranslationManager\Commands;

use Illuminate\Console\Command;
use Statikbe\FilamentTranslationManager\FilamentTranslationManager;
use Statikbe\FilamentTranslationManager\Support\TranslationHtmlSanitizer;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;
use Statikbe\LaravelChainedTranslator\Exceptions\InvalidTranslationPathException;
use Statikbe\LaravelChainedTranslator\Exceptions\SaveTranslationFileException;
use Statikbe\LaravelChainedTranslator\TranslationGroupFinder;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Sanitizes the translations that were saved before the translation manager sanitized HTML on save.
 */
class SanitizeTranslationsCommand extends Command
{
    protected $signature = 'filament-translation-manager:sanitize
                            {--dry-run : Only list the translations that would change, without saving them}';

    protected $description = 'Remove unsafe HTML from the custom translations';

    public function handle(ChainedTranslationManager $manager, TranslationHtmlSanitizer $sanitizer, TranslationGroupFinder $groupFinder): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $groups = $groupFinder->findIn([(string) $this->laravel->make('chained-translator.path.lang.custom')]);
        $changed = 0;
        $failed = false;

        foreach (FilamentTranslationManager::getLocales() as $locale) {
            foreach ($groups as $group) {
                try {
                    $translations = $manager->getCustomTranslations($locale, $group);

                    foreach ($translations as $key => $translation) {
                        if (! is_string($translation)) {
                            if (is_array($translation)) {
                                $this->components->warn("Skipped [{$locale}] {$group}.{$key}: nested translations are not checked.");
                            }

                            continue;
                        }

                        $sanitized = $sanitizer->sanitize($translation);

                        if ($sanitized === $translation) {
                            continue;
                        }

                        $changed++;
                        $this->line("<comment>[{$locale}] {$group}.{$key}</comment>");
                        $this->line('  <fg=red>- '.OutputFormatter::escape($translation).'</>');
                        $this->line('  <fg=green>+ '.OutputFormatter::escape($sanitized).'</>');

                        if (! $dryRun) {
                            $manager->save($locale, $group, (string) $key, $sanitized);
                        }
                    }
                } catch (InvalidTranslationPathException|SaveTranslationFileException $e) {
                    $failed = true;
                    $this->components->error("[{$locale}] {$group}: {$e->getMessage()}");
                }
            }
        }

        if ($changed === 0) {
            $this->components->info('No translations contain unsafe HTML.');
        } elseif ($dryRun) {
            $this->components->info("{$changed} translation(s) would be sanitized. Run the command without --dry-run to save them.");
        } else {
            $this->components->info("{$changed} translation(s) sanitized.");
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
