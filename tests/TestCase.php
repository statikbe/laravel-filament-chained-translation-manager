<?php

namespace Statikbe\FilamentTranslationManager\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Statikbe\FilamentTranslationManager\FilamentTranslationManagerServiceProvider;
use Statikbe\FilamentTranslationManager\Tests\Fixtures\TestPanelProvider;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;
use Statikbe\LaravelChainedTranslator\ChainedTranslatorConfig;
use Statikbe\LaravelChainedTranslator\ChainLoader;
use Statikbe\LaravelChainedTranslator\TranslationServiceProvider;

abstract class TestCase extends Orchestra
{
    public const GATE = 'use-translation-manager';

    protected string $tempPath;

    protected string $langPath;

    protected string $customLangPath;

    protected function setUp(): void
    {
        $this->tempPath = sys_get_temp_dir().'/filament-translation-manager-tests/'.uniqid('', true);
        $this->langPath = $this->tempPath.'/lang';
        $this->customLangPath = $this->tempPath.'/lang-custom';

        $this->createTranslationFiles();

        parent::setUp();

        $this->useTemporaryTranslationDirectories();

        Gate::define(self::GATE, fn ($user) => (bool) ($user->can_translate ?? false));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        // Filament's providers must be registered before Livewire's, like package discovery does in an app,
        // because Filament overrides one of Livewire's bindings.
        return [
            BladeCaptureDirectiveServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            TranslationServiceProvider::class,
            FilamentTranslationManagerServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.locale', 'en');
        $app['config']->set('filament-translation-manager.locales', ['en', 'nl']);
        $app['config']->set('filament-translation-manager.gate', self::GATE);
        $app['config']->set('filament-translation-manager.ignore_groups', ['secret']);

        $app->useLangPath($this->langPath);
    }

    /**
     * Points the chained translator to the temporary lang and lang-custom directories of this test.
     */
    private function useTemporaryTranslationDirectories(): void
    {
        $this->app->instance('chained-translator.path.lang.custom', $this->customLangPath);

        foreach (['translator', 'translation.loader', 'translation.loader.custom', 'translation.loader.default', ChainedTranslationManager::class] as $abstract) {
            $this->app->forgetInstance($abstract);
        }

        $this->app->singleton(ChainedTranslationManager::class, fn ($app) => new ChainedTranslationManager(
            $app->make(Filesystem::class),
            $app->make(ChainLoader::class),
            $this->customLangPath,
            $app->make(ChainedTranslatorConfig::class),
        ));
    }

    private function createTranslationFiles(): void
    {
        $files = new Filesystem;

        foreach (['en', 'nl'] as $locale) {
            $files->ensureDirectoryExists($this->langPath.'/'.$locale);
            $files->ensureDirectoryExists($this->customLangPath.'/'.$locale);
        }

        $files->put($this->langPath.'/en/test.php', "<?php return ['hello' => 'Hello'];");
        $files->put($this->langPath.'/nl/test.php', "<?php return ['hello' => 'Hallo'];");
        $files->put($this->langPath.'/en/secret.php', "<?php return ['key' => 'Secret'];");
    }

    protected function actingAsTranslator(bool $canTranslate = true): static
    {
        return $this->actingAs(new GenericUser(['id' => 1, 'can_translate' => $canTranslate]));
    }

    protected function customTranslationFile(string $locale, string $group): string
    {
        return $this->customLangPath.'/'.$locale.'/'.$group.'.php';
    }
}
