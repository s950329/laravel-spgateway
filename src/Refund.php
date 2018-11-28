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
        if (config('app.env') === 'production') {
            $this->apiUrl['CREDIT_CARD_CANCEL_API']
                = 'https://core.spgateway.com/API/CreditCard/Cancel';
            $this->apiUrl['CREDIT_CARD_REFUND_API']
                = 'https://core.spgateway.com/API/CreditCard/Close';
            $this->apiUrl['DELAYED_REFUND_API']
                = 'https://core.spgateway.com/API/Refund';
        } else {
            $this->apiUrl['CREDIT_CARD_CANCEL_API']
                = 'https://ccore.spgateway.com/API/CreditCard/Cancel';
            $this->apiUrl['CREDIT_CARD_REFUND_API']
                = 'https://ccore.spgateway.com/API/CreditCard/Close';
            $this->apiUrl['DELAYED_REFUND_API']
                = 'https://ccore.spgateway.com/API/Refund';
        }

        $this->helpers = new Helpers();
    }

    /**
     * 產生智付通退費 / 取消授權必要資料
     *
     * @param       $orderNo
     * @param       $amount
     * @param null  $notifyUrl
     * @param bool  $delayed
     * @param array $params
     *
     * @return Refund|object
     */
    public function generate(
        $orderNo,
        $amount,
        $notifyUrl = null,
        $delayed = false,
        $params = []
    ) {
        $mpg = new MPG();
        $tradeInfo = $mpg->search($orderNo, $amount);
        $tradeInfo = $tradeInfo->Result;

        if ($tradeInfo->TradeStatus === "1"
            && $tradeInfo->PaymentType === "CREDIT"
            && $tradeInfo->CloseStatus === "0"
        ) {
            $this->postData = $this->generateCreditCancel(
                $orderNo,
                $amount,
                $notifyUrl
            );

            $this->postType = 'cancel';
        } elseif ($tradeInfo->TradeStatus === "1"
            && $tradeInfo->PaymentType === "CREDIT"
            && $tradeInfo->CloseStatus === "3"
        ) {
            $this->postData = $this->generateCreditRefund(
                $orderNo,
                $amount,
                $params
            );

            $this->postType = 'refund';
        } elseif ($tradeInfo->TradeStatus === "1"
            && $delayed === true
        ) {
            $this->postData = $this->generateDelayedRefund(
                $orderNo,
                $params
            );

            $this->postType = 'delayed';
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
            'MerchantID_' => config('spgateway.mpg.MerchantID'),
            'PostData_'   => $PostData_,
        ];

        return $this;
    }

    /**
     * 傳送退款 / 取消授權請求到智付通
     *
     * @param array $headers 自訂Headers
     *
     * @return mixed|string
     */
    public function send($headers = [])
    {
        if ($this->postType === 'cancel') {
            $url = $this->apiUrl['CREDIT_CARD_CANCEL_API'];
        } elseif ($this->postType === 'refund') {
            $url = $this->apiUrl['CREDIT_CARD_REFUND_API'];
        } elseif ($this->postType === 'delayed') {
            $url = $this->apiUrl['DELAYED_REFUND_API'];
        }

        $res = $this->helpers->sendPostRequest(
            $url,
            $this->postDataEncrypted,
            $headers
        );

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
     * @param $params
     *
     * @return array
     */
    private function generateCreditRefund($orderNo, $amount, $params)
    {
        $postData = [
            'RespondType'     => 'JSON',
            'Version'         => '1.0',
            'Amt'             => $params['RefundAmt'] ?? $amount,
            'MerchantOrderNo' => $orderNo,
            'IndexType'       => 1,
            'TimeStamp'       => time(),
            'CloseType'       => 2,
        ];

        return $postData;
    }

    /**
     *
     *
     * @param $orderNo
     * @param $params
     *
     * @return array
     */
    private function generateDelayedRefund($orderNo, $params)
    {
        $postData = [
            'Version'    => '1.0',
            'TimeStamp'  => time(),
            'MerOrderNo' => $orderNo
        ];

        $postData = array_merge($postData, $params);

        return $postData;
    }

    public function getPostData()
    {
        return $this->postData;
    }

    public function getPostType()
    {
        return $this->postType;
    }

    public function getPostDataEncrypted()
    {
        return $this->postDataEncrypted;
    }
}