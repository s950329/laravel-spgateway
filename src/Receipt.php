<?php
/**
 *
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
     *
     * @return array
     */
    public function generateReceiptData(
        array $params
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

        $postData = array_filter($postData, function ($value) {
            return ($value !== null && $value !== false && $value !== '');
        });

        // 加密
        $postDataEncrypted = $this->encryptPostData($postData);

        return [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $postDataEncrypted,
        ];
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
            $this->addPadding($postData),
            'AES-256-CBC',
            config('spgateway.receipt.HashKey'),
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            config('spgateway.receipt.HashIV')
        )));

        return $post_data;
    }

    public function addPadding(
        $string,
        $blocksize = 32
    ) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
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