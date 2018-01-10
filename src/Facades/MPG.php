<?php
namespace LeoChien\Spgateway\Facades;

use Illuminate\Support\Facades\Facade;

class MPG extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mpg';
    }
}