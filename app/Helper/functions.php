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
function output_data($data = [], $code = 200, $msg = 'success')
{
    $result = [];
    $result = $data;
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
    return output_data([], $code, $msg);
}


