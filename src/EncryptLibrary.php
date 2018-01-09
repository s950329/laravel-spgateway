<?php
/**
 *
 *
 * User: Chu
 * Date: 2018/1/8
 * Time: 下午7:30
 */

namespace LeoChien\Spgateway;


class EncryptLibrary
{
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
}