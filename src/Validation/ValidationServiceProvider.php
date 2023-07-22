<?php

namespace Triangle\MongoDB\Validation;

use Illuminate\Validation\ValidationServiceProvider as BaseProvider;

/**
 *
 */
class ValidationServiceProvider extends BaseProvider
{
    /**
     * @return void
     */
    protected function registerPresenceVerifier()
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']);
        });
    }
}
