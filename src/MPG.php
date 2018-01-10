<?php

namespace LeoChien\Spgateway;

use Exception;
use LeoChien\Spgateway\Libraries\Helpers;

class MPG
{
    private $apiUrl;
    private $helpers;
    private $postData;

    /**
     * 產生智付通訂單建立必要資訊
     *
     * @param       $amount   integer 訂單金額
     * @param       $email    string 訂購人email
     * @param       $itemDesc string 商品描述
     * @param array $params
     *
     * @return $this|Exception
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

            if ($validator !== true) {
                throw new Exception($validator['field']
                    . $validator['message']);
            }

            $postData = [

                'Amt'             => $amount,
                'ItemDesc'        => $itemDesc,
                'Email'           => $email,
                'MerchantID'      => config('spgateway.mpg.MerchantID'),
                'RespondType'     => 'JSON',
                'TimeStamp'       => time(),
                'Version'         => '1.4',
                'MerchantOrderNo' => $params['MerchantOrderNo'] ??
                    $this->helpers->generateOrderNo(),
                'LangType'        => $params['LangType'] ?? 'zh-tw',
                'TradeLimit'      => $params['TradeLimit'] ?? 180,
                'ExpireDate'      => $params['ExpireDate'] ?? null,
                'ExpireTime'      => $params['ExpireTime'] ?? null,
                'ReturnURL'       => $params['ReturnURL'] ?? null,
                'NotifyURL'       => $params['NotifyURL'] ?? null,
                'CustomerURL'     => $params['CustomerURL'] ?? null,
                'ClientBackURL'   => $params['ClientBackURL'] ?? null,
                'EmailModify'     => $params['EmailModify'] ?? 1,
                'LoginType'       => $params['LoginType'] ?? 0,
                'OrderComment'    => $params['OrderComment'] ?? null,
                'TokenTerm'       => $params['TokenTerm'] ?? null,
                'CREDIT'          => $params['CREDIT'] ?? null,
                'CreditRed'       => $params['CreditRed'] ?? null,
                'InstFlag'        => $params['InstFlag'] ?? null,
                'UNIONPAY'        => $params['UNIONPAY'] ?? null,
                'WEBATM'          => $params['WEBATM'] ?? null,
                'VACC'            => $params['VACC'] ?? null,
                'CVS'             => $params['CVS'] ?? null,
                'BARCODE'         => $params['BARCODE'] ?? null,
                'CREDITAE'        => $params['BARCODE'] ?? null,
                'ALIPAY'          => $params['ALIPAY'] ?? null,
                'TENPAY'          => $params['TENPAY'] ?? null,
            ];

            $postData = array_filter($postData, function ($value) {
                return ($value !== null && $value !== false && $value !== '');
            });

            $this->postData = $postData;

            return $this->encrypt();
        } catch (Exception $e) {
            return $e;
        }
    }

    private $postDataEncrypted;

    public function __construct()
    {
        $this->apiUrl = [];
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

        $this->helpers = new Helpers();
    }

    public function encrypt()
    {
        $tradeInfo = $this->createMpgAesEncrypt($this->postData);
        $tradeSha = $this->createMpgSHA256Encrypt($tradeInfo);

        $this->postDataEncrypted = [
            'MerchantID' => env('SPGATEWAY_MERCHANT_ID'),
            'TradeInfo'  => $tradeInfo,
            'TradeSha'   => $tradeSha,
            'Version'    => '1.4',
        ];

        return $this;
    }


    /**
     * 前台送出訂單建立表單到智付通
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function send()
    {
        return view('spgateway::send-order',
            [
                'apiUrl' => $this->apiUrl['MPG_API'],
                'order'  => $this->postDataEncrypted
            ]);
    }

    /**
     * 驗證表單
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
                return $this->errorMessage('LangType',
                    '英文版參數為 en，繁體中文版參數為 zh-tw');
            }
        }

        if (!is_numeric($amount)) {
            return $this->errorMessage('Amt', '必須為大於0的整數');
        }

        if (strlen($itemDesc) > 50) {
            return $this->errorMessage('Amt', '必須小於50字');
        }

        if (isset($params['TradeLimit'])) {
            if ($params['TradeLimit'] < 60 || $params['TradeLimit'] > 900) {
                return $this->errorMessage('TradeLimit', '秒數下限為60秒，上限為900秒');
            }
        }

        if (isset($params['ExpireDate'])) {
            if (date_parse_from_format('Y-m-d',
                    $params['ExpireDate'])['error_count']
                > 0
            ) {
                return $this->errorMessage('ExpireDate',
                    '格式需為Y-m-d，如:2017-01-01');
            }

            if (strtotime($params['ExpireDate']) < time()
                || strtotime('-180 days', $params['ExpireDate']) > time()
            ) {
                return $this->errorMessage('ExpireDate', '可接受最大值為 180 天');
            }
        }

        if (isset($params['ExpireTime'])) {
            if (date_parse_from_format('H:i:s',
                    $params['ExpireTime'])['error_count']
                > 0
            ) {
                return $this->errorMessage('ExpireTime',
                    '格式需為Y-m-d，如:2017-01-01');
            }

            if (strtotime($params['ExpireTime']) < time()
                || strtotime('-1 hour', $params['ExpireTime']) > time()
            ) {
                return $this->errorMessage('ExpireTime', '可接受最大值為 180 天');
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
            array_walk(explode(',', $params['InstFlag']),
                function ($val, $key) {
                    if (!in_array($val, [1, 3, 6, 12, 18, 24])) {
                        return $this->errorMessage('InstFlag',
                            '期數必須為3、6、12、18、24');
                    }
                });
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
     * 回傳錯誤訊息
     *
     * @param $field
     * @param $message
     *
     * @return array
     */
    private function errorMessage($field, $message)
    {
        return [
            'field'   => $field,
            'message' => $message,
        ];
    }

