<?php

namespace Natnaeln4d\MailCustomizer\Tests;

use Natnaeln4d\MailCustomizer\MailCustomizerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [MailCustomizerServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        // Auth is covered by its own test; keep the others focused on behaviour.
        $app['config']->set('mail-customizer.middleware', ['web']);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
