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
        if (config('app.env') === 'production') {
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
        $postData_ = $this->encryptPostData($this->postData);

        $this->postDataEncrypted = [
            'PartnerID_' => config('spgateway.PartnerID'),
            'PostData_'  => $postData_,
        ];

        return $this;
    }

    /**
     * 智付通資料加密
     *
     * @param $postData
     *
     * @return string
     */
    public function encryptPostData(
        $postData
    ) {
        // 所有資料與欄位使用 = 符號組合，並用 & 符號串起字串
        $postData = http_build_query($postData);

        // 加密字串
        $post_data = trim(bin2hex(openssl_encrypt(
            $this->helpers->addPadding($postData),
            'AES-256-CBC',
            config('spgateway.CompanyKey'),
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            config('spgateway.CompanyIV')
        )));

        return $post_data;
    }

    /**
     * 傳送扣款指示要求到智付通
     *
     * @param array $headers 自訂Headers
     *
     * @return mixed
     */
    public function send($options = [])
    {
        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['CHARGE_INSTRUCT_API'],
            $this->postDataEncrypted,
            $options
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
