<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Menu;
use App\Models\MenuCate;
use App\Models\Shop;
use App\SignatureHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ApiController extends Controller
{
    //获取商家列表
    public function shops()
    {
        $db = Shop::all(['id',
            'shop_category_id',
            'shop_name',
            'shop_img',
            'shop_rating',
            'fengniao',
            'bao',
            'piao',
            'zhun',
            'notice',
            'discount',
            'brand',
            'on_time',
            'start_send',
            'send_cost',
            'status']);
        //dd($db);
        foreach ($db as $b) {
            $b['distance'] = mt_rand(100, 10000);//距离
            $b['estimate_time'] = mt_rand(10, 60);//预计送达时间
        }
        return json_encode($db);
    }

    //获取指定商家
    public function shop(Request $request)
    {
        $id = $request->id;
        $shop = Shop::select([
            "id",
            "shop_name",
            "shop_img",
            "shop_rating",
            "brand",
            "on_time",
            "fengniao",
            "bao",
            "piao",
            "zhun",
            "start_send",
            "send_cost",
            "notice",
            "discount"])->where('id', '=', "{$id}")->first();

        $shop['distance'] = mt_rand(100, 10000);//距离
        $shop['estimate_time'] = mt_rand(10, 60);//预计送达时间
        $shop['service_code'] = mt_rand(4, 10);//服务总评分
        $shop['foods_code'] = mt_rand(4, 10);//食物总评分
        $shop['high_or_low'] = true;//低于还是高于周边商家
        $shop['h_l_percent'] = mt_rand(20, 80);//低于还是高于周边商家的百分比
        $shop['evaluate'] = [
            "user_id" => 12344,
            "username" => "w******k",
            "user_img" => "http://www.homework.com/images/slider-pic4.jpeg",
            "time" => "2017-2-22",
            "evaluate_code" => 1,
            "send_time" => 30,
            "evaluate_details" => "不怎么好吃",
        ];

        $menucates = MenuCate::select(
            ["description",
            "is_selected",
            'id',
            "name",
            "type_accumulation"]
        )->where('shop_id', "{$id}")->get();

        foreach ($menucates as &$menucate) {

            $menus = Menu::select([
                "id",
                "goods_name",
                "rating",
                "goods_price",
                "description",
                "month_sales",
                "rating_count",
                "tips",
                "satisfy_count",
                "satisfy_rate",
                "goods_img",
            ])->where('category_id', "{$menucate->id}")->get();

            foreach ($menus as &$menu){
                $menu['goods_id']=$menu['id'];
                unset($menu['id']);
            }
            $menucate['goods_list']=$menus;
        }

        $shop['commodity'] = $menucates;

        //dd($menucates);
        return json_encode($shop);
    }

    //短信验证
    public function sms()
    {
        $tel=request()->tel;
        $params = [];

        // *** 需用户填写部分 ***

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAIouqSpVF2lGsj";
        $accessKeySecret = "B7V2T8g1akU7ciJqLeTzE2l20EiiQJ";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $tel;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "褚国均";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_140555045";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        //$code=2222;
        $code=random_int(1000,9999);
        $params['TemplateParam'] = Array (
            "code" =>$code
            //"product" => "阿里通信"
        );
        Redis::set('sms',$code);
        Redis::expire('sms',300);
        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            ))
        // fixme 选填: 启用https
        // ,true
        );

        return json_encode($content);
    }

    //用户注册
    public function regist(Request $request){



        $redis=Redis::get('sms');
        $sms=$request->sms;
        if ($redis!=$sms){
            return json_encode([
                'ststus'=>'false',
                'message'=>'验证码错误',
            ]);
        }
        $password=bcrypt($request->password);
        Member::create([
            'username'=>$request->username,
            'tel'=>$request->tel,
            'password'=>$password,
        ]);
        return json_encode([
            'status'=>'true',
            'message'=>'注册成功'
        ]);
    }

    public function loginCheck(Request $request)
    {

        //$id=Member::where('name','=',"{$request->name}")->select('id');
        if (Auth::attempt([
            'username'=>$request->name,
            'password'=>$request->password,
        ])){
            return json_encode([
              'status'=>'true',
              'message'=>'登录成功',
                'id'=>Auth::user()->id,
                'username'=>"{$request->name}",
            ]);
        }else{
            return json_encode([
                'status'=>'false',
                'message'=>'登录失败',
            ]);
        }
    }

}
