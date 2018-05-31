<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/28
 * Time: 11:34
 */
namespace App\Http\Controllers;

use App\Helper\Util\IP;
use App\Models\Ad;
use App\Models\Strategy;
use App\Models\StrategyAdList;
use App\Models\StrategyList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdController extends Controller
{
    /**
     * 根据流量策略显示广告数据
     */
    public function index(Request $request)
    {
        $position_id = $request->input("position_id", 1);
        $channel = $request->input("channel");
        $packagename = $request->input("packagename");
        $version = $request->input("version");
        $brand = $request->input("brand");
        $sdk_version = $request->input("sdk_version");
        $country = $request->input("country"); //国家
        $province = $request->input("province");  //省
        $city = $request->input("city");
        $ip = $request->getClientIp(); //获取IP
        $validate = Validator::make($request->all(), [
            "position_id" => "required",

        ]);
        if ($validate->fails()) {
            return \App\Helper\output_error($validate->errors()->first());
        }
        //查找策略广告
        $map['position_id'] = $position_id;
        $map['status'] = 1;
        $strategyModel = new Strategy();
        $strategy = $strategyModel->getList($map, 200);
        if (!$strategy) {
            return \App\Helper\output_error("流量策略不存在");
        }
        $strateIds = [];
        foreach ($strategy as $val) {
            $strateIds[] = $val->id;
        }
        $strategyListModel = new StrategyList();
        $strategy_list = $strategyListModel->whereIn("strategy_id", $strateIds)->where("status", "=", 1)->get();
        if (!$strategy_list) {
            return \App\Helper\output_error("流量策略不存在");
        }
        //判断流量策略
        $strategy_id = 0;  //返回的strate_id
        $return_strategy_id = false;
        foreach ($strategy_list as $val) {
            $type = $val->type;
            $rule = $val->rule;
            $rule_content = $val->rule_content;
            if ($strategy_id == 0) {
                $strategy_id = $val->strategy_id;
            } elseif ($strategy_id != $val->strategy_id) {
                if ($return_strategy_id == true) {
                    break;
                }
                $strategy_id = $val['strategy_id'];
            } elseif ($strategy_id == $val->strategy_id && !$return_strategy_id) {
                continue;
            }
            switch ($type) {
                case 1:
                    //包名
                    $rule_content = explode(",", $rule_content);
                    if ($rule == 0 && in_array($packagename, $rule_content)) {
                        $return_strategy_id = true;
                    } elseif ($rule == 1 && !in_array($packagename, $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        $return_strategy_id = false;
                    }
                    break;
                case 2:
                    //手机版本
                    if (in_array($rule, [0, 1])) {
                        $rule_content = explode(",", $rule_content);
                    }
                    if ($rule == 0 && in_array($version, $rule_content)) {
                        $return_strategy_id = true;
                    } elseif ($rule == 1 && !in_array($version, $rule_content)) {
                        $return_strategy_id = true;
                    } elseif ($rule == 2 && $rule_content <= $version) {
                        $return_strategy_id = true;
                    } elseif ($rule == 3 && $rule_content >= $version) {
                        $return_strategy_id = true;
                    } else {
                        $return_strategy_id = false;
                    }
                    break;
                case 3:
                    //手机品牌
                    $rule_content = explode(",", $rule_content);
                    if ($rule == 0 && in_array($brand, $rule_content)) {
                        $return_strategy_id = true;
                    } elseif ($rule == 1 && !in_array($brand, $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        $return_strategy_id = false;
                    }
                    break;
                case 4:
                    //地区(国家)
                    $location = IP::find($ip);
                    if ($location == 'N/A') {
                        $location[0] = '';
                    }
                    // 有上报时用上报的
                    if (!empty($country)) {
                        $location[0] = $country;
                    }
                    $rule_content = explode(',', $val['rule_content']);
                    if ($rule == 0 && in_array($location[0], $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        if ($rule == 1 && !in_array($location[0], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                    }
                    break;
                case 5:
                    //省
                    // 根据IP获取地理位置
                    $location = IP::find($ip);
                    if ($location == 'N/A') {
                        $location[1] = '';
                    }
                    // 有上报时用上报的
                    if (!empty($province)) {
                        $location[1] = str_replace(array('省', '市'), '', $province);
                    }
                    $rule_content = explode(',', $val['rule_content']);
                    if ($rule == 0 && in_array($location[1], $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        if ($rule == 1 && !in_array($location[1], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                    }
                    break;
                case 6:
                    //市
                    $location = IP::find($ip);
                    if ($location == 'N/A') {
                        $location[2] = '';
                    }
                    // 有上报时用上报的
                    if (!empty($city)) {
                        $location[2] = str_replace(array('省', '市'), '', $city);
                    }
                    $rule_content = explode(',', $val['rule_content']);
                    if ($rule == 0 && in_array($location[2], $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        if ($rule == 1 && !in_array($location[2], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                    }
                    break;
                case 7:
                    //区县
                    // 根据IP获取地理位置
                    $location = IP::find($ip);
                    if ($location == 'N/A') {
                        $location[3] = '';
                    }
                    $rule_content = explode(',', $rule_content);
                    if ($rule == 0 && in_array($location[3], $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        if ($rule == 1 && !in_array($location[3], $rule_content)) {
                            $return_strategy_id = true;
                        } else {
                            $return_strategy_id = false;
                        }
                    }
                    break;
                default:
                    //渠道
                    $rule_content = explode(",", $rule_content);
                    if ($rule == 0 && in_array($channel, $rule_content)) {
                        $return_strategy_id = true;
                    } elseif ($rule == 1 && !in_array($channel, $rule_content)) {
                        $return_strategy_id = true;
                    } else {
                        $return_strategy_id = false;
                    }

            }
        }
        if ($return_strategy_id == false || $strategy_id==0) {
            return \App\Helper\output_error("没有合适的流量策略");
        }

        //获取广告数据
        $sadMap['strategy_id'] = $strategy_id;
        $sadMap['status'] = 1;
        $strategyAdListModel = new StrategyAdList();
        $s_ad_list = $strategyAdListModel->getList($sadMap);
        if (!$s_ad_list) {
            return \App\Helper\output_error("找不到相关策略的广告数据");
        }
        $adIds = [];
        foreach ($s_ad_list as $s_ad) {
            $adIds[] = $s_ad->ad_id;
        }
        $adModel = new Ad();
        $ad_list = $adModel->getAdList($adIds);
        if (!$ad_list) {
            return \App\Helper\output_error("广告数据不存在");
        }
        $return = []; //返回的数据
        foreach($ad_list as $ad){

        }

    }

}