<?php

namespace LeoChien\Spgateway;

use LeoChien\Spgateway\Libraries\Helpers;

class Charge
{
    protected $helpers;

    public function __construct(Helpers $helpers)
    {
        if (env('APP_ENV') === 'production') {
            $this->apiUrl['MPG_API']
                = 'https://core.spgateway.com/MPG/mpg_gateway';
            $this->apiUrl['QUERY_TRADE_INFO_API']
                = 'https://core.spgateway.com/API/QueryTradeInfo';
        } else {
            $this->apiUrl['MPG_API']
                = 'https://ccore.spgateway.com/MPG/mpg_gateway';
            $this->apiUrl['QUERY_TRADE_INFO_API']
                = 'https://ccore.spgateway.com/API/QueryTradeInfo';
        }

        $this->helpers = $helpers;
    }

    public function create()
    {

    }
}