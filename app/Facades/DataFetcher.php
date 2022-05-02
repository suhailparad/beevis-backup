<?php

namespace App\Facades;

use App\Services\DataFetcher as ServicesDataFetcher;
use Illuminate\Support\Facades\Facade;

class DataFetcher extends Facade{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ServicesDataFetcher::class;
    }

}
