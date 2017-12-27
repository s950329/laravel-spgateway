<?php

return [

    /*
    |--------------------------------------------------
    | For MPG Trade API
    |--------------------------------------------------
    |
    | 這是用來進行MPG交易的相關設定，每項皆為必填
    |
     */
    'mpg' => [
        'Version' => env('SPGATEWAY_MPG_VERSION', 1.2),
        'MerchantID' => env('SPGATEWAY_MERCHANT_ID', 'MS3133867'),
        'HashKey' => env('SPGATEWAY_HASH_KEY', 'hKzMezBAlVVBr3LCti43H6KzHq8oSGFa'),
        'HashIV' => env('SPGATEWAY_HASH_IV', 'reXFNyf9taKUewxH'),
        'ReturnURL' => env('SPGATEWAY_RETURN_URL', ''),
        'NotifyURL' => env('SPGATEWAY_NOTIFY_URL', ''),
    ],

    /*
    |--------------------------------------------------
    | For Create Merchant API
    |--------------------------------------------------
    |
    | 這是用來建立智付通商店的相關設定，每項皆為必填
    |
     */
    'CompanyKey' => env('SPGATEWAY_COMPANY_KEY', 'mHKhUMKlGUjVpT3xxFJPsWHUdBd8jUeG'),
    'CompanyIV' => env('SPGATEWAY_COMPANY_IV', 'CZYwLISsDbn25rzD'),
    'PartnerID' => env('SPGATEWAY_PARTNER_ID', 'hourmaster'),
    'MerchantIDPrefix' => env('SPGATEWAY_MERCHANT_ID_PREFIX', 'HMS'),

    /*
    |--------------------------------------------------
    | For Create Receipt API
    |--------------------------------------------------
    |
    | 這是用來開立智付通發票的相關設定，每項皆為必填
    |
     */
    'receipt' => [
        'HashKey' => env('SPGATEWAY_RECEIPT_KEY'),
        'HashIV' => env('SPGATEWAY_RECEIPT_IV'),
        'MerchantID' => env('SPGATEWAY_RECEIPT_MERCHANT_ID', '3836438'),
    ],
];
