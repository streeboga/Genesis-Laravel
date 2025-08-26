<?php

namespace Streeboga\GenesisLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use Streeboga\Genesis\GenesisClient;

/**
 * @method static \Streeboga\Genesis\AuthClient auth()
 * @method static \Streeboga\Genesis\BillingClient billing()
 * @method static \Streeboga\Genesis\FeaturesClient features()
 * @method static \Streeboga\Genesis\UsersClient users()
 * @method static \Streeboga\Genesis\EmbedClient embed()
 */
class Genesis extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GenesisClient::class;
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();
        if (!$instance) {
            throw new \RuntimeException('GenesisClient is not bound in the container.');
        }

        if (property_exists($instance, $method)) {
            return $instance->{$method};
        }

        return $instance->{$method}(...$args);
    }
}


