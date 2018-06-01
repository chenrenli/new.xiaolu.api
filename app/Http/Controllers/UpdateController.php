<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/28
 * Time: 20:35
 */
namespace App\Http\Controllers;

use App\Models\Update;
use Illuminate\Http\Request;
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
}