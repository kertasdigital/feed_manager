<?php

namespace KertasDigital\FeedManager\Facades;

use Illuminate\Support\Facades\Facade;

class FeedManagerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'FeedManagerFactory';
    }
}
