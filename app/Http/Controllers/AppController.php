<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/30
 * Time: 12:12
 */
namespace App\Http\Controllers;

use App\Helper\Util\AES;
use App\Helper\Util\IP;
use App\Models\Ad;
use App\Models\App;
use App\Models\AppAd;
use App\Models\Position;
use App\Models\Sdk;
use App\Models\Strategy;
use App\Models\StrategyAdList;
use App\Models\StrategyRule;
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
        $packagename = $params['packageName'];  //游戏包名
        $channel = $params['channel'];   //渠道名称

        $map['packagename'] = $packagename;
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

        //将广告数据对应起来  (默认的sdk对应的广告数据)
        $result = [];
        foreach ($app_ad as $key => $ad) {
            $position = Position::find($ad->position_id);
            $p_name = $position->name;
            $data = [];
            $data['sdkName'] = $ad->sdk_title;
            $data['appId'] = $ad->appid;
            $data['positionId'] = $ad->adid;
            $data['adPackageName'] = $ad->adpackagename;  //第三方广告包名
            $result[$p_name] = $data;
        }
        //根据策略筛选广告
        //查找策略
        $version = $params['version']??'';    //版本号
        $brand = $params['brand']??''; //手机品牌
        $operator = $params['operator']??''; //运营商
        $net = $params['net']??''; //网络
        $map['ip'] = $request->getClientIp();
        $map['version'] = $version;
        $map['brand'] = $brand;
        $map['operator'] = $operator;
        $map['net'] = $net;
        $map['channel'] = $channel;
        $map['sdkVersion'] = $params['sdkVersion']??""; //sdk版本

        $return = $this->getStrategy($map);
        $res = json_decode($return, true);

        if ($res['ok'] == false) {
            //策略不命中的话显示默认的sdk广告
            return \App\Helper\output_data($result);
        } else {
            //替换成策略的广告
            $strategy_ad_ids = $res['data']['ad_ids'];
            $adModel = new Ad();
            $ad_list = $adModel->whereIn("id", $strategy_ad_ids)->get();
            $result = [];
            if (!$ad_list) {
                return \App\Helper\output_error("广告数据不存在");
            }
            foreach ($ad_list as $ad) {
                $sdk = Sdk::where("id", "=", $ad->sdk_id)->first();
                $position = Position::find($ad->position_id);
                $p_name = $position->name;
                $data = [];
                $data['sdkName'] = $sdk->title;
                $data['appId'] = $ad->appid;
                $data['positionId'] = $ad->adid;
                $data['adPackageName'] = $ad->packagename;  //广告包名
                $result[$p_name] = $data;
            }
            return \App\Helper\output_data($result);

        }


    }

    private function getStrategy($map)
    {

        $version = $map['version'];
        $packagename = $map['packagename'];
        $brand = $map['brand'];
        $net = $map['net'];
        $channel = $map['channel'];
        $ip = $map['ip'];
        $operator = $map['operator'];

        $strategyModel = new Strategy();
        $strategy_list = $strategyModel->getList(['status' => 1]);
        if (!$strategy_list) {
            return \App\Helper\onResult(false, [], "策略不存在");
        }
        $strategy_ids = [];
        foreach ($strategy_list as $strategy) {
            $strategy_ids[] = $strategy->id;
        }
        $strategyRuleModel = new StrategyRule();
        $rule_list = $strategyRuleModel->getList($strategy_ids);
        $strategy_id = 0;
        $return_strategy_id = false;
        if ($rule_list) {
            foreach ($rule_list as $val) {
                //规则
                $type = $val->type;
                $rule = $val->rule;
                $rule_content = $val->rule_content;
                if ($strategy_id == 0) {
                    $strategy_id = $val->strategy_id;
                } elseif ($strategy_id != $val->strategy_id) {
                    if ($return_strategy_id == true) {
                        break;
                    }
                    $strategy_id = $val->strategy_id;
                } elseif ($strategy_id == $val->strategy_id && !$return_strategy_id) {
                    continue;
                }

                switch ($type) {
                    case 1:
                        //手机版本
                        if (in_array($rule, [1, 2])) {
                            $rule_content = explode(",", $rule_content);
                        }
                        if ($rule == 2 && in_array($version, $rule_content)) {
                            //包含
                            $return_strategy_id = true;
                        } elseif ($rule == 1 && !in_array($version, $rule_content)) {
                            //不包含
                            $return_strategy_id = true;
                        } elseif ($rule == 3 && $rule_content <= $version) {
                            //小于等于
                            $return_strategy_id = true;
                        } elseif ($rule == 4 && $rule_content >= $version) {
                            //大于等于
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                        break;
                    case 2:
                        //包名
                        $rule_content = explode(",", $rule_content);
                        if ($rule == 2 && in_array($packagename, $rule_content)) {
                            //包含
                            $return_strategy_id = true;
                        } elseif ($rule == 1 && !in_array($packagename, $rule_content)) {
                            //不包含
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                        echo "package----".$return_strategy_id."aaa\r\n";
                        break;
                    case 3:
                        //手机品牌
                        $rule_content = explode(",", $rule_content);
                        if ($rule == 2 && in_array($brand, $rule_content)) {
                            //包含
                            $return_strategy_id = true;
                        } elseif ($rule == 1 && !in_array($brand, $rule_content)) {
                            //不包含
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                        break;
                    case 4:
                        //渠道
                        $rule_content = explode(",", $rule_content);
                        if ($rule == 2 && in_array($channel, $rule_content)) {
                            //包含
                            $return_strategy_id = true;
                        } elseif ($rule == 1 && !in_array($channel, $rule_content)) {
                            //不包含
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                        break;
                    case 5:
                        //运营商
                        $rule_content = explode(",", $rule_content);
                        if ($rule == 2 && in_array($operator, $rule_content)) {
                            //包含
                            $return_strategy_id = true;
                        } elseif ($rule == 1 && !in_array($operator, $rule_content)) {
                            //不包含
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                        break;
                    case 6:
                        //网络
                        $rule_content = explode(",", $rule_content);
                        if ($rule == 2 && in_array($net, $rule_content)) {
                            //包含
                            $return_strategy_id = true;
                        } elseif ($rule == 1 && !in_array($net, $rule_content)) {
                            //不包含
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }

                        break;
                    case 7:
                        //日期
                        $rule_content = explode(",", $rule_content);
                        $begin_time = str_replace("-", "", $rule_content[0]);
                        $begin_time_hour = $rule_content[1];
                        $end_time = str_replace("-", "", $rule_content[2]);
                        $end_time_hour = $rule_content[3];
                        $h = intval(date("H"));
                        $date = date("Ymd");
                        if ($date >= $begin_time && $date <= $end_time && $h >= $begin_time_hour && $h <= $end_time_hour) {
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                        break;

                    case 8:
                        //地区(国家)
                        // 根据IP获取地理位置
                        $location = IP::find($ip);
                        if ($location == 'N/A') {
                            $location[0] = '';
                        }
                        $rule_content = explode(',', $rule_content);
                        if ($rule == 2 && in_array($location[0], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            if ($rule == 1 && !in_array($location[0], $rule_content)) {
                                $return_strategy_id = true;
                            } else {
                                $return_strategy_id = false;
                            }
                        }
                        break;
                    case 9:
                        //地区(省)
                        // 根据IP获取地理位置
                        $location = IP::find($ip);
                        if ($location == 'N/A') {
                            $location[1] = '';
                        }
                        $rule_content = explode(',', $rule_content);
                        if ($rule == 2 && in_array($location[1], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            if ($rule == 1 && !in_array($location[1], $rule_content)) {
                                $return_strategy_id = true;
                            } else {
                                $return_strategy_id = false;
                            }
                        }
                        break;
                    case 10:
                        //地区(城市)
                        $location = IP::find($ip);
                        if ($location == 'N/A') {
                            $location[2] = '';
                        }
                        $rule_content = explode(',', $rule_content);
                        if ($rule == 2 && in_array($location[2], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            if ($rule == 1 && !in_array($location[2], $rule_content)) {
                                $return_strategy_id = true;
                            } else {
                                $return_strategy_id = false;
                            }
                        }
                        break;

                }
            }
        }

        if ($return_strategy_id == false || $strategy_id == 0) {
            return \App\Helper\onResult(false, [], "没有合适的流量策略");
        }
        //获取广告数据
        $sadMap['strategy_id'] = $strategy_id;
        $sadMap['status'] = 1;
        $strategyAdListModel = new StrategyAdList();
        $s_ad_list = $strategyAdListModel->getList($sadMap);
        if (empty($s_ad_list)) {
            return \App\Helper\onResult(false, [], "找不到相关策略的广告数据");
        }
        $adids = [];
        foreach ($s_ad_list as $s_ad) {
            $adids[] = $s_ad->ad_id;
        }
        if (count($adids) == 0) {
            return \App\Helper\onResult(false, [], "找不到相关策略的广告数据");
        }
        $result = [];
        $result['ad_ids'] = $adids;
        return \App\Helper\onResult(true, $result);


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