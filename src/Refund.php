<?php

namespace LeoChien\Spgateway;

use LeoChien\Spgateway\Libraries\Helpers;

class Refund
{
    private $apiUrl;
    private $helpers;
    private $postData;
    private $postType;
    private $postDataEncrypted;

    public function __construct()
    {
        $this->apiUrl = [];
        if (env('APP_ENV') === 'production') {
            $this->apiUrl['CREDIT_CARD_CANCEL_API']
                = 'https://core.spgateway.com/API/CreditCard/Cancel';
            $this->apiUrl['REFUND_API']
                = 'https://core.spgateway.com/API/CreditCard/Close';
        } else {
            $this->apiUrl['CREDIT_CARD_CANCEL_API']
                = 'https://ccore.spgateway.com/API/CreditCard/Cancel';
            $this->apiUrl['REFUND_API']
                = 'https://ccore.spgateway.com/API/CreditCard/Close';
        }

        $this->helpers = new Helpers();
    }

    /**
     * 產生智付通退費 / 取消授權必要資料
     *
     * @param      $orderNo
     * @param      $amount
     * @param null $notifyUrl
     *
     * @return Refund|object
     */
    public function generate($orderNo, $amount, $notifyUrl = null)
    {
        $mpg = new MPG();
        $tradeInfo = $mpg->search($orderNo, $amount);
        $tradeInfo = $tradeInfo->Result;

        if ($tradeInfo->TradeStatus === "1"
            && $tradeInfo->CloseStatus === "0"
        ) {
            $this->postData = $this->generateCreditCancel(
                $orderNo,
                $amount,
                $notifyUrl
            );

            $this->postType = 'cancel';
        } elseif ($tradeInfo->TradeStatus === "1"
            && $tradeInfo->CloseStatus === "3"
        ) {
            $this->postData = $this->generateCreditRefund(
                $orderNo,
                $amount
            );

            $this->postType = 'refund';
        } else {
            return (Object)[
                'Status' => false,
            ];
        }

        return $this->encrypt();
    }

    /**
     * 加密資料
     *
     * @return $this
     */
    private function encrypt()
    {
        $PostData_ = $this->helpers->encryptPostData($this->postData);

        $this->postDataEncrypted = [
            'MerchantID_' => env('SPGATEWAY_MERCHANT_ID'),
            'PostData_'   => $PostData_,
        ];

        return $this;
    }

    /**
     * 傳送退款 / 取消授權請求到智付通
     *
     * @return mixed|string
     */
    public function send()
    {
        if($this->postType === 'cancel'){
            $url = $this->apiUrl['CREDIT_CARD_CANCEL_API'];
        } else {
            $url = $this->apiUrl['REFUND_API'];
        }

        $res = $this->helpers->sendPostRequest($url, $this->postDataEncrypted);

        $res = json_decode($res);

        return $res;
    }

    /**
     * 產生取消授權必要資料
     *
     * @param $MerchantOrderNo
     * @param $Amt
     * @param $notifyUrl
     *
     * @return array
     */
    private function generateCreditCancel(
        $MerchantOrderNo,
        $Amt,
        $notifyUrl
    ) {
        $postData = [
            'RespondType'     => 'JSON',
            'Version'         => '1.0',
            'Amt'             => $Amt,
            'MerchantOrderNo' => $MerchantOrderNo,
            'IndexType'       => 1,
            'TimeStamp'       => time(),
            'NotifyURL'       => $notifyUrl,
        ];

        return $postData;
    }

    /**
     * 產生退費必要資料
     *
     * @param $orderNo
     * @param $amount
     *
     * @return array
     */
    private function generateCreditRefund($orderNo, $amount)
    {
        $postData = [
            'RespondType'     => 'JSON',
            'Version'         => '1.0',
            'Amt'             => $orderNo,
            'MerchantOrderNo' => $amount,
            'IndexType'       => 1,
            'TimeStamp'       => time(),
            'CloseType'       => 2,
        ];

        return $postData;
    }
}