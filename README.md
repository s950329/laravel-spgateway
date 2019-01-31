# Laravel Spgateway
[![Latest Stable Version](https://poser.pugx.org/leochien/laravel-spgateway/v/stable)](https://packagist.org/packages/leochien/laravel-spgateway)
    [![Total Downloads](https://poser.pugx.org/leochien/laravel-spgateway/downloads)](https://packagist.org/packages/leochien/laravel-spgateway)
    [![Latest Unstable Version](https://poser.pugx.org/leochien/laravel-spgateway/v/unstable)](https://packagist.org/packages/leochien/laravel-spgateway)
    [![License](https://poser.pugx.org/leochien/laravel-spgateway/license)](https://packagist.org/packages/leochien/laravel-spgateway)

Laravel Spgateway是一個開源的 [智付通](https://www.spgateway.com/) 非官方套件

## 目錄
1. [環境要求](#要求)
2. [安裝](#安裝)
3. [配置](#配置)
4. [使用](#使用)
    1. [多功能收款MPG](#多功能收款MPG)
    2. [電子發票](#電子發票串接)
    3. [退款/取消授權](#退款/取消授權)
    4. [平台費用扣款指示](#平台費用扣款指示)
5. [版本紀錄](#版本紀錄)
5. [License](#License)

## 要求

1. PHP >= 7
2. Laravel >= 5
3. Composer

## 安裝

```
$ composer require leochien/laravel-spgateway
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
    'MPG' => LeoChien\Spgateway\Facades\MPG::class,
    'Receipt' => LeoChien\Spgateway\Facades\Receipt::class,
    'Refund' => LeoChien\Spgateway\Facades\Refund::class,
    'Transfer' => LeoChien\Spgateway\Facades\Transfer::class,
],
```

2. 創建設定檔：

```shell
php artisan vendor:publish --provider="LeoChien\Spgateway\SpgatewayServiceProvider"
```

3. 修改 `config/spgateway.php` 中對應的參數。

4. 在 `.env` 中加入下列設定

```
# 金流系統設定
SPGATEWAY_HASH_KEY=
SPGATEWAY_HASH_IV=

# 金流合作推廣商系統設定
SPGATEWAY_COMPANY_KEY=
SPGATEWAY_COMPANY_IV=
SPGATEWAY_PARTNER_ID=
SPGATEWAY_MERCHANT_ID_PREFIX=
SPGATEWAY_MERCHANT_ID=

# 發票系統設定
SPGATEWAY_RECEIPT_KEY=
SPGATEWAY_RECEIPT_IV=
SPGATEWAY_RECEIPT_MERCHANT_ID=
```

## 使用

### 多功能收款MPG

#### 快速上手
建立訂單
```
// 產生智付通訂單資料
$order = MPG::generate(
    100,
    'leo@hourmasters.com',
    '測試商品'
);

// $order的 getPostData() 及 getPostDataEncrypted() 會回傳包含即將傳送到智付通的表單資料，可在此時紀錄log

// 前台送出表單到智付通
return $order->send();
```

解析智付通回傳訂單資料
```
$tradeInfo = MPG::parse(request()->TradeInfo);
```

查詢訂單
```
$order = MPG::search(
    '20180110151950mTHuUY'
    100
);
```

#### 可用方法

> ### generate ($amount, $email, $itemDesc \[, $params \])

產生智付通建立訂單必要欄位

##### 參數

1. `amount (Integer)`: 訂單金額
2. `email (String)`: 訂購人Email
3. `itemDesc (String)`: 商品描述
5. `[ params (Array) ]`: 其他可選參數，詳見下方參數表

##### 回傳

1. `(Class)`: MPG Class實體，其中getPostData() 及 getPostDataEncrypted()會回傳即將送到智付通的表單資料

##### 使用範例
```
$order = MPG::generate(
    100,
    'leo@hourmasters.com',
    '測試商品'
);
```

##### 參數表

| 欄位            |     型態     |       可選值      |   預設  | 備註                                            |
|-----------------|:------------:|:-----------------:|:-------:|----------------------------------------------|
| MerchantOrderNo |  Varchar(20) |                   |         | 商店自訂編號，若無填寫則由套件產生               |
| LangType        |    String    |   `zh-tw` / `en`  | `zh-tw` |                                              |
| TradeLimit      |    Number    |      60 ~ 900     |   180   |                                              |
| ExpireDate      | String (Ymd) |                   |         |      範例：20171231                           |
| ReturnURL       |      Url     |                   |         |                                              |
| NotifyURL       |      Url     |                   |         |                                              |
| CustomerURL     |      Url     |                   |         |                                              |
| ClientBackURL   |      Url     |                   |         |                                              |
| EmailModify     |    Number    |     `0` / `1`     |    1    |                                              |
| LoginType       |    Number    |     `0` / `1`     |    0    |                                              |
| OrderComment    |    String    |                   |         |                                              |
| TokenTerm       |    String    |                   |         | 信用卡快速結帳                                |
| CREDIT          |    Number    |     `0` / `1`     |         | 信用卡一次付清                                |
| CreditRed       |    Number    |     `0` / `1`     |         | 信用卡紅利                                    |
| InstFlag        |    Number    |     `0` / `1`     |         | 信用卡分期付款                                 |
| UNIONPAY        |    Number    |     `0` / `1`     |         | 銀聯卡                                        |
| WEBATM          |    Number    |     `0` / `1`     |         | WebATM                                       |
| VACC            |    Number    |     `0` / `1`     |         | ATM轉帳                                      |
| CVS             |    Number    |     `0` / `1`     |         | 超商代碼繳費                                  |
| BARCODE         |    Number    |     `0` / `1`     |         | 條碼繳費，訂單金額需介於20～20000               |
| CREDITAGREEMENT |    Number    |     `0` / `1`     |         | 約定信用卡授權                                |
| TokenLife       |    String    |                   |         | 約定信用卡付款之有效日期，範例：1912（2019-12）  |

##### 備註
* 支付方式若無選擇，默認開啟智付通後台設定方式
* 若無傳送訂單編號預設會建立年月日時分秒+6位隨機字串的訂單編號，e.g. 20180110151950mTHuUY

> ### send ()

前台送出智付通訂單建立表單

##### 使用範例
```
$order->send();
```

> ### parse ($tradeInfo)

解析智付通交易結果回傳參數，也適用於取號完成

##### 參數

1. `tradeInfo (String)`: 智付通回傳，經AES加密之交易資料

##### 回傳
詳見[智付通文件](https://www.spgateway.com/WebSiteData/document/5.pdf)第六節：交易支付系統回傳參數說明 / 第七節：取號完成系統回傳參數說明
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$tradeInfo = MPG::parse(request()->TradeInfo);
```

> ### search ($orderNo, $amount)

產生智付通查詢訂單必要欄位

##### 參數

1. `orderNo (String)`: 訂單編號
2. `amount (Integer)`: 訂單金額

##### 回傳
詳見[智付通文件](https://www.spgateway.com/WebSiteData/document/4.pdf)第四章：交易查詢系統回應訊息
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
// 查詢智付通訂單
$order = MPG::search(
    '20180110151950mTHuUY'
    100
);
```

### 電子發票

#### 快速上手
開立發票
```
// 產生智付通開立發票資料
$receipt = Receipt::generate([
    'BuyerName'       => 'Leo',
    'TotalAmt'        => 10,
    'ItemName'        => [1],
    'ItemCount'       => [1],
    'ItemUnit'        => ['式'],
    'ItemPrice'       => [10],
]);

// $receipt的 getPostData() 及 getPostDataEncrypted 會回傳即將傳送到智付通的表單資料，可在此時紀錄log

// 送出開立發票申請，取得發票開立回傳結果
$res = $receipt->send();
```

觸發開立發票
```
// 產生智付通出發開立發票資料
$receipt = Receipt::generateTrigger('17122817285242624', '20171121WJNBX5NNBP', 100);

// $receipt的 getTriggerPostData() 及 getTriggerPostDataEncrypted() 會回傳即將傳送到智付通的表單資料，可在此時紀錄log

// 送出觸發開立發票申請，取得發票觸發開立回傳結果
$res = $receipt->send();
```

作廢發票
```
// 產生智付通作廢發票資料
$receipt = Receipt::generateInvalid('YF83646422', '作廢原因');

// $receipt的 getInvalidPostData() 及 getInvalidPostDataEncrypted() 會回傳即將傳送到智付通的表單資料，可在此時紀錄log

// 送出作廢發票申請，取得作廢發票回傳結果
$res = $receipt->sendInvalid();
```

查詢發票
```
// 查詢發票資料
$receipt = Receipt::search('20171121WJNBX5NNBP', 100);
```
#### 可用方法

> ### generate ($params)

產生智付通開立電子發票必要欄位

##### 參數

1. `params (Array)`: 詳見下方可選參數

##### 回傳

1. `(Class)`: Class實體，其中getPostData() 及 getPostDataEncrypted()包含即將送到智付通的表單資料

##### 使用範例
```
$receipt = Receipt::generate([
    'BuyerName'       => 'Leo',
    'TotalAmt'        => 10,
    'ItemName'        => [1],
    'ItemCount'       => [1],
    'ItemUnit'        => ['式'],
    'ItemPrice'       => [10],
]);
```

##### 可選參數

| 欄位             | 必填  |      型態      |          可選值        |  預設 | 備註                                |
|------------------|:----:|:--------------:|:---------------------:|:-----:|------------------------------------|
| TransNum         |      |     String     |                       |       | 智付寶平台交易序號                  |
| MerchantOrderNo  |      |     String     |                       |       | 商店自訂編號，若無填寫則由套件產生    |
| Status           |      |     Number     |    `0` / `1` / `3`    |   1   | 開立發票方式                        |
| CreateStatusTime |      | String (Y-m-d) |                       |       | 範例：2017-12-31                   |
| Category         |      |     String     |     `B2B` / `B2C`     | `B2C` |                                    |
| BuyerName        |   ✔  |     String     |                       |       |                                    |
| BuyerUBN         |      |     String     |                       |       |                                    |
| BuyerAddress     |      |     String     |                       |       |                                    |
| BuyerEmail       |      |      Email     |                       |       |                                    |
| CarrierType      |      |     String     |       `0` / `1`       |   1   |                                    |
| CarrierNum       |      |     String     |       `0` / `1`       |   0   |                                    |
| LoveCode         |      |     Number     |                       |       |                                    |
| PrintFlag        |      |     String     |       `Y` / `N`       |  `Y`  |                                    |
| TaxType          |      |     String     | `1` / `2` / `3` / `9` |  `1`  |                                    |
| TaxRate          |      |     Number     |                       |  `5`  |                                    |
| CustomsClearance |      |     Number     |       `1` / `2`       |       |                                    |
| Amt              |      |     Number     |                       |       | 若無填寫則由套件計算                 |
| AmtSales         |      |     Number     |                       |       |                                    |
| AmtZero          |      |     Number     |                       |       |                                    |
| AmtFree          |      |     Number     |                       |       |                                    |
| TaxAmt           |      |     Number     |                       |       | 若無填寫則由套件計算                 |
| TotalAmt         |   ✔  |     Number     |                       |       |                                    |
| ItemName         |   ✔  |      Array     |                       |       |                                    |
| ItemCount        |   ✔  |      Array     |                       |       |                                    |
| ItemUnit         |   ✔  |      Array     |                       |       |                                    |
| ItemPrice        |   ✔  |      Array     |                       |       |                                    |
| ItemTaxType      |      |      Array     |                       |       |                                    |
| ItemAmt          |      |      Array     |                       |       | 若無填寫則由套件計算                 |
| Comment          |      |     String     |                       |       |                                    |

##### 備註
* 本套件僅提供快速串接方式，詳細稅額計算方式請務必與公司財會人員進行確認

> ### send()

傳送開立發票請求到智付通

##### 回傳
詳見[智付通文件](https://inv.pay2go.com/dw_files/info_api/pay2go_gateway_electronic_invoice_api_V1_1_7.pdf)第四節之二：開立發票系統回應訊息
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $receipt->send();
```

> ### generateTrigger ($invoiceTransNo, $orderNo, $amount)

產生智付通觸發開立電子發票必要資訊

##### 參數

1. `invoiceTransNo (String)`: 智付寶開立序號
2. `orderNo (String)`: 商店自訂編號
3. `amount (Integer)`: 發票金額

##### 回傳

1. `(Class)`: Class實體，其中getTriggerPostData() 及 getTriggerPostDataEncrypted()會回傳即將送到智付通的表單資料

##### 使用範例
```
$receipt = Receipt::generateTrigger('17122817285242624', '20171121WJNBX5NNBP', 100);
```

> ### sendTrigger()

送出觸發開立電子發票請求到智付通

##### 回傳
詳見[智付通文件](https://inv.pay2go.com/dw_files/info_api/pay2go_gateway_electronic_invoice_api_V1_1_7.pdf)第四節之四：觸發開立發票系統回應訊息
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $receipt->sendTrigger();
```

> ### generateInvalid ($receiptNumber, $invalidReason)

產生智付通觸發開立電子發票必要資訊

##### 參數

1. `receiptNumber (String)`: 發票號碼
2. `receiptNumber (String)`: 作廢原因

##### 回傳

1. `(Class)`: Class實體，其中getInvalidPostData() 及 getInvalidPostDataEncrypted()會回傳即將送到智付通的表單資料

##### 使用範例
```
$receipt = Receipt::generateInvalid('17122817285242624', '作廢原因');
```

> ### sendInvalid()

送出觸發開立電子發票請求到智付通

##### 回傳
詳見[智付通文件](https://inv.pay2go.com/dw_files/info_api/pay2go_gateway_electronic_invoice_api_V1_1_7.pdf)第五節之二：作廢發票系統回應訊息
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $receipt->sendInvalid();
```

> ### search($orderNo, $amount)

查詢發票

##### 參數

1. `orderNo (String)`: 商店自訂編號
2. `amount (Integer)`: 發票金額

##### 回傳
詳見[智付通文件](https://inv.pay2go.com/dw_files/info_api/pay2go_gateway_electronic_invoice_api_V1_1_7.pdf)第七節之二：查詢發票系統回應訊息
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $receipt->search('20171121WJNBX5NNBP', 100);
```

### 退款/取消授權
因智付通信用卡退費有尚未請款需串接取消授權API，已請款需串接退款API之規則，本功能旨在整合此一過程，降低開發人員負擔
另外也提供非即時交易退款功能

#### 快速上手

```
// 產生智付通退費 / 取消授權必要資訊
$refund = Refund::generate('20171121WJNBX5NNBP', 100);

// $refund的 postType為cacnel時，訂單準備取消授權；為refund時，訂單準備退款
// $refund的 getPostData() 及 getPostDataEncrypted() 會回傳即將傳送到智付通的表單資料，可在此時紀錄log

// 送出退款/取消授權申請，取得回傳結果
$res = $refund->send();
```

#### 可用方法

> ### generate ($orderNo, $amount\[, $notifyUrl = null, $delayed = false, $params = []\])

產生智付通退費 / 取消授權必要欄位

##### 參數

1. `orderNo (String)`: 商店自訂編號
2. `amount (String)`: 訂單金額，若不想全額退款請於可選參數中傳送Amt欄位
3. `[ notifyUrl (String) ]`: 接受取消授權結果位址，於取消授權或非即時交易時才需填寫
4. `[ delayed (Boolean) ]`: 是否為非即時交易
5. `[ params (Array) ]`: 詳見下方參數表

##### 回傳

1. `(Class)`: Class實體，其中getPostData() 及 getPostDataEncrypted()包含即將送到智付通的表單資料；getPostType()為cacnel時，訂單準備取消授權，為refund時，訂單準備退款

##### 使用範例
```
$refund = Refund::generate('20171121WJNBX5NNBP', 100);
```

##### 可選參數
1. 即時交易

| 欄位      | 必填 |  型態  | 備註                                           |
|-----------|:----:|:------:|------------------------------------------------|
| RefundAmt |      | Number | 請退款金額，若發動退費後不退全額則需傳送此參數 |

2. 非即時交易

| 欄位        | 必填 |  型態  | 備註                              |
|-------------|:----:|:------:|-----------------------------------|
| AccNo       |   ✔  | String | 退款金額轉入之帳號                |
| BankNo      |   ✔  | String | 金融機構總行代號                  |
| SubBankCode |   ✔  | String | 金融機構分行代號                  |
| AccName     |   ✔  | String | 帳戶使用名稱                      |
| RefundAmt   |   ✔  | Number | 退款金額，必須小於等於訂單金額              |
| Id          |   +  | String | 買方身分證字號，Id及UBN需擇一填寫 |
| UBN         |   +  | String | 買方統一編號，Id及UBN需擇一填寫   |

> ### send()

傳送退費 / 取消授權請求到智付通

##### 回傳
取消授權：詳見[智付通文件](https://www.spgateway.com/WebSiteData/document/gateway_creditcard_deauthorize_api_V1_0_0.pdf)第五節：取消授權完成後系統回應訊息

退款：詳見[智付通文件](https://www.spgateway.com/WebSiteData/document/2.pdf)第五節：系統回應訊息
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $refund->send();
```

### 已約定信用卡付款

#### 快速上手
```
// 產生付款必要資訊
$charge = Charge::generate(
    100,
    'email@email.com',
    'itemDesc',
    'xxxxxxxxxxxxxxxxxx',
    'xxx',
    [
        'MerchantOrderNo' => '20171121WJNBX5NNBP'
    ]
);

// $transfer的 getPostData() 及 getPostDataEncrypted() 會回傳即將傳送到智付通的表單資料，可在此時紀錄log

// 送出扣款
$res = $charge->send();
```
#### 可用方法

> ### generate ($amount, $email, $itemDesc, $tokenValue, $tokenTerm, $params)

產生已約定信用卡付款必要欄位

##### 參數

1. `$amount (String)`: 金額
2. `$email (String)`: 購買人 Email
3. `$itemDesc (Integer)`: 商品描述
4. `$tokenValue (Integer)`: 約定信用卡授權碼
5. `$tokenTerm ()`: 約定信用卡付款之付款人綁定資料
6. `$params ()`: 可選選項

##### 可選參數

| 欄位               | 必填 |  型態  | 備註                                       |
|-------------------|:----:|:------:|-------------------------------------------|
| MerchantOrderNo   |     | String | 訂單編號                                    |
| TokenSwitch       |     | String | 當此參數為”on”時，才會啟用約定信用卡付款授權功能 |

##### 回傳

1. `(Class)`: Class實體，其中 getPostData() 及 getPostDataEncrypted() 包含即將送到智付通的表單資料

##### 使用範例
```
$charge = Charge::generate(
    100,
    'email@email.com',
    'itemDesc',
    'xxxxxxxxxxxxxxxxxx',
    'xxx',
    [
        'MerchantOrderNo' => '20171121WJNBX5NNBP'
    ]
);
```

> ### send()

傳送已約定信用卡付款請求到智付通

##### 回傳
本文件未公開，請向合作之智付通業務人員索取
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $transfer->send();
```

### 平台費用扣款指示

#### 快速上手
```
// 產生平台費用扣款指示必要資訊
$transfer = Transfer::generate('20171121WJNBX5NNBP', 100, 0, 0);

// $transfer的 getPostData() 及 getPostDataEncrypted() 會回傳即將傳送到智付通的表單資料，可在此時紀錄log

// 送出扣款指示申請，取得扣款指示回傳結果
$res = $transfer->send();
```
#### 可用方法

> ### generate ($merchantID, $amount, $feeType, $balanceType)

產生智付通扣款指示必要欄位

##### 參數

1. `orderNo (String)`: 商店自訂編號
2. `amount (String)`: 金額
3. `feeType (Integer)`: 費用類別
4. `balanceType (Integer)`: 交易正負值

##### 回傳

1. `(Class)`: Class實體，其中getPostData() 及 getPostDataEncrypted()包含即將送到智付通的表單資料

##### 使用範例
```
$transfer = Transfer::generate('20171121WJNBX5NNBP', 100);
```

> ### send()

傳送扣款指示請求到智付通

##### 回傳
本文件未公開，請向合作之智付通業務人員索取
```
{
    "Status": "..."
    "Message": "..."
    "Result": {...}
}
```

##### 使用範例
```
$res = $transfer->send();
```

## 版本紀錄
### 1.1.0
1. 非信用卡退款
2. MPG交易串接增加支付寶欄位（測試功能）

### 1.0.0
1. MPG交易串接 / 查詢
2. 電子發票
3. 退款 / 取消授權
4. 平台扣款指示

## License

Laravel Spgateway is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT)

