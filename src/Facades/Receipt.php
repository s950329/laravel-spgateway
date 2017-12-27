<?php
/**
 *
 *
 * User: Chu
 * Date: 2017/12/26
 * Time: 下午9:09
 */
namespace LeoChien\Spgateway\Facades;

use Illuminate\Support\Facades\Facade;

class Spgateway extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'spgateway';
    }
}