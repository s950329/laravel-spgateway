<?php

namespace LeoChien\Spgateway;

use Exception;
use LeoChien\Spgateway\Libraries\Helpers;

class MPG
{
    /**
     * api url list.
     *
     * @var array
     */
    private $apiUrl;

    /**
     * Helper.
     *
     * @var LeoChien\Spgateway\Libraries\Helpers
     */
    private $helpers;

    /**
     * post data.
     *
     * @var array
     */
    private $postData;

    /**
     * Encrypted post data.
     *
     * @var array
     */
    private $postDataEncrypted;

    public function __construct()
    {
        $this->apiUrl = [];
        if ('production' === config('app.env')) {
            $this->apiUrl['MPG_API']
                = 'https://core.newebpay.com/MPG/mpg_gateway';
            $this->apiUrl['QUERY_TRADE_INFO_API']
                = 'https://core.newebpay.com/API/QueryTradeInfo';
        } else {
            $this->apiUrl['MPG_API']
                = 'https://ccore.newebpay.com/MPG/mpg_gateway';
            $this->apiUrl['QUERY_TRADE_INFO_API']
                = 'https://ccore.newebpay.com/API/QueryTradeInfo';
        }

        $this->helpers = new Helpers();
    }

    /**
     * 產生智付通訂單建立必要資訊.
     *
     * @param       $amount   integer 訂單金額
     * @param       $email    string 訂購人email
     * @param       $itemDesc string 商品描述
     *
     * @return MPG|Exception
     */
    public function generate(
        $amount,
        $email,
        $itemDesc,
        array $params = []
    ) {
        try {
            $validator = $this->formValidator(
                $amount,
                $itemDesc,
                $email,
                $params
            );

            if (true !== $validator) {
                throw new Exception($validator['field'] . $validator['message']);
            }

            $postData = [
                'Amt' => $amount,
                'ItemDesc' => $itemDesc,
                'Email' => $email,

                'MerchantID' => config('spgateway.mpg.MerchantID'),
                'RespondType' => config('spgateway.mpg.RespondType'),
                'TimeStamp' => time(),
                'Version' => config('spgateway.mpg.Version'),

                //options
                'LangType' => $params['LangType'] ?? config('spgateway.mpg.LangType'),
                'MerchantOrderNo' => $params['MerchantOrderNo'] ?? $this->helpers->generateOrderNo(),
                'TradeLimit' => $params['TradeLimit'] ?? config('spgateway.mpg.TradeLimit'),
                'ExpireDate' => $params['ExpireDate'] ?? config('spgateway.mpg.ExpireDate'),
                'ReturnURL' => $params['ReturnURL'] ?? config('spgateway.mpg.ReturnURL'),
                'NotifyURL' => $params['NotifyURL'] ?? config('spgateway.mpg.NotifyURL'),
                'CustomerURL' => $params['CustomerURL'] ?? config('spgateway.mpg.CustomerURL'),
                'ClientBackURL' => $params['ClientBackURL'] ?? config('spgateway.mpg.ClientBackURL'),
                'EmailModify' => $params['EmailModify'] ?? config('spgateway.mpg.EmailModify'),
                'LoginType' => $params['LoginType'] ?? config('spgateway.mpg.LoginType'),

                'OrderComment' => $params['OrderComment'] ?? null,

                //付款方式相關設定
                'CREDIT' => $params['CREDIT'] ?? config('spgateway.mpg.CREDIT'),
                'ANDROIDPAY' => $params['ANDROIDPAY'] ?? config('spgateway.mpg.ANDROIDPAY'),
                'SAMSUNGPAY' => $params['SAMSUNGPAY'] ?? config('spgateway.mpg.SAMSUNGPAY'),
                'InstFlag' => $params['InstFlag'] ?? config('spgateway.mpg.InstFlag'),
                'CreditRed' => $params['CreditRed'] ?? config('spgateway.mpg.CreditRed'),
                'UNIONPAY' => $params['UNIONPAY'] ?? config('spgateway.mpg.UNIONPAY'),
                'WEBATM' => $params['WEBATM'] ?? config('spgateway.mpg.WEBATM'),
                'VACC' => $params['VACC'] ?? config('spgateway.mpg.VACC'),
                'CVS' => $params['CVS'] ?? config('spgateway.mpg.CVS'),
                'BARCODE' => $params['BARCODE'] ?? config('spgateway.mpg.BARCODE'),
                'P2G' => $params['P2G'] ?? config('spgateway.mpg.P2G'),

                //物流啟用相關
                'CVSCOM' => $params['CVSCOM'] ?? config('spgateway.mpg.CVSCOM'),

                //信用卡快速結帳
                'TokenTerm' => $params['TokenTerm'] ?? null,                //付款人綁定資料 Varchar(20)  1.可對應付款人之資料，用於綁定付款人與 信用卡卡號時使用，例:會員編號、 Email。 2.限英、數字，「.」、「_」、「@」、「-」格 式。
                'TokenTermDemand' => $params['TokenTermDemand'] ?? null,    //指定付款人信用卡快速結帳必填欄位 Int(1) 1 = 必填信用卡到期日與背面末三碼 2 = 必填信用卡到期日 3 = 必填背面末三碼

                'CREDITAE' => $params['BARCODE'] ?? null,
                'ALIPAY' => $params['ALIPAY'] ?? null,
                'TENPAY' => $params['TENPAY'] ?? null,

                // 信用卡授權專用欄位
                'CREDITAGREEMENT' => $params['CREDITAGREEMENT'] ?? null,
                'TokenLife' => $params['TokenLife'] ?? null,

                // 以下為支付寶 / 財付通專用欄位
                'Receiver' => $params['Receiver'] ?? null,
                'Tel1' => $params['Tel1'] ?? null,
                'Tel2' => $params['Tel2'] ?? null,
            ];

            if (isset($params['Commodities'])) {
                $postData['Count'] = count($params['Commodities']);
                $postData = array_merge($postData, $this->parseCommodities($params['Commodities']));
            }

            $postData = array_filter($postData, function ($value) {
                return null !== $value && false !== $value && '' !== $value;
            });

            $this->postData = $postData;

            return $this->encrypt();
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * 加密訂單資料.
     */
    public function encrypt(): MPG
    {
        $tradeInfo = $this->createMpgAesEncrypt($this->postData);
        $tradeSha = $this->createMpgSHA256Encrypt($tradeInfo);

        $this->postDataEncrypted = [
            'MerchantID' => config('spgateway.mpg.MerchantID'),
            'TradeInfo' => $tradeInfo,
            'TradeSha' => $tradeSha,
            'Version' => config('spgateway.mpg.Version'),
        ];

        return $this;
    }

    /**
     * 前台送出訂單建立表單到智付通.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function send()
    {
        return view(
            'spgateway::send-order',
            [
                'apiUrl' => $this->apiUrl['MPG_API'],
                'order' => $this->postDataEncrypted,
            ]
        );
    }

    /**
     * 解析智付通交易結果回傳參數.
     *
     * @param $tradeInfo String 智付通回傳參數中的TradeInfo
     *
     * @return mixed
     */
    public function parse($tradeInfo)
    {
        $result = $this->createAES256decrypt($tradeInfo);

        return json_decode($result);
    }

    /**
     * 搜尋訂單.
     *
     * @param string $orderNo
     * @param int $amount
     *
     * @return mixed
     */
    public function search(
        $orderNo,
        $amount
    ) {
        $postData = [
            'MerchantID' => config('spgateway.mpg.MerchantID'),
            'Version' => '1.1',
            'RespondType' => 'JSON',
            'TimeStamp' => time(),
            'MerchantOrderNo' => $orderNo,
            'Amt' => $amount,
        ];

        $postData['CheckValue'] = $this->generateTradeInfoCheckValue(
            $orderNo,
            $amount
        );

        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['QUERY_TRADE_INFO_API'],
            $postData
        );

        $result = json_decode($res);

        return $result;
    }

    /**
     * Get origin post data array.
     */
    public function getPostData(): array
    {
        return $this->postData;
    }

    /**
     * Get encrypted post data.
     */
    public function getPostDataEncrypted(): array
    {
        return $this->postDataEncrypted;
    }

    /**
     * 驗證表單.
     *
     * @param $amount
     * @param $itemDesc
     * @param $email
     * @param $params
     *
     * @return array|bool
     */
    private function formValidator(
        $amount,
        $itemDesc,
        $email,
        $params
    ) {
        if (isset($params['LangType'])) {
            if (!in_array($params['LangType'], ['en', 'zh-tw'])) {
                return $this->errorMessage(
                    'LangType',
                    '英文版參數為 en，繁體中文版參數為 zh-tw'
                );
            }
        }

        if (!is_numeric($amount)) {
            return $this->errorMessage('Amt', '必須為大於0的整數');
        }

        if (mb_strlen($itemDesc) > 50) {
            return $this->errorMessage('ItemDesc', '必須小於50字');
        }

        if (isset($params['TradeLimit'])) {
            if ($params['TradeLimit'] < 60 || $params['TradeLimit'] > 900) {
                return $this->errorMessage('TradeLimit', '秒數下限為60秒，上限為900秒');
            }
        }

        if (isset($params['ExpireDate'])) {
            if (date_parse_from_format(
                'Ymd',
                $params['ExpireDate']
            )['error_count']
                > 0
            ) {
                return $this->errorMessage(
                    'ExpireDate',
                    '格式需為Ymd，如:20170101'
                );
            }

            if (strtotime($params['ExpireDate']) < strtotime('today')
                || strtotime('-180 days', strtotime($params['ExpireDate'])) > strtotime('today')
            ) {
                return $this->errorMessage('ExpireDate', '可接受最大值為 180 天');
            }
        }

        if (isset($params['ReturnURL'])) {
            if (!filter_var($params['ReturnURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('ReturnURL', '必須為合法的Url');
            }
        }

        if (isset($params['NotifyURL'])) {
            if (!filter_var($params['NotifyURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('NotifyURL', '必須為合法的Url');
            }
        }

        if (isset($params['CustomerURL'])) {
            if (!filter_var($params['CustomerURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('CustomerURL', '必須為合法的Url');
            }
        }

        if (isset($params['ClientBackURL'])) {
            if (!filter_var($params['ClientBackURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('ClientBackURL', '必須為合法的Url');
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorMessage('Email', '必須為合法的Email');
        }

        if (isset($params['EmailModify'])) {
            if (!in_array($params['EmailModify'], [1, 0])) {
                return $this->errorMessage('EmailModify', '必須為0或1');
            }
        }

        if (isset($params['LoginType'])) {
            if (!in_array($params['LoginType'], [1, 0])) {
                return $this->errorMessage('LoginType', '必須為0或1');
            }
        }

        if (isset($params['OrderComment'])) {
            if (strlen($params['OrderComment']) > 300) {
                return $this->errorMessage('OrderComment', '必須小於300字');
            }
        }

        if (isset($params['CREDIT'])) {
            if (isset($params['CREDIT'])) {
                if (!in_array($params['CREDIT'], [1, 0])) {
                    return $this->errorMessage('CREDIT', '必須為0或1');
                }
            }
        }

        if (isset($params['CreditRed'])) {
            if (isset($params['CreditRed'])) {
                if (!in_array($params['CreditRed'], [1, 0])) {
                    return $this->errorMessage('CreditRed', '必須為0或1');
                }
            }
        }

        if (isset($params['InstFlag'])) {
            $instFlag = explode(',', $params['InstFlag']);
            array_walk(
                $instFlag,
                function ($val, $key) {
                    if (!in_array($val, [1, 3, 6, 12, 18, 24])) {
                        return $this->errorMessage(
                            'InstFlag',
                            '期數必須為3、6、12、18、24'
                        );
                    }
                }
            );
        }

        if (isset($params['UNIONPAY'])) {
            if (!in_array($params['UNIONPAY'], [1, 0])) {
                return $this->errorMessage('UNIONPAY', '必須為0或1');
            }
        }

        if (isset($params['WEBATM'])) {
            if (!in_array($params['WEBATM'], [1, 0])) {
                return $this->errorMessage('WEBATM', '必須為0或1');
            }
        }

        if (isset($params['EmailModify'])) {
            if (!in_array($params['EmailModify'], [1, 0])) {
                return $this->errorMessage('EmailModify', '必須為0或1');
            }
        }

        if (isset($params['VACC'])) {
            if (!in_array($params['VACC'], [1, 0])) {
                return $this->errorMessage('VACC', '必須為0或1');
            }
        }

        if (isset($params['CVS'])) {
            if (!in_array($params['CVS'], [1, 0])) {
                return $this->errorMessage('CVS', '必須為0或1');
            }
        }

        if (isset($params['BARCODE'])) {
            if (!in_array($params['BARCODE'], [1, 0])) {
                return $this->errorMessage('BARCODE', '必須為0或1');
            }
        }

        return true;
    }

    /**
     * 回傳錯誤訊息.
     *
     * @param $field
     * @param $message
     *
     * @return array
     */
    private function errorMessage($field, $message)
    {
        return [
            'field' => $field,
            'message' => $message,
        ];
    }

    private function createMpgAesEncrypt($parameter = '')
    {
        $return_str = '';
        if (!empty($parameter)) {
            $return_str = http_build_query($parameter);
        }

        $key = config('spgateway.mpg.HashKey');
        $iv = config('spgateway.mpg.HashIV');

        return trim(bin2hex(openssl_encrypt(
            $this->helpers->addPadding($return_str),
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        )));
    }

    private function createAES256decrypt($parameter = '')
    {
        $key = config('spgateway.mpg.HashKey');
        $iv = config('spgateway.mpg.HashIV');

        return $this->stripPadding(openssl_decrypt(
            hex2bin($parameter),
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        ));
    }

    /**
     * SHA256 加密.
     *
     * @param string $aes
     *
     * @return string
     */
    private function createMpgSHA256Encrypt($aes)
    {
        $hashKey = config('spgateway.mpg.HashKey');
        $hashIv = config('spgateway.mpg.HashIV');

        $queryString = "HashKey={$hashKey}&{$aes}&HashIV={$hashIv}";

        //sha256 編碼
        $data = hash('sha256', $queryString);

        //轉大寫
        $tradeSha = strtoupper($data);

        return $tradeSha;
    }

    private function stripPadding($string)
    {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . '}/', $string)) {
            $string = substr($string, 0, strlen($string) - $slast);

            return $string;
        }

        return false;
    }

    /**
     * 產生訂單查詢檢查碼
     *
     * @param $orderNo
     * @param $amount
     *
     * @return string
     */
    private function generateTradeInfoCheckValue($orderNo, $amount)
    {
        $data = [
            'IV' => config('spgateway.mpg.HashIV'),
            'Amt' => $amount,
            'MerchantID' => config('spgateway.mpg.MerchantID'),
            'MerchantOrderNo' => $orderNo,
            'Key' => config('spgateway.mpg.HashKey'),
        ];

        $check_code_str = http_build_query($data);
        $checkValue = strtoupper(hash('sha256', $check_code_str));

        return $checkValue;
    }

    /**
     * 解析境外支付（支付寶/財付通）專用欄位.
     *
     * @param array $commodities
     *
     * @return array
     */
    private function parseCommodities($commodities = [])
    {
        $newCommodities = [];

        foreach ($commodities as $index => $commodity) {
            $newCommodity = [];

            foreach ($commodity as $key => $value) {
                $newCommodity[$key . ($index + 1)] = $value;
            }

            $newCommodities = array_merge($newCommodities, $newCommodity);
        }

        return $newCommodities;
    }
}
