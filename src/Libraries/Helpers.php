<?php
/**
 *
 *
 * User: Chu
 * Date: 2018/1/9
 * Time: 下午6:04
 */

namespace LeoChien\Spgateway\Libraries;

use GuzzleHttp\Client;

class Helpers
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * 智付通資料加密
     *
     * @param      $postData
     *
     * @param null $key
     * @param null $iv
     *
     * @return string
     */
    public function encryptPostData(
        $postData,
        $key = null,
        $iv = null
    ) {
        // 所有資料與欄位使用 = 符號組合，並用 & 符號串起字串
        $postData = http_build_query($postData);

        // 加密字串
        $post_data = trim(bin2hex(openssl_encrypt(
            $this->addPadding($postData),
            'AES-256-CBC',
            $key ?? config('spgateway.mpg.HashKey'),
            OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,
            $iv ?? config('spgateway.mpg.HashIV')
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

    public function sendPostRequest($url, $postData, $options = [])
    {
        return $this->client
            ->request(
                'POST',
                $url,
                array_merge(['form_params' => $postData], $options)
            )
            ->getBody()
            ->getContents();
    }

    /**
     * 產生訂單編號
     *
     * @return string
     */
    public function generateOrderNo()
    {
        return date('YmdHis') . str_random(6);
    }
}
