<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/30
 * Time: 12:12
 */
namespace App\Http\Controllers;

use App\Helper\Util\AES;
use App\Models\App;
use App\Models\AppAd;
use App\Models\Position;
use App\Models\Sdk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppController extends Controller
{

    /**
     * @api {POST} /fetchAd  获取广告
     * @apiName fetchAd
     * @apiGroup
     * @apiVersion 1.0.0
     *
     *
     * @apiSuccessExample Success-Response:
     * {
     *     "code": 200,
     *     "msg": "success",
     *     "data": [
     *         {
     *             "video": {
     *                 "sdkName": "test",
     *                 "appId": "test444",
     *                 "positionId": "tst123456"
     *             }
     *         },
     *         {
     *             "interstitial": {
     *                 "sdkName": "test333",
     *                 "appId": "test5555",
     *                 "positionId": "tst123456"
     *             }
     *         }
     *     ]
     * }
     */
    public function fetchAd(Request $request)
    {
//        $str = '{"packageName":"com.titan.stub","channel":"xiaomi"}';
//        echo $str1 = AES::encrypt(config('auth.aec_key'), config('auth.aec_iv'),$str);

        $content = $request->getContent();
        $params = AES::decrypt(config('auth.aec_key'), config('auth.aec_iv'), $content);
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
        $packagename = $params['packageName'];
        $channel = $params['channel'];
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
        $result['ok'] = true;
        foreach ($app_ad as $key => $ad) {
            $position = Position::find($ad->position_id);
            $p_name = $position->name;
            $data = [];
            $data['sdkName'] = $ad->sdk_title;
            $data['appId'] = $ad->appid;
            $data['positionId'] = $ad->adid;
            $result[$p_name] = $data;
        }
        return \App\Helper\output_data($result);

    }

    /**
     * 获取sdk列表
     */
    public function getSdkList(Request $request)
    {
        $sdkModel = new Sdk();
        $sdk_list = $sdkModel->where([])->get();
        $result = [];
        if ($sdk_list) {
            foreach ($sdk_list as $key => $sdk) {
                $result[$key]['_id'] = $sdk->id;
                $result[$key]['name'] = $sdk->title;
                $result[$key]['filePath'] = $sdk->start_path;
                $result[$key]['className'] = $sdk->start_class;
            }
        }
        return \App\Helper\output_data($result);
    }
}