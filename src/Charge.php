<?php

namespace LeoChien\Spgateway;

use LeoChien\Spgateway\Libraries\Helpers;

class Charge
{
    protected $helpers;
    protected $apiUrl;
    protected $postData;
    protected $postDataEncrypted;

    public function __construct(Helpers $helpers)
    {
        if (config('app.env') === 'production') {
            $this->apiUrl = 'https://core.spgateway.com/API/CreditCard';
        } else {
            $this->apiUrl = 'https://ccore.spgateway.com/API/CreditCard';
        }

        $this->helpers = $helpers;
    }

    /**
     * 產生智付通自動扣款必要資訊
     *
     * @param       $amount   integer 訂單金額
     * @param       $email    string 訂購人email
     * @param       $prodDesc string 購買商品資訊
     * @param array $params
     *
     * @return $this|Exception
     */
    public function generate(
        $amount,
        $email,
        $prodDesc,
        $tokenValue,
        $tokenTerm,
        array $params = []
    ) {
        try {
            $postData = [
                'TimeStamp' => time(),
                'Version' => '1.0',
                'MerchantOrderNo' => $params['MerchantOrderNo'] ?? $this->helpers->generateOrderNo(),
                'Amt' => $amount,
                'ProdDesc' => $prodDesc,
                'PayerEmail' => $email,
                'TokenValue' => $tokenValue,
                'TokenTerm' => $tokenTerm,
                'TokenSwitch' => $params['TokenSwitch'] ?? 'on',
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

    /**
     * 前台送出訂單建立表單到智付通
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function send($options = [])
    {
        $res = $this->helpers->sendPostRequest(
            $this->apiUrl,
            $this->postDataEncrypted,
            $options
        );

        $res = json_decode($res);

        return $res;
    }

    /**
     * 加密訂單資料
     *
     * @return $this
     */
    public function encrypt()
    {
        $PostData_ = $this->helpers->encryptPostData($this->postData);

        $this->postDataEncrypted = [
            'MerchantID_' => config('spgateway.mpg.MerchantID'),
            'PostData_' => $PostData_,
            'Pos_' => 'JSON',
        ];

        return $this;
    }

    public function getPostData()
    {
        return $this->postData;
    }

    public function getPostDataEncrypted()
    {
        return $this->postDataEncrypted;
    }
}
