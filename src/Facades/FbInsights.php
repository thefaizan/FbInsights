<?php

namespace SmartHub\FbInsights\Facades;

use Illuminate\Support\Facades\Facade;

class FbInsights extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fbinsights';
    }
}
