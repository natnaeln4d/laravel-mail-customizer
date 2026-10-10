<?php

namespace Natnaeln4d\MailCustomizer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MailCustomizerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::group([
            'prefix' => config('mail-customizer.route_prefix', 'api/mail-customizer'),
            'middleware' => config('mail-customizer.middleware', ['web', 'auth']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        });
        if (config('mail-customizer.ui.enabled', true)) {
            Route::group([
                'prefix' => config('mail-customizer.ui.path', 'mail-customizer'),
                'middleware' => config('mail-customizer.ui.middleware', config('mail-customizer.middleware', ['web', 'auth'])),
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/routes/web.php');
            });
        }

        $this->loadViewsFrom(__DIR__.'/Resources/views', 'mail-customizer');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        $this->publishes([
            __DIR__.'/../config/mail-customizer.php' => config_path('mail-customizer.php'),
        ], 'config');
        
        $this->publishes([
            __DIR__.'/../dist/mail-customizer.js' => public_path('vendor/mail-customizer/js/mail-customizer.js'),
        ], 'assets');
        
        $this->publishes([
            __DIR__.'/Resources/views' => resource_path('views/vendor/mail-customizer'),
        ], 'views');
    }

    public function register()
    {
        $this->app->singleton('mail-customizer', function ($app) {
            return new Services\TemplateCompiler();
        });
        
        $this->app->singleton(Rendering\EmailRenderer::class);
        $this->app->alias(Rendering\EmailRenderer::class, 'mail-renderer');

        $this->app->singleton(Services\DragDropBuilder::class);
        $this->app->alias(Services\DragDropBuilder::class, 'mail-builder');
        
        $this->mergeConfigFrom(
            __DIR__.'/../config/mail-customizer.php', 'mail-customizer'
        );
    }
}