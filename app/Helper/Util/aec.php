<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/30
 * Time: 16:10
 */
namespace App\Helper\Util;

class AES
{
    /**
     * @param $key 加密KEY
     * @param $iv 加密向量
     * @param $data 需要加密的数据
     * @return string
     */
    public static function encrypt($key, $iv, $data)
    {
        //echo openssl_encrypt($data, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode(openssl_encrypt($data, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv));
    }

    /**
     * @param $key
     * @param $iv
     * @param $data
     * @return string
     */
    public static function decrypt($key, $iv, $data)
    {
        return openssl_decrypt(base64_decode($data), "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
    }
}