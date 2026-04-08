<?php

declare(strict_types=1);

namespace Graystack\Ship24\Tests;

use Graystack\Ship24\Ship24ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [Ship24ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ship24.api_key', 'test-api-key');
        $app['config']->set('ship24.base_url', 'https://api.ship24.com/public/v1');
    }
}
