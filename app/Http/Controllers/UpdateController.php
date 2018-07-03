<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/28
 * Time: 20:35
 */
namespace App\Http\Controllers;

use App\Helper\Util\AES;
use App\Models\App;
use App\Models\AppAd;
use App\Models\Sdk;
use App\Models\Update;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpdateController extends Controller
{
    /**
     * @api {GET} /update  更新sdk接口
     * @apiName update
     * @apiGroup
     * @apiVersion 1.0.0
     *
     * @apiParam {String} version
     *
     * @apiSuccessExample Success-Response:
     * {
     *     "ok": true,
     *     "data": {
     *         "version": "1.0.2",
     *         "file_path": "http://www.baidu.com"
     *     }
     * }
     */
    public function index(Request $request)
    {
        $version = $request->input("version");
        $validate = Validator::make($request->all(), [
            "version" => "required",
        ]);
        if ($validate->fails()) {
            return \App\Helper\onResult(false, [], $validate->errors()->first());
        }
        $version = str_replace(".", "", $version);
        $update = Update::where("ver", ">", $version)->first();
        if ($update) {
            $result['version'] = $update->version;
            $result['file_path'] = $update->file_path;
            return \App\Helper\onResult(true, $result);
        } else {
            return \App\Helper\onResult(false, [], "没有可用的更新信息");
        }
    }

    /**
     * 获取更新信息
     * 请求参数：{“packageName”: “包名”, “channel”: “渠道”}
     * {“codeVersion”:”主程序代码版本”, “sdkVersions”: [
     * {“name”: “GDT”, version:”1.21”},{“name”: “Oneway”, version:”2.21”}
     * ]}
     */
    public function getUpdateInfo(Request $request)
    {
        $content = $request->getContent();
        $params = AES::decrypt(config('auth.aec_key'), config('auth.aec_iv'), $content);
        //$params = $content;
        $params = json_decode($params, true);
        if (!is_array($params)) {
            return \App\Helper\output_error("参数错误");
        }
        $validate = Validator::make($params, [
            "packageName" => "required",
            "channel" => "required",
        ]);
        if ($validate->fails()) {
            return \App\Helper\output_error($validate->errors()->first());
        }
        $map['packagename'] = $params['packageName'];
        $map['channel_title'] = $params['channel'];
        $app = App::where($map)->first();
        if (!$app) {
            return \App\Helper\output_error("应用不存在");
        }
        $app_ad = AppAd::where("app_id", "=", $app->id)->get();
        $sdk_ids = [];
        if ($app_ad) {
            foreach ($app_ad as $ad) {
                $sdk_ids[] = $ad->sdk_id;
            }
        }
        //获取主程序更新信息
        $updateModel = new Update();
        $update = $updateModel->where("type", 0)->where("app_id", "=", $app->id)->orderBy("id", "desc")->first();
        $return = [];
        $return['codeVersion'] = $update->version??"";
        $return['sdkVersions'] = [];
        foreach ($sdk_ids as $sdk_id) {
            $update = Update::where("sdk_id", "=", $sdk_id)->orderBy("id", "desc")->first();
            $sdk = Sdk::where("id", "=", $sdk_id)->first();
            if ($sdk && $update) {
                $version = "";
                $version = $update->version??"";
                $return['sdkVersions'][] = ["name" => $sdk->title, "version" => $version];
            }

        }
        return \App\Helper\output_data($return);


    }

    /**
     * 请求参数：{“packageName”: “包名”, “channel”: “渠道”, “version:”: “主程序代码版本”}
     * 更新主程序
     * 返回结果：{
     * “ok”:true, “resPath:”代码文件路径”, “key”: “秘钥（包括文件及resPath路径的加密）”
     * }
     */
    public function updateCode(Request $request)
    {

        $content = $request->getContent();
        $params = AES::decrypt(config('auth.aec_key'), config('auth.aec_iv'), $content);
        //$params = $content;
        $params = json_decode($params, true);

        if (!is_array($params)) {
            return \App\Helper\output_error("参数错误");
        }
        $validate = Validator::make($params, [
            "packageName" => "required",
            "channel" => "required",
        ]);
        if ($validate->fails()) {
            return \App\Helper\output_error($validate->errors()->first());
        }

        $map['packagename'] = $params['packageName'];
        $map['channel_title'] = $params['channel'];
        $app = App::where($map)->first();
        if (!$app) {
            return \App\Helper\output_error("应用不存在");
        }
        //查找更新信息
        //获取主程序更新信息
        $version = $params['version']??"";
        $ver = str_replace(".", "", $version);
        $updateModel = new Update();
        $update = $updateModel->where("type", "=", 0)->where("app_id", "=", $app->id)->where("ver", "=",
            $ver)->orderBy("id", "desc")->first();
        if (!$update) {
            return \App\Helper\output_error("没有更新信息");
        }
        $return = [];
        $return['resPath'] = $update->file_path;
        $return['key'] = $update->key;
        return \App\Helper\output_data($return);

    }

    /**
     * 更新sdk信息
     * 请求参数：{“packageName”: “包名”, “channel”: “渠道”, “sdkName”: “要更新的SDK名称”, “version:”: “SDK版本”}
     * 返回结果：{
     * “ok”:true, “resPath:”SDK文件路径”, “key”: “秘钥（包括文件及resPath路径的加密）”
     */
    public function updateSdk(Request $request)
    {
        $content = $request->getContent();
        $params = AES::decrypt(config('auth.aec_key'), config('auth.aec_iv'), $content);
        //$params = $content;
        $params = json_decode($params, true);

        if (!is_array($params)) {
            return \App\Helper\output_error("参数错误");
        }
        $validate = Validator::make($params, [
            "packageName" => "required",
            "channel" => "required",
        ]);
        if ($validate->fails()) {
            return \App\Helper\output_error($validate->errors()->first());
        }

        $map['packagename'] = $params['packageName'];
        $map['channel_title'] = $params['channel'];
        $app = App::where($map)->first();
        if (!$app) {
            return \App\Helper\output_error("应用不存在");
        }
        $version = $params['version']??"";
        $sdkName = $params['sdkName']??"";
        //$ver = str_replace(".", "", $version);
        $sdk = Sdk::where("title", "=", $sdkName)->first();
        if (!$sdk) {
            return \App\Helper\output_error("sdk is not exist");
        }

        DB::enableQueryLog();
        $update = Update::where("sdk_id", "=", $sdk->id)->where("version", "=", $version)->where('type',1)->first();
        //调试
        if(isset($params['is_debug']) && $params['is_debug']){
            print_r( DB::getQueryLog());

        }
        if (!$update) {
            return \App\Helper\output_error("sdk更新信息不存在");
        }
        $return = [];
        $return['resPath'] = $update->file_path;
        $return['key'] = $update->key;
        return \App\Helper\output_data($return);

    }
}