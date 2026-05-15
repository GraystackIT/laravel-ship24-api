<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Tests;

use GraystackIT\Ship24\Ship24ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [Ship24ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ship24.api_key', config('ship24.api_key'));
        $app['config']->set('ship24.base_url', config('ship24.base_url'));
    }
}
