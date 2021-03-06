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

// 测试api调用的路由
Route::post('/getRequest', 'Api\BaiduPicRecognizeController@getRequest');
// 获取识别种类的名称及描述
Route::get('/getCategoryNameDesc', 'Api\BaiduPicRecognizeController@getCategoryNameDesc');
