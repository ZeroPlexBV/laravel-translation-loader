<?php

namespace Spatie\TranslationLoader;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use ReflectionClass;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslationServiceProvider extends PackageServiceProvider implements DeferrableProvider
{
    /**
     * @param  Package  $package
     *
     * @return void
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-translation-loader')
            ->hasConfigFile()
            ->hasMigrations(
                'create_language_lines_table',
                'alter_language_lines_table_add_column_namespace'
            );

        $this->registerLoader();
        $this->registerTranslator();
    }

    /**
     * Register the translation line loader. This method registers a
     * `TranslationLoaderManager` instead of a simple `FileLoader` as the
     * applications `translation.loader` instance.
     */
    protected function registerLoader(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            $class = config('translation-loader.translation_manager');

            return new $class($app['files'], $this->languagePaths($app['path.lang']));
        });
    }

    /**
     * The folders language files are read from, later ones winning: Laravel's own texts (English),
     * this package's copies of them in other languages, and the application's lang folder. They
     * are passed together because a path added to the loader later would win from the application.
     *
     * @param  string  $applicationPath
     *
     * @return array<string>
     */
    protected function languagePaths(string $applicationPath): array
    {
        $frameworkPath = dirname((new ReflectionClass(FileLoader::class))->getFileName()) . '/lang';

        return [$frameworkPath, dirname(__DIR__) . '/lang', $applicationPath];
    }

    /**
     * @return void
     */
    protected function registerTranslator(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app->getLocale();

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app->getFallbackLocale());

            return $trans;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['translator', 'translation.loader'];
    }
}
