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

    public function generateOrder(array $form)
    {
        try {
            $validator = $this->formValidator($form);

            if ($validator !== true) {
                throw new Exception($validator['field']
                    . $validator['message']);
            }

            $order = [
                'MerchantID'      => config('spgateway.mpg.MerchantID'),
                'RespondType'     => $form['RespondType'] ?? 'JSON',
                'TimeStamp'       => time(),
                'Version'         => config('spgateway.mpg.Version'),
                'LangType'        => $form['LangType'] ?? 'zh-tw',
                'MerchantOrderNo' => $form['MerchantOrderNo'],
                'Amt'             => $form['Amt'],
                'ItemDesc'        => $form['ItemDesc'],
                'TradeLimit'      => $form['TradeLimit'] ?? 180,
                'ExpireDate'      => $form['ExpireDate'] ?? null,
                'ExpireTime'      => $form['ExpireTime'] ?? null,
                'ReturnURL'       => $form['ReturnURL'] ?? null,
                'NotifyURL'       => $form['NotifyURL'] ?? null,
                'CustomerURL'     => $form['CustomerURL'] ?? null,
                'ClientBackURL'   => $form['ClientBackURL'] ?? null,
                'Email'           => $form['Email'],
                'EmailModify'     => $form['EmailModify'] ?? 1,
                'LoginType'       => $form['LoginType'] ?? 0,
                'OrderComment'    => $form['OrderComment'] ?? null,
                'TokenTerm'       => $form['TokenTerm'] ?? null,
                'CREDIT'          => $form['CREDIT'] ?? null,
                'CreditRed'       => $form['CreditRed'] ?? null,
                'InstFlag'        => $form['InstFlag'] ?? null,
                'UNIONPAY'        => $form['UNIONPAY'] ?? null,
                'WEBATM'          => $form['WEBATM'] ?? null,
                'VACC'            => $form['VACC'] ?? null,
                'CVS'             => $form['CVS'] ?? null,
                'BARCODE'         => $form['BARCODE'] ?? null,
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

    private function formValidator($form)
    {
        if (isset($form['RespondType'])) {
            if (!in_array($form['RespondType'], ['JSON', 'String'])) {
                return $this->errorMessage('RespondType', '必須為JSON或是String');
            }
        }

        if (isset($form['RespondType'])) {
            if (!in_array($form['LangType'], ['en', 'zh-tw'])) {
                return $this->errorMessage('LangType',
                    '英文版參數為 en，繁體中文版參數為 zh-tw');
            }
        }

        if (!is_numeric($form['Amt'])) {
            return $this->errorMessage('Amt', '必須為大於0的整數');
        }

        if (strlen($form['ItemDesc']) > 50) {
            return $this->errorMessage('Amt', '必須小於50字');
        }

        if (isset($form['TradeLimit'])) {
            if ($form['TradeLimit'] < 60 || $form['TradeLimit'] > 900) {
                return $this->errorMessage('TradeLimit', '秒數下限為60秒，上限為900秒');
            }
        }

        if (isset($form['ExpireDate'])) {
            if (date_parse_from_format('Y-m-d',
                    $form['ExpireDate'])['error_count']
                > 0
            ) {
                return $this->errorMessage('ExpireDate',
                    '格式需為Y-m-d，如:2017-01-01');
            }

            if (strtotime($form['ExpireDate']) < time()
                || strtotime('-180 days', $form['ExpireDate']) > time()
            ) {
                return $this->errorMessage('ExpireDate', '可接受最大值為 180 天');
            }
        }

        if (isset($form['ExpireTime'])) {
            if (date_parse_from_format('H:i:s',
                    $form['ExpireTime'])['error_count']
                > 0
            ) {
                return $this->errorMessage('ExpireTime',
                    '格式需為Y-m-d，如:2017-01-01');
            }

            if (strtotime($form['ExpireTime']) < time()
                || strtotime('-1 hour', $form['ExpireTime']) > time()
            ) {
                return $this->errorMessage('ExpireTime', '可接受最大值為 180 天');
            }
        }

        if (isset($form['ReturnURL'])) {
            if (!filter_var($form['ReturnURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('ReturnURL', '必須為合法的Url');
            }
        }

        if (isset($form['NotifyURL'])) {
            if (!filter_var($form['NotifyURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('NotifyURL', '必須為合法的Url');
            }
        }

        if (isset($form['CustomerURL'])) {
            if (!filter_var($form['CustomerURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('CustomerURL', '必須為合法的Url');
            }
        }

        if (isset($form['ClientBackURL'])) {
            if (!filter_var($form['ClientBackURL'], FILTER_VALIDATE_URL)) {
                return $this->errorMessage('ClientBackURL', '必須為合法的Url');
            }
        }

        if (!filter_var($form['Email'], FILTER_VALIDATE_EMAIL)) {
            return $this->errorMessage('Email', '必須為合法的Email');
        }

        if (isset($form['EmailModify'])) {
            if (!in_array($form['EmailModify'], [1, 0])) {
                return $this->errorMessage('EmailModify', '必須為0或1');
            }
        }

        if (isset($form['LoginType'])) {
            if (!in_array($form['LoginType'], [1, 0])) {
                return $this->errorMessage('LoginType', '必須為0或1');
            }
        }

        if (isset($form['OrderComment'])) {
            if (strlen($form['OrderComment']) > 300) {
                return $this->errorMessage('OrderComment', '必須小於300字');
            }
        }

        if (isset($form['CREDIT'])) {
            if (isset($form['CREDIT'])) {
                if (!in_array($form['CREDIT'], [1, 0])) {
                    return $this->errorMessage('CREDIT', '必須為0或1');
                }
            }
        }

        if (isset($form['CreditRed'])) {
            if (isset($form['CreditRed'])) {
                if (!in_array($form['CreditRed'], [1, 0])) {
                    return $this->errorMessage('CreditRed', '必須為0或1');
                }
            }
        }

        if (isset($form['InstFlag'])) {
            array_walk(explode(',', $form['InstFlag']), function ($val, $key) {
                if (!in_array($val, [1, 3, 6, 12, 18, 24])) {
                    return $this->errorMessage('InstFlag', '期數必須為3、6、12、18、24');
                }
            });
        }

        if (isset($form['UNIONPAY'])) {
            if (!in_array($form['UNIONPAY'], [1, 0])) {
                return $this->errorMessage('UNIONPAY', '必須為0或1');
            }
        }

        if (isset($form['WEBATM'])) {
            if (!in_array($form['WEBATM'], [1, 0])) {
                return $this->errorMessage('WEBATM', '必須為0或1');
            }
        }

        if (isset($form['EmailModify'])) {
            if (!in_array($form['EmailModify'], [1, 0])) {
                return $this->errorMessage('EmailModify', '必須為0或1');
            }
        }

        if (isset($form['VACC'])) {
            if (!in_array($form['VACC'], [1, 0])) {
                return $this->errorMessage('VACC', '必須為0或1');
            }
        }

        if (isset($form['CVS'])) {
            if (!in_array($form['CVS'], [1, 0])) {
                return $this->errorMessage('CVS', '必須為0或1');
            }
        }

        if (isset($form['BARCODE'])) {
            if (!in_array($form['BARCODE'], [1, 0])) {
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
