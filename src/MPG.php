<?php

namespace LeoChien\Spgateway;

use Exception;
use \GuzzleHttp\Client;

class MPG
{
    private $apiUrl;
    private $client;

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

        $this->client = new Client();
    }

    public function generateOrder(array $params)
    {
        try {
            $validator = $this->formValidator($params);

            if ($validator !== true) {
                throw new Exception($validator['field']
                    . $validator['message']);
            }

            $order = [
                'MerchantID'      => config('spgateway.mpg.MerchantID'),
                'RespondType'     => $params['RespondType'] ?? 'JSON',
                'TimeStamp'       => time(),
                'Version'         => config('spgateway.mpg.Version'),
                'LangType'        => $params['LangType'] ?? 'zh-tw',
                'MerchantOrderNo' => $params['MerchantOrderNo'],
                'Amt'             => $params['Amt'],
                'ItemDesc'        => $params['ItemDesc'],
                'TradeLimit'      => $params['TradeLimit'] ?? 180,
                'ExpireDate'      => $params['ExpireDate'] ?? null,
                'ExpireTime'      => $params['ExpireTime'] ?? null,
                'ReturnURL'       => $params['ReturnURL'] ?? null,
                'NotifyURL'       => $params['NotifyURL'] ?? null,
                'CustomerURL'     => $params['CustomerURL'] ?? null,
                'ClientBackURL'   => $params['ClientBackURL'] ?? null,
                'Email'           => $params['Email'],
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
            ];

            $order = array_filter($order, function ($value) {
                return ($value !== null && $value !== false && $value !== '');
            });

            $order['CheckValue'] = $this->generateCheckValue($order);

            return $order;

        } catch (Exception $e) {
            return $e;
        }
    }

        public function sendOrder($order)
    {
        return view('spgateway::send-order',
            ['apiUrl' => $this->apiUrl['MPG_API'], 'order' => $order]);
    }

    private function formValidator($params)
    {
        if (isset($params['RespondType'])) {
            if (!in_array($params['RespondType'], ['JSON', 'String'])) {
                return $this->errorMessage('RespondType', '必須為JSON或是String');
            }
        }

        if (isset($params['RespondType'])) {
            if (!in_array($params['LangType'], ['en', 'zh-tw'])) {
                return $this->errorMessage('LangType',
                    '英文版參數為 en，繁體中文版參數為 zh-tw');
            }
        }

        if (!is_numeric($params['Amt'])) {
            return $this->errorMessage('Amt', '必須為大於0的整數');
        }

        if (strlen($params['ItemDesc']) > 50) {
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

        if (!filter_var($params['Email'], FILTER_VALIDATE_EMAIL)) {
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
            array_walk(explode(',', $params['InstFlag']), function ($val, $key) {
                if (!in_array($val, [1, 3, 6, 12, 18, 24])) {
                    return $this->errorMessage('InstFlag', '期數必須為3、6、12、18、24');
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

    private function errorMessage($field, $message)
    {
        return [
            'field'   => $field,
            'message' => $message,
        ];
    }

    private function generateCheckValue($order)
    {
        $chkRes = [
            'Amt'             => $order['Amt'],
            'MerchantID'      => config('spgateway.mpg.MerchantID'),
            'MerchantOrderNo' => $order['MerchantOrderNo'],
            'TimeStamp'       => $order['TimeStamp'],
            'Version'         => config('spgateway.mpg.Version'),
        ];

        ksort($chkRes, SORT_REGULAR);
        $checkMaster = http_build_query($chkRes);
        $hashKey = config('spgateway.mpg.HashKey');
        $hashIV = config('spgateway.mpg.HashIV');
        $checkValue = strtoupper(hash('sha256',
            "HashKey={$hashKey}&{$checkMaster}&HashIV={$hashIV}"));

        return $checkValue;
    }

    /**
     * 搜尋訂單
     *
     * @param array $params
     *
     * @return mixed
     */
    public function searchOrder(array $params)
    {
        $order = [
            'MerchantID'      => config('spgateway.mpg.MerchantID'),
            'Version'         => '1.1',
            'RespondType'     => $params['RespondType'] ?? 'JSON',
            'TimeStamp'       => time(),
            'MerchantOrderNo' => $params['MerchantOrderNo'],
            'Amt'             => $params['Amt'],
        ];

        $order['CheckValue'] = $this->generateTradeInfoCheckValue($order);

        $res = $this->client->request('POST',
            $this->apiUrl['QUERY_TRADE_INFO_API'], [
                'form_params' => $order,
                'verify'      => false
            ])->getBody()->getContents();

        $result = json_decode($res);

        return $result;
    }

    protected function generateTradeInfoCheckValue($params)
    {
        $data = [
            'IV'              => config('spgateway.mpg.HashIV'),
            'Amt'             => $params['Amt'],
            'MerchantID'      => config('spgateway.mpg.MerchantID'),
            'MerchantOrderNo' => $params['MerchantOrderNo'],
            'Key'             => config('spgateway.mpg.HashKey'),
        ];

        $check_code_str = http_build_query($data);
        $checkValue = strtoupper(hash("sha256", $check_code_str));

        return $checkValue;
    }
}
