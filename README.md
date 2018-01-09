# Laravel Spgateway

Laravel Spgateway是一個開源的 [智付通](https://www.spgateway.com/) 非官方套件

## 要求

1. PHP >= 7
2. Laravel >= 5
3. Composer

## 安裝

```
$ composer require "leochien/spgateway"
```

## 配置

### Laravel 應用

1. 在 `config/app.php` 註冊 ServiceProvider 和 Facade (Laravel 5.5 無須手動註冊)

```php
'providers' => [
    // ...
    LeoChien\Spgateway\SpgatewayServiceProvider::class,
],
'aliases' => [
    // ...
    'Spgateway' => LeoChien\Spgateway\SpgatewayFacade::class,
],
```

2. 創建設定檔：

```shell
php artisan vendor:publish --provider="LeoChien\Spgateway\SpgatewayServiceProvider"
```

3. 修改 `config/spgateway.php` 中對應的參數。

4. 或在 `.env` 中加入下列設定

```
# 金流系統設定
SPGATEWAY_HASH_KEY=
SPGATEWAY_HASH_IV=

# 金流合作推廣商系統設定
SPGATEWAY_COMPANY_KEY=
SPGATEWAY_COMPANY_IV=
SPGATEWAY_PARTNER_ID=hourmaster
SPGATEWAY_MERCHANT_ID_PREFIX=HMS
SPGATEWAY_MERCHANT_ID=MS3133867

# 發票系統設定
SPGATEWAY_RECEIPT_KEY=gJpgVlZVvmBhnMR81f1gOT1jxVTFYAMe
SPGATEWAY_RECEIPT_IV=jbufwta2QLkdyblA
SPGATEWAY_RECEIPT_MERCHANT_ID=3836438
```

## 使用

### MPG 串接

#### 使用範例

1. 產生智付通訂單資料
```
$mpg = new MPG();

$order = $mpg->generateOrder([
    'MerchantOrderNo' => '20171226',
    'Amt'             => 100,
    'ItemDesc'        => '測試商品',
    'Email'           => 'leo@hourmasters.com'
]);
```

2. 前台送出表單到智付通
```
return $mpg->sendOrder($order);
```

#### 參數表

| 欄位            | 必填 |     型態     |       可選值      |   預設  | 備註 |
|-----------------|:----:|:------------:|:-----------------:|:-------:|------|
| MerchantOrderNo |   ✔  |    String    |                   |         |   商店自訂編號   |
| Amt             |   ✔  |    Number    |                   |         |      |
| ItemDesc        |   ✔  |    String    |                   |         |   商品描述   |
| Email           |   ✔  |     Email    |                   |         |      |
| RespondType     |      |    String    | `JSON` / `String` |  `JSON` |      |
| LangType        |      |    String    |   `zh-tw` / `en`  | `zh-tw` |      |
| TradeLimit      |      |    Number    |      60 ~ 900     |   180   |      |
| ExpireDate      |      | String (Ymd) |                   |         |      |
| ExpireTime      |      | String (His) |                   |         |      |
| ReturnURL       |      |      Url     |                   |         |      |
| NotifyURL       |      |      Url     |                   |         |      |
| CustomerURL     |      |      Url     |                   |         |      |
| ClientBackURL   |      |      Url     |                   |         |      |
| EmailModify     |      |    Number    |     `0` / `1`     |    1    |      |
| LoginType       |      |    Number    |     `0` / `1`     |    0    |      |
| OrderComment    |      |    String    |                   |         |      |
| TokenTerm       |      |    String    |                   |         |   信用卡快速結帳   |
| CREDIT          |      |    Number    |     `0` / `1`     |         |   信用卡一次付清   |
| CreditRed       |      |    Number    |     `0` / `1`     |         |   信用卡紅利   |
| InstFlag        |      |    Number    |     `0` / `1`     |         |   信用卡分期付款   |
| UNIONPAY        |      |    Number    |     `0` / `1`     |         |   銀聯卡   |
| WEBATM          |      |    Number    |     `0` / `1`     |         |   WebATM   |
| VACC            |      |    Number    |     `0` / `1`     |         |   ATM轉帳   |
| CVS             |      |    Number    |     `0` / `1`     |         |   超商代碼繳費   |
| BARCODE         |      |    Number    |     `0` / `1`     |         |   條碼繳費，訂單金額需介於20～20000   |

#### 備註
* 支付方式至少必須選擇一種，若皆無選擇預設為啟用信用卡

## License

This project is licensed under the MIT License

