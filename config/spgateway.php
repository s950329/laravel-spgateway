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
        'MerchantID' => env('SPGATEWAY_MERCHANT_ID', ''),
        'HashKey' => env('SPGATEWAY_HASH_KEY', ''),
        'HashIV' => env('SPGATEWAY_HASH_IV', ''),
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
    'CompanyKey' => env('SPGATEWAY_COMPANY_KEY', ''),
    'CompanyIV' => env('SPGATEWAY_COMPANY_IV', ''),
    'PartnerID' => env('SPGATEWAY_PARTNER_ID', ''),
    'MerchantIDPrefix' => env('SPGATEWAY_MERCHANT_ID_PREFIX', ''),

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
        'MerchantID' => env('SPGATEWAY_RECEIPT_MERCHANT_ID', ''),
    ],
];
