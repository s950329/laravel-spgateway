<?php

namespace LeoChien\Spgateway;

use LeoChien\Spgateway\Libraries\Helpers;

class Receipt
{
    private $apiUrl;
    private $helpers;
    private $postData;
    private $postDataEncrypted;
    private $triggerPostData;
    private $triggerPostDataEncrypted;
    private $invalidPostData;
    private $invalidPostDataEncrypted;

    public function __construct()
    {
        if (config('app.env') === 'production') {
            $this->apiUrl['CREATE_RECEIPT_API']
                = 'https://inv.pay2go.com/API/invoice_issue';
            $this->apiUrl['INVALID_RECEIPT_API']
                = 'https://inv.pay2go.com/API/invoice_invalid';
            $this->apiUrl['TRIGGER_RECEIPT_API']
                = 'https://inv.pay2go.com/API/invoice_touch_issue';
            $this->apiUrl['SEARCH_RECEIPT_API']
                = 'https://inv.pay2go.com/API/invoice_search';
        } else {
            $this->apiUrl['CREATE_RECEIPT_API']
                = 'https://cinv.pay2go.com/API/invoice_issue';
            $this->apiUrl['INVALID_RECEIPT_API']
                = 'https://cinv.pay2go.com/API/invoice_invalid';
            $this->apiUrl['TRIGGER_RECEIPT_API']
                = 'https://cinv.pay2go.com/API/invoice_touch_issue';
            $this->apiUrl['SEARCH_RECEIPT_API']
                = 'https://cinv.pay2go.com/API/invoice_search';
        }

        $this->helpers = new Helpers();
    }

    /**
     * 產生智付通開立電子發票必要資訊
     *
     * @param array $params
     *
     * @return $this|array
     */
    public function generate(array $params)
    {
        $params['TaxRate'] = $params['TaxRate'] ?? 5;
        $params['Category'] = $params['Category'] ?? 'B2C';

        $itemAmt = [];

        if ($params['Category'] === 'B2B') {
            $params['ItemPrice'] = array_map(function ($price) use ($params) {
                return $this->priceBeforeTax($price, $params['TaxRate']);
            }, $params['ItemPrice']);
        }

        foreach ($params['ItemCount'] as $k => $v) {
            $itemAmt[$k] = $params['ItemCount'][$k]
                * $params['ItemPrice'][$k];
        }

        $params['ItemName'] = implode('|', $params['ItemName']);
        $params['ItemCount'] = implode('|', $params['ItemCount']);
        $params['ItemUnit'] = implode('|', $params['ItemUnit']);
        $params['ItemPrice'] = implode('|', $params['ItemPrice']);
        $params['ItemAmt'] = implode('|', $itemAmt);

        // 智付通開立電子發票必要資訊
        $postData = [
            'RespondType'      => $params['RespondType'] ?? 'JSON',
            'Version'          => '1.4',
            'TimeStamp'        => time(),
            'TransNum'         => $params['TransNum'] ?? null,
            'MerchantOrderNo'  => $params['MerchantOrderNo'] ??
                $this->helpers->generateOrderNo(),
            'Status'           => $params['Status'] ?? '1',
            'CreateStatusTime' => $params['CreateStatusTime'] ?? null,
            'Category'         => $params['Category'] ?? 'B2C',
            'BuyerName'        => $params['BuyerName'],
            'BuyerUBN'         => $params['BuyerUBN'] ?? null,
            'BuyerAddress'     => $params['BuyerAddress'] ?? null,
            'BuyerEmail'       => $params['BuyerEmail'],
            'CarrierType'      => $params['CarrierType'] ?? null,
            'CarrierNum'       => $params['CarrierNum'] ?? null,
            'LoveCode'         => $params['LoveCode'] ?? null,
            'PrintFlag'        => $params['PrintFlag'] ?? 'Y',
            'TaxType'          => $params['TaxType'] ?? '1',
            'TaxRate'          => $params['TaxRate'] ?? 5,
            'CustomsClearance' => $params['CustomsClearance'] ?? null,
            'Amt'              => $this->priceBeforeTax($params['TotalAmt'],
                $params['TaxRate']),
            'AmtSales'         => $params['AmtSales'] ?? null,
            'AmtZero'          => $params['AmtZero'] ?? null,
            'AmtFree'          => $params['AmtFree'] ?? null,
            'TaxAmt'           => $this->calcTax($params['TotalAmt'],
                $params['TaxRate']),
            'TotalAmt'         => $params['TotalAmt'],
            'ItemName'         => $params['ItemName'],
            'ItemCount'        => $params['ItemCount'],
            'ItemUnit'         => $params['ItemUnit'],
            'ItemPrice'        => $params['ItemPrice'],
            'ItemAmt'          => $params['ItemAmt'],
            'ItemTaxType'      => $params['ItemTaxType'] ?? null,
            'Comment'          => $params['Comment'] ?? null,
        ];

        $this->postData = array_filter($postData, function ($value) {
            return ($value !== null && $value !== false && $value !== '');
        });

        return $this->encrypt();
    }

