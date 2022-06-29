<?php

namespace Partymeister\Frontend\Providers;

use Illuminate\Support\ServiceProvider;
use Partymeister\Frontend\Console\Commands\PartymeisterFrontendCachePhotowallCommand;

/**
 * Class PartymeisterServiceProvider
 */
class PartymeisterServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->config();
        $this->routes();
        $this->routeModelBindings();
        $this->translations();
        $this->views();
        $this->navigationItems();
        $this->permissions();
        $this->registerCommands();
        $this->migrations();
        $this->publishResourceAssets();
        $this->components();
        $this->templates();
        merge_local_config_with_db_configuration_variables('partymeister-frontend');
    }

    /**
     * Set configuration files for publishing
     */
    public function config()
    {
    }

    /**
     * Set routes
     */
    public function routes()
    {
        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../../routes/web.php';
            require __DIR__.'/../../routes/api.php';
        }
    }

    /**
     * Add route model bindings
     */
    public function routeModelBindings()
    {
    }

    /**
     * Set translation path
     */
    public function translations()
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'partymeister-frontend');

        $this->publishes([
            __DIR__.'/../../lang' => resource_path('lang/vendor/partymeister-frontend'),
        ], 'partymeister-frontend-translations');
    }

    /**
     * Set view path
     */
    public function views()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'partymeister-frontend');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/partymeister-frontend'),
        ], 'partymeister-frontend-views');
    }

    /**
     * Merge backend navigation items from configuration file
     */
    public function navigationItems()
    {
    }

    /**
     * Merge permission config file
     */
    public function permissions()
    {
    }

    /**
     * Register artisan commands
     */
    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PartymeisterFrontendCachePhotowallCommand::class,
            ]);
        }
    }

    /**
     * Set migration path
     */
    public function migrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Publish all necessary asset resources
     */
    public function publishResourceAssets()
    {
        $assets = [
            __DIR__.'/../../resources/assets/sass' => resource_path('assets/sass'),
            __DIR__.'/../../resources/assets/npm'  => resource_path('assets/npm'),
            __DIR__.'/../../resources/assets/js'   => resource_path('assets/js'),
        ];

        $this->publishes($assets, 'partymeister-frontend-install-resources');
    }

    /**
     * Register components from config file
     */
    public function components()
    {
        $config = $this->app['config']->get('motor-cms-page-components', []);
        $this->app['config']->set('motor-cms-page-components', array_replace_recursive(require __DIR__.'/../../config/motor-cms-page-components.php', $config));
    }

    /**
     * Register templates from config file
     */
    public function templates()
    {
        $config = $this->app['config']->get('motor-cms-page-templates', []);
        $this->app['config']->set('motor-cms-page-templates', array_replace_recursive(require __DIR__.'/../../config/motor-cms-page-templates.php', $config));
    }
}
