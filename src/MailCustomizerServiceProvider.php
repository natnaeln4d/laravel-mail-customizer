<?php
// src/MailCustomizerServiceProvider.php

namespace Natnaeln4d\MailCustomizer;

use Illuminate\Support\ServiceProvider;

class MailCustomizerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
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
        
        $this->mergeConfigFrom(
            __DIR__.'/../config/mail-customizer.php', 'mail-customizer'
        );
    }
}