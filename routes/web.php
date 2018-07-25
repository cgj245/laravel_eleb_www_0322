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

Route::prefix('api')->group(function (){
    //获取商家信息
    Route::get('shops','ApiController@shops')->name('shops');
    //获取指定商家信息
    Route::get('shop','ApiController@shop')->name('shop');

    //注册
    Route::post('regist','ApiController@regist')->name('regist');
    //短信验证
    Route::get('sms','ApiController@sms')->name('sms');
    //登录
    Route::post('loginCheck','ApiController@loginCheck')->name('loginCheck');

});
