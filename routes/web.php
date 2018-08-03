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

Route::prefix('api')->group(function () {
    //获取商家信息
    Route::get('shops', 'ApiController@shops')->name('shops');
    //获取指定商家信息
    Route::get('shop', 'ApiController@shop')->name('shop');
    //注册
    Route::post('regist', 'ApiController@regist')->name('regist');
    //短信验证
    Route::get('sms', 'ApiController@sms')->name('sms');
    //登录
    Route::post('loginCheck', 'ApiController@loginCheck')->name('loginCheck');
    //地址列表
    Route::get("addressList", "ApiController@addressList")->name('addressList');
    //添加保存地址
    Route::post('addAddress','ApiController@addAddress')->name('addAddress');
    //指定地址接口
    Route::get('address','ApiController@address')->name('address');
    // 保存修改地址接口
    Route::post('editAddress','ApiController@editAddress')->name('editAddress');
    //获取购物车数据接口
    Route::get('cart','ApiController@cart')->name('cart');
    //保存购物车接口
    Route::post('addCart','ApiController@addCart')->name('addCart');
    //添加订单接口
    Route::post('addOrder','ApiController@addOrder')->name('addOrder');
    //获得指定订单接口
    Route::get('order','ApiController@order')->name('order');
    //获得订单列表接口
    Route::get('orderList','ApiController@orderList')->name('orderList');
    //忘记密码接口
    Route::get('changePassword','ApiController@changePassword')->name('changePassword');
    //忘记密码接口
    Route::post('forgetPassword','ApiController@forgetPassword')->name('forgetPassword');
    // 支付接口
    Route::post('pay','ApiController@pay')->name('pay');
});