    public function priceBeforeTax($price, $tax)
    {
        return $price - $this->calcTax($price, $tax);
    }

    public function calcTax($price, $tax)
    {
        $taxRate = $tax / 100;

        return $price - round($price / (1 + $taxRate));
    }

    /**
     * 加密開立發票資料
     *
     * @return $this
     */
    private function encrypt()
    {
        $postDataEncrypted = $this->helpers->encryptPostData(
            $this->postData,
            config('spgateway.receipt.HashKey'),
            config('spgateway.receipt.HashIV')
        );

        $this->postDataEncrypted = [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $postDataEncrypted,
        ];

        return $this;
    }

    /**
     * 傳送開立發票請求到智付通
     *
     * @param array $headers 自訂Headers
     *
     * @return mixed
     */
    public function send($options = [])
    {
        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['CREATE_RECEIPT_API'],
            $this->postDataEncrypted,
            $options
        );

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    /**
     * 產生智付通觸發開立電子發票必要資訊
     *
     * @param      $invoiceTransNo
     * @param      $orderNo
     * @param      $amount
     * @param null $transNum
     *
     * @return $this
     */
    public function generateTrigger(
        $invoiceTransNo,
        $orderNo,
        $amount,
        $transNum = null
    ) {
        // 智付通作廢電子發票必要資訊
        $triggerPostData = [
            'RespondType'     => 'JSON',
            'Version'         => '1.0',
            'TimeStamp'       => time(),
            'TransNum'        => $transNum,
            'InvoiceTransNo'  => $invoiceTransNo,
            'MerchantOrderNo' => $orderNo,
            'TotalAmt'        => $amount,
        ];

        $this->triggerPostData = array_filter($triggerPostData,
            function ($value) {
                return ($value !== null && $value !== false && $value !== '');
            });

        return $this->encryptTrigger();
    }

    /**
     * 加密觸發開立發票資料
     *
     * @return $this
     */
    private function encryptTrigger()
    {
        $postDataEncrypted = $this->helpers
            ->encryptPostData(
                $this->triggerPostData,
                config('spgateway.receipt.HashKey'),
                config('spgateway.receipt.HashIV')
            );

        $this->triggerPostDataEncrypted = [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $postDataEncrypted,
        ];

        return $this;
    }

    /**
     * 送出觸發開立電子發票請求到智付通
     *
     * @return bool
     */
    public function sendTrigger()
    {
        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['TRIGGER_RECEIPT_API'],
            $this->triggerPostDataEncrypted
        );

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    /**
     * 產生智付通作廢電子發票必要資訊
     *
     * @param $receiptNumber
     * @param $invalidReason
     *
     * @return $this|array
     */
    public function generateInvalid(
        $receiptNumber,
        $invalidReason
    ) {
        // 智付通作廢電子發票必要資訊
        $this->invalidPostData = [
            'RespondType'   => 'JSON',
            'Version'       => '1.0',
            'TimeStamp'     => time(),
            'InvoiceNumber' => $receiptNumber,
            'InvalidReason' => $invalidReason,
        ];

        return $this->encryptInvalid();
    }

    /**
     * 加密作廢發票資料
     *
     * @return $this
     */
    private function encryptInvalid()
    {
        $postDataEncrypted
            = $this->helpers->encryptPostData($this->invalidPostData);

        $this->invalidPostDataEncrypted = [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $postDataEncrypted,
        ];

        return $this;
    }

    /**
     * 送出作廢電子發票請求到智付通
     *
     * @return bool
     */
    public function sendInvalid()
    {
        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['INVALID_RECEIPT_API'],
            $this->invalidPostDataEncrypted
        );

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    /**
     * 查詢發票
     *
     * @param $orderNo
     * @param $amount
     *
     * @return bool
     */
    public function search($orderNo, $amount)
    {
        $postData = [
            'RespondType'     => 'JSON',
            'Version'         => '1.1',
            'TimeStamp'       => time(),
            'SearchType'      => 1,
            'MerchantOrderNo' => $orderNo,
            'TotalAmt'        => $amount,
        ];

        $postDataEncrypted = [
            'MerchantID_' => config('spgateway.receipt.MerchantID'),
            'PostData_'   => $this->helpers->encryptPostData($postData),
        ];

        $res = $this->helpers->sendPostRequest(
            $this->apiUrl['SEARCH_RECEIPT_API'],
            $postDataEncrypted
        );

        $result = json_decode($res);

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }

    public function getPostData()
    {
        return $this->postData;
    }

    public function getPostDataEncrypted()
    {
        return $this->postDataEncrypted;
    }

    public function getTriggerPostData()
    {
        return $this->triggerPostData;
    }

    public function getTriggerPostDataEncrypted()
    {
        return $this->triggerPostDataEncrypted;
    }

    public function getInvalidPostData()
    {
        return $this->invalidPostData;
    }

    public function getInvalidPostDataEncrypted()
    {
        return $this->invalidPostDataEncrypted;
    }
}