    private function createMpgAesEncrypt($parameter = "")
    {
        $return_str = '';
        if (!empty($parameter)) {
            $return_str = http_build_query($parameter);
        }

        $key = config('spgateway.mpg.HashKey');
        $iv = config('spgateway.mpg.HashIV');

        return trim(bin2hex(openssl_encrypt($this->helpers->addPadding($return_str),
            'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv)));
    }

    private function createAES256decrypt($parameter = "")
    {
        $key = config('spgateway.mpg.HashKey');
        $iv = config('spgateway.mpg.HashIV');

        return $this->stripPadding(openssl_decrypt(hex2bin($parameter), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv));
    }

    private function createMpgSHA256Encrypt($aes)
    {
        $key = config('spgateway.mpg.HashKey');
        $iv = config('spgateway.mpg.HashIV');

        $data = "HashKey=$key&" . $aes . "&HashIV=$iv";

        return strtoupper(hash("sha256", $data));
    }

    private function stripPadding($string)
    {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }

    /**
     * 解析智付通交易結果回傳參數
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
     * 搜尋訂單
     *
     * @param string $orderNo
     * @param int    $amount
     *
     * @return mixed
     */
    public function search(
        $orderNo,
        $amount
    ) {
        $postData = [
            'MerchantID'      => config('spgateway.mpg.MerchantID'),
            'Version'         => '1.1',
            'RespondType'     => 'JSON',
            'TimeStamp'       => time(),
            'MerchantOrderNo' => $orderNo,
            'Amt'             => $amount,
        ];

        $postData['CheckValue'] = $this->generateTradeInfoCheckValue($orderNo,
            $amount);

        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['QUERY_TRADE_INFO_API'],
            $postData
        );

        $result = json_decode($res);

        return $result;
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
            'IV'              => config('spgateway.mpg.HashIV'),
            'Amt'             => $amount,
            'MerchantID'      => config('spgateway.mpg.MerchantID'),
            'MerchantOrderNo' => $orderNo,
            'Key'             => config('spgateway.mpg.HashKey'),
        ];

        $check_code_str = http_build_query($data);
        $checkValue = strtoupper(hash("sha256", $check_code_str));

        return $checkValue;
    }
}
