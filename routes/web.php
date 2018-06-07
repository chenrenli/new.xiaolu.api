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
    return view('welcome');
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