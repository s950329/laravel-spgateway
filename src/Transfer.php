<?php
/**
 * 用來做合作金流推廣商請/退款
 *
 * User: Chu
 * Date: 2018/1/8
 * Time: 下午7:26
 */

namespace LeoChien\Spgateway;


use GuzzleHttp\Client;

class Transfer
{
    private $apiUrl;
    private $client;
    private $encryptLibrary;

    public function __construct()
    {
        if (env('APP_ENV') === 'production') {
            $this->apiUrl['CREATE_RECEIPT_API']
                = 'https://core.spgateway.com/API/ChargeInstruct';
        } else {
            $this->apiUrl['CREATE_RECEIPT_API']
                = 'https://ccore.spgateway.com/API/ChargeInstruct';
        }

        $this->client = new Client();
        $this->encryptLibrary = new EncryptLibrary();
    }

    /**
     * 產生智付通開立電子發票必要資訊
     *
     * @param array $params
     * @param bool  $encrypt
     *
     * @return array
     */
    public function generateTransferData(
        array $params,
        $encrypt = true
    ) {
        // 智付通開立電子發票必要資訊
        $postData = [
            'Version'     => '1.0',
            'TimeStamp'   => time(),
            'MerchantID'  => $params['MerchantID'],
            'Amt'         => $params['Amt'],
            'FeeType'     => $params['FeeType'],
            'BalanceType' => $params['BalanceType'],
        ];

        if ($encrypt) {
            return $this->encryptReceiptData($postData);
        } else {
            return $postData;
        }
    }

    public function encryptReceiptData($postData)
    {
        $postData = array_filter($postData, function ($value) {
            return ($value !== null && $value !== false && $value !== '');
        });

        // 加密
        $postDataEncrypted = $this->encryptLibrary->encryptPostData($postData);

        return [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $postDataEncrypted,
        ];
    }
}