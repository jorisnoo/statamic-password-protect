<?php

namespace Noo\PasswordProtect\Tests;

use Illuminate\Foundation\Application;
use Noo\PasswordProtect\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $app['files']->delete($app->resourcePath('addons/statamic-password-protect.yaml'));
        $app['config']->set('statamic.api.enabled', true);
        $app['config']->set('statamic.graphql.enabled', true);
    }

    protected function tearDown(): void
    {
        try {
            $this->app['files']->delete($this->app->resourcePath('addons/statamic-password-protect.yaml'));
        } finally {
            parent::tearDown();
        }
    }
}
