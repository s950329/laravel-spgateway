<?php
/**
 * 用來管理智付通發票系統
 *
 * User: Chu
 * Date: 2017/12/26
 * Time: 下午6:49
 */

namespace LeoChien\Spgateway;

use GuzzleHttp\Client;

class Receipt
{
    private $apiUrl;
    private $client;
    private $encryptLibrary;

    public function __construct()
    {
        if (env('APP_ENV') === 'production') {
            $this->apiUrl['CREATE_RECEIPT_API']
                = 'https://inv.pay2go.com/API/invoice_issue';
            $this->apiUrl['INVALID_RECEIPT_API']
                = 'https://inv.pay2go.com/API/invoice_invalid';
        } else {
            $this->apiUrl['CREATE_RECEIPT_API']
                = 'https://cinv.pay2go.com/API/invoice_issue';
            $this->apiUrl['INVALID_RECEIPT_API']
                = 'https://cinv.pay2go.com/API/invoice_invalid';
        }

        $this->client = new Client();
        $this->encryptLibrary = new EncryptLibrary();
    }

    public function createReceipt(
        $param
    ) {
        // 產生送至智付通的資料
        $spgatewayPostData
            = $this->generateReceiptData($param);

        /* 呼叫智付通電子發票API */
        $res = $this->client->request(
            'POST',
            $this->apiUrl['CREATE_RECEIPT_API'],
            ['form_params' => $spgatewayPostData,]
        )->getBody()->getContents();

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    /**
     * 產生智付通開立電子發票必要資訊
     *
     * @param array $params
     * @param bool  $encrypt
     *
     * @return array
     */
    public function generateReceiptData(
        array $params,
        $encrypt = true
    ) {
        $params['TaxRate'] = $params['TaxRate'] ?? 5;
        $params['Category'] = $params['Category'] ?? 'B2C';

        $itemAmt = [];
        foreach ($params['ItemCount'] as $k => $v) {
            if ($params['Category'] === 'B2B') {
                $itemAmt[$k] = $this->priceBeforeTax($params['ItemCount'][$k]
                    * $params['ItemPrice'][$k], $params['TaxRate']);
            } else {
                $itemAmt[$k] = $params['ItemCount'][$k]
                    * $params['ItemPrice'][$k];
            }
        }

        $params['ItemName'] = implode('|', $params['ItemName']);
        $params['ItemCount'] = implode('|', $params['ItemCount']);
        $params['ItemUnit'] = implode('|', $params['ItemUnit']);
        $params['ItemPrice'] = implode('|', $params['ItemPrice']);
        $params['ItemAmt'] = implode('|', $itemAmt);


        // 智付通開立電子發票必要資訊
        $postData = [
            'RespondType'      => $params['RespondType'] ?? 'JSON',
            'Version'          => '1.3',
            'TimeStamp'        => time(),
            'MerchantOrderNo'  => $params['MerchantOrderNo'],
            'Status'           => $params['Status'] ?? '1',
            'CreateStatusTime' => $params['CreateStatusTime'] ?? null,
            'Category'         => $params['Category'] ?? 'B2C',
            'BuyerName'        => $params['BuyerName'],
            'BuyerUBN'         => $params['BuyerUBN'] ?? null,
            'BuyerEmail'       => $params['BuyerEmail'],
            'PrintFlag'        => $params['PrintFlag'] ?? 'Y',
            'TaxType'          => $params['TaxType'] ?? '1',
            'TaxRate'          => $params['TaxRate'] ?? 5,
            'Amt'              => $this->priceBeforeTax($params['TotalAmt'],
                $params['TaxRate']),
            'TaxAmt'           => $this->calcTax($params['TotalAmt'],
                $params['TaxRate']),
            'TotalAmt'         => $params['TotalAmt'],
            'ItemName'         => $params['ItemName'],
            'ItemCount'        => $params['ItemCount'],
            'ItemUnit'         => $params['ItemUnit'],
            'ItemPrice'        => $params['ItemPrice'],
            'ItemAmt'          => $params['ItemAmt'],
            'ItemTaxType'      => $params['ItemTaxType'] ?? null,
            'Comment'          => $params['Comment'] ?? null
        ];

        if($encrypt){
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

    public function calcTax($price, $tax)
    {
        $taxRate = $tax / 100;
        return $price - round($price / (1 + $taxRate));
    }

    public function priceBeforeTax($price, $tax)
    {
        return $price - $this->calcTax($price, $tax);
    }

    /**
     * 呼叫智付通電子發票API作廢電子發票
     *
     * @param $params
     *
     * @return bool
     */
    public function invalidReceipt($params)
    {
        // 產生送至智付通的資料
        $spgatewayPostData
            = $this->generateInvalidReceiptData($params);

        /* 呼叫智付通電子發票API */
        $res = $this->client->request(
            'POST',
            $this->apiUrl['INVALID_RECEIPT_API'],
            ['form_params' => $spgatewayPostData,]
        )->getBody()->getContents();

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    /**
     * 產生智付通作廢電子發票必要資訊
     *
     * @param $params
     *
     * @return array
     */
    public function generateInvalidReceiptData(
        $params
    ) {
        // 智付通作廢電子發票必要資訊
        $postData = [
            'RespondType'   => 'JSON',
            'Version'       => '1.0',
            'TimeStamp'     => time(),
            'InvoiceNumber' => $params['InvoiceNumber'],
            'InvalidReason' => $params['InvalidReason'],
        ];

        // 加密
        $postDataEncrypted = $this->encryptPostData($postData);

        return [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $postDataEncrypted,
        ];
    }
}