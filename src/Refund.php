<?php
/**
 *
 *
 * User: Chu
 * Date: 2017/12/27
 * Time: 上午11:35
 */

namespace LeoChien\Spgateway;


use GuzzleHttp\Client;

class Refund
{
    private $apiUrl;
    private $client;

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

        $this->client = new Client();
    }

    /**
     * 智付通退費
     *
     * @param $MerchantOrderNo
     * @param $Amt
     * @param $notifyUrl
     *
     * @return bool|mixed|string
     */
    public function refund($MerchantOrderNo, $Amt, $notifyUrl = null)
    {
        $mpg = new MPG();
        $tradeInfo = $mpg->searchOrder([
            'MerchantOrderNo' => $MerchantOrderNo,
            'Amt'             => $Amt,
        ]);
        $tradeInfo = $tradeInfo->Result;

        // 訂單取消授權
        if ($tradeInfo->TradeStatus === "1"
            && $tradeInfo->CloseStatus === "0"
        ) {
            $creditCancelData
                = $this->generateCreditCancelPostData($MerchantOrderNo, $Amt,
                $notifyUrl);

            /* 寫入log */
            $res = $this->client->request('POST',
                $this->apiUrl['CREDIT_CARD_CANCEL_API'], [
                    'form_params' => $creditCancelData,
                    'verify'      => false
                ])->getBody()->getContents();

            $res = json_decode($res);

            /* 回傳結果 */
            return $res;
        } else {
            if ($tradeInfo->TradeStatus === "1"
                && $tradeInfo->CloseStatus === "3"
            ) {
                $refundData
                    = $this->generateCreditClosePostData($MerchantOrderNo, $Amt);

                /* 寫入log */
                /* 寫入log */
                $res = $this->client->request('POST',
                    $this->apiUrl['REFUND_API'], [
                        'form_params' => $refundData,
                        'verify'      => false
                    ])->getBody()->getContents();

                $res = json_decode($res);

                /* 回傳結果 */
                return $res;
            } else {
                return (Object)[
                    'Status' => false,
                ];
            }
        }
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
    public function generateCreditCancelPostData(
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

        $PostData_ = $this->encryptPostData($postData);

        return [
            'MerchantID_' => env('SPGATEWAY_MERCHANT_ID'),
            'PostData_'   => $PostData_,
        ];
    }

    /**
     * 產生退費必要資料
     *
     * @param $MerchantOrderNo
     * @param $Amt
     *
     * @return array
     */
    public function generateCreditClosePostData($MerchantOrderNo, $Amt)
    {
        $postData = [
            'RespondType'     => 'JSON',
            'Version'         => '1.0',
            'Amt'             => $MerchantOrderNo,
            'MerchantOrderNo' => $Amt,
            'IndexType'       => 1,
            'TimeStamp'       => time(),
            'CloseType'       => 2,
        ];

        $PostData_ = $this->encryptPostData($postData);

        return [
            'MerchantID_' => env('SPGATEWAY_MERCHANT_ID'),
            'PostData_'   => $PostData_,
        ];
    }

    /**
     * 加密欄位
     *
     * @param $postData
     *
     * @return string
     */
    public function encryptPostData($postData)
    {
        // 所有資料與欄位使用 = 符號組合，並用 & 符號串起字串
        $postData = http_build_query($postData);

        // 加密字串
        $post_data = trim(bin2hex(openssl_encrypt(
            $this->addPadding($postData),
            'AES-256-CBC',
            env('SPGATEWAY_HASH_KEY'),
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            env('SPGATEWAY_HASH_IV')
        )));

        return $post_data;
    }

    public
    function addPadding(
        $string,
        $blocksize = 32
    ) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }
}