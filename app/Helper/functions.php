<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/28
 * Time: 11:30
 */
namespace App\Helper;

use App\Helper\Util\AES;

/**
 * 返回正确的JSon数据
 */
function output_data($data = [], $ok = true)
{
    $result = [];
    $result = $data;
    //$result['ok'] = $ok;
    /*
    $result['code'] = $code;
    $result['msg'] = $msg;
    if ($data) {
        $result['data'] = $data;
    }*/
    $key = config('auth.aec_key');
    $iv = config('auth.aec_iv');
    $result = AES::encrypt($key, $iv, json_encode($result));
    return $result;
    //return json_encode($result);
}

/**
 * 返回错误的JSon数据
 */
function output_error($msg = "error", $code = -1)
{
    $result = [];
    $result['ok'] = false;
    $result['error'] = $msg;
    $key = config('auth.aec_key');
    $iv = config('auth.aec_iv');
    $result = AES::encrypt($key, $iv, json_encode($result));
    return $result;
}

/**
 * 返回信息
 * @param bool $ok
 * @param $data
 */
function onResult($ok = true, $data = [], $msg = 'error')
{
    $result = [];
    $result['ok'] = $ok;
    if ($ok) {
        $result['data'] = $data;
    } else {
        $result['error'] = $msg;
    }
    return json_encode($result);
}


