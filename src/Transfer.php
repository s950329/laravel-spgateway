<?php

namespace LeoChien\Spgateway;

use LeoChien\Spgateway\Libraries\Helpers;

class Transfer
{
    private $apiUrl;
    private $helpers;
    private $postData;
    private $postDataEncrypted;

    public function __construct()
    {
        if (env('APP_ENV') === 'production') {
            $this->apiUrl['CHARGE_INSTRUCT_API']
                = 'https://core.spgateway.com/API/ChargeInstruct';
        } else {
            $this->apiUrl['CHARGE_INSTRUCT_API']
                = 'https://ccore.spgateway.com/API/ChargeInstruct';
        }

        $this->helpers = new Helpers();
    }

    /**
     * 產生平台扣款指示必要欄位
     *
     * @param      $merchantID
     * @param      $amount
     * @param      $feeType
     * @param      $balanceType
     *
     * @return $this|Transfer
     */
    public function generate(
        $merchantID,
        $amount,
        $feeType,
        $balanceType
    ) {
        // 智付通平台費用扣款必要資訊
        $this->postData = [
            'Version'     => '1.0',
            'TimeStamp'   => time(),
            'MerchantID'  => $merchantID,
            'Amount'      => $amount,
            'FeeType'     => $feeType,
            'BalanceType' => $balanceType,
        ];

        return $this->encrypt();
    }

    /**
     * 加密資料
     *
     * @return $this
     */
    public function encrypt()
    {
        $postData_ = $this->helpers->encryptPostData($this->postData);

        $this->postDataEncrypted = [
            'PartnerID_' => config('spgateway.PartnerID'),
            'PostData_'  => $postData_,
        ];

        return $this;
    }

    /**
     * 傳送扣款指示要求到智付通
     *
     * @return mixed
     */
    public function send()
    {
        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['CHARGE_INSTRUCT_API'],
            $this->postDataEncrypted
        );

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    public function getPostData(){
        return $this->postData;
    }

    public function getPostDataEncrypted(){
        return $this->postDataEncrypted;
    }
}