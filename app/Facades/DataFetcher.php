<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DataFetcher extends Facade{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        // CartService::init();
        return App\Services\DataFetcher::class;
    }

}
