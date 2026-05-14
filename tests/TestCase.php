<?php

namespace NextMigrant\Plunk\Tests;

use NextMigrant\Plunk\PlunkServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PlunkServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('plunk.secret_key', 'sk_test_secret');
        config()->set('plunk.public_key', 'pk_test_public');
        config()->set('plunk.base_api_url', 'https://next-api.useplunk.com');
        config()->set('plunk.timeout', 30);
        config()->set('plunk.retry.times', 1);
        config()->set('plunk.retry.sleep', 0);
    }
}
