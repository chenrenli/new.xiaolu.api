<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/30
 * Time: 12:12
 */
namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppAd;
use App\Models\Sdk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppController extends Controller
{
    /**
     * @api {GET} /app/getAd    获取应用广告
     * @apiName getAd
     * @apiGroup app
     * @apiVersion 1.0.0
     *
     * @apiParam {String} packagename   包名
     * @apiParam {String} channel       渠道名称
     *
     * @apiSuccessExample Success-Response:
     * {
     *     "code": 200,
     *     "msg": "success",
     *     "data": {
     *         "packagename": "com.test",
     *         "channel_id": 2,
     *         "channel_title": "xiaomi",
     *         "sdk_list": [
     *             {
     *                 "appid": "test444",
     *                 "adid": 4,
     *                 "sdk_id": 1,
     *                 "sdk_title": "test",
     *                 "sdk_start_path": "test",
     *                 "sdk_start_class": "test"
     *             },
     *             {
     *                 "appid": "test5555",
     *                 "adid": 5,
     *                 "sdk_id": 2,
     *                 "sdk_title": "test333",
     *                 "sdk_start_path": "test",
     *                 "sdk_start_class": "test"
     *             }
     *         ]
     *     }
     * }
     */
    public function fetchAd(Request $request)
    {

        $packagename = $request->input("packagename");
        $channel = $request->input("channel");
        $validate = Validator::make($request->all(), [
            "packagename" => "required",
            "channel" => "required",
        ]);
        if ($validate->fails()) {
            return \App\Helper\output_error($validate->errors()->first());
        }
        $map['packagename'] = $packagename;
        $map['channel_title'] = $channel;
        $appModel = new App();
        $app = $appModel->where($map)->first();
        if (!$app) {
            return \App\Helper\output_error("找不到相应的应用");
        }
        $appAdModel = new AppAd();
        $app_ad = $appAdModel->where("app_id", "=", $app->id)->get();
        if (!$app_ad) {
            return \App\Helper\output_error("应用没有设置sdk");
        }
        $sdkIds = [];
        foreach ($app_ad as $ad) {
            $sdkIds [] = $ad->sdk_id;
        }
        $sdk_list = Sdk::whereIn("id", $sdkIds)->get();
        $sdks = [];
        foreach ($sdk_list as $sdk) {
            $sdks[$sdk->id] = $sdk;
        }
        //将广告数据对应起来
        $result = [];
        $result['packagename'] = $packagename;
        $result['channel_id'] = $app->channel_id;
        $result['channel_title'] = $app->channel_title;
        foreach ($app_ad as $ad) {
            $data = [];
            $data['appid'] = $ad->appid;
            $data['adid'] = $ad->id;
            $data['sdk_id'] = $ad->sdk_id;
            $data['sdk_title'] = $sdks[$ad->sdk_id]->title;
            $data['sdk_start_path'] = $sdks[$ad->sdk_id]->start_path;
            $data['sdk_start_class'] = $sdks[$ad->sdk_id]->start_class;
            $result['sdk_list'][] = $data;
        }

        return \App\Helper\output_data($result);

    }

    /**
     * 获取sdk列表
     */
    public function getSdkList(Request $request){
        $sdkModel = new Sdk();
        $sdk_list = $sdkModel->select();
        $result = [];
        if($sdk_list){
            foreach($sdk_list as $sdk){

            }
        }
    }
}