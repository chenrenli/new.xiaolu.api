<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

//    $ip = "113.108.182.52";
//    // 根据IP获取地理位置
//    $location = \App\Helper\Util\IP::find($ip);
//
//    echo "<pre>";
//    print_r($location);


});

Route::get("/ad", "AdController@index");

//获取广告数据
Route::get("/fetchAd", "AppController@fetchAd");
Route::post("/fetchAd", "AppController@fetchAd");

//获取sdk列表
Route::get("/getSdkList", "AppController@getSdkList");
Route::post("/getSdkList", "AppController@getSdkList");

//更新接口
Route::get("/update", "UpdateController@index");
Route::post("/update", "UpdateController@index");

//获取更新版本信息
Route::post("/getUpdateInfo", "UpdateController@getUpdateInfo");
//更新主程序
Route::post("/updateCode", "UpdateController@updateCode");
//更新sdk
Route::post("/updateSdk", "UpdateController@updateSdk");