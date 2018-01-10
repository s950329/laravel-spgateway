<?php
/**
 *
 *
 * User: Chu
 * Date: 2018/1/9
 * Time: 下午3:52
 */

namespace LeoChien\Spgateway\Facades;

use Illuminate\Support\Facades\Facade;

class Transfer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'transfer';
    }
}