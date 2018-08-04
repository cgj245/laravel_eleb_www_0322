<?php

namespace App\Http\Controllers;

use App\Models\Addre;
use App\Models\Cart;
use App\Models\Member;
use App\Models\Menu;
use App\Models\MenuCate;
use App\Models\Order;
use App\Models\OrderGood;
use App\Models\Shop;
use App\Models\Shop_user;
use App\SignatureHelper;
use function foo\func;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;


class ApiController extends Controller
{
    //获取商家列表
    public function shops(Request $request)
    {

        //dd($db);
        if (!empty($request->keyword)) {
            $db = Shop::where('shop_name', 'like', "%{$request->keyword}%")->get();
        } else {
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
        }

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

            foreach ($menus as &$menu) {
                $menu['goods_id'] = $menu['id'];
                unset($menu['id']);
            }
            $menucate['goods_list'] = $menus;
        }

        $shop['commodity'] = $menucates;

        //dd($menucates);
        return json_encode($shop);
    }

    //短信验证
    public function sms()
    {
        $tel = request()->tel;
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
        $code = random_int(1000, 9999);
        $params['TemplateParam'] = Array(
            "code" => $code
            //"product" => "阿里通信"
        );
        Redis::set('sms' . $tel, $code);
        Redis::expire('sms' . $tel, 300);
        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
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
    public function regist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:members',
            'tel' => 'required|unique:members',
            'password' => 'required',
        ], [
            'username.required' => '用户名不能为空',
            'username.unique' => '用户名已存在',
            'tel.required' => '电话不能为空',
            'password.required' => '密码不能为空',
            'tel.unique' => '电话已存在',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }

        $tel = $request->tel;
        $redis = Redis::get('sms' . $tel);
        $sms = $request->sms;
        if ($redis != $sms) {
            return json_encode([
                'status' => 'false',
                'message' => '验证码错误',
            ]);
        }
        $password = bcrypt($request->password);
        Member::create([
            'username' => $request->username,
            'tel' => $request->tel,
            'password' => $password,
        ]);
        return json_encode([
            'status' => 'true',
            'message' => '注册成功'
        ]);
    }

    //登录
    public function loginCheck(Request $request)
    {
//        $validator=Validator::make($request->all(),[
//            'name'=>'required|unique:member',
//            'password'=>'required'
//        ]);

        //$id=Member::where('name','=',"{$request->name}")->select('id');
        if (Auth::attempt([
            'username' => $request->name,
            'password' => $request->password,
        ])
        ) {
            return json_encode([
                'status' => 'true',
                'message' => '登录成功',
                'id' => Auth::user()->id,
                'username' => "{$request->name}",
            ]);
        } else {
            return json_encode([
                'status' => 'false',
                'message' => '登录失败',
            ]);
        }
    }

    //地址列表
    public function addressList()
    {
        $id=Auth::user()->id;
        $addre = Addre::where('user_id',$id)->
        //makeHidden(['user_id', 'county', 'is_default', 'created_at', 'updated_at'])->
        get();
        foreach ($addre as &$v) {
            $v['area'] = $v['county'];
            $v['detail_address'] = $v['address'];
            $v['provence'] = $v['province'];
            unset($v['county']);
            unset($v['address']);
            unset($v['address']);
            unset($v['province']);
            unset($v['user_id']);
            unset($v['created_at']);
            unset($v['updated_at']);
            unset($v['is_default']);
        }

        return json_encode($addre);
    }

    //添加地址
    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ], [
            'name.required' => '用户名不能为空',
            'tel.required' => '电话不能为空',
            'provence.required' => '省份不能为空',
            'city.required' => '城市不能为空',
            'area.required' => '区不能为空',
            'detail_address.required' => '详细地址不能为空',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        if (!preg_match('/^1[3456789]\d{9}$/', $request->tel)) {
            return [
                'status' => 'false',
                'message' => '电话不合法',
            ];
        }
        $user_id = Auth::user()->id;
        Addre::create([
            'user_id' => $user_id,
            'name' => $request->name,
            'tel' => $request->tel,
            'province' => $request->provence,
            'city' => $request->city,
            'county' => $request->area,
            'address' => $request->detail_address,
            'is_default' => 0,

        ]);

        return json_encode([
            'status' => 'true',
            'message' => '添加成功',
        ]);
//        $data=[];
//        $data['name']=$request->name;
//        $data['tel']=$request->tel;
//        $data['province']=$request->provence;
//        $data['city']=$request->city;
//        $data['county']=$request->area;
//        $data['address']=$request->detail_address;


    }

    //指定地址接口
    public function address(Request $request)
    {

        $res = Addre::where('id', '=', "{$request->id}")->get();
        return json_encode([
            'id' => $res[0]->id,
            'provence' => $res[0]->province,
            'city' => $res[0]->city,
            'area' => $res[0]->county,
            'detail_address' => $res[0]->address,
            'name' => $res[0]->name,
            'tel' => $res[0]->tel,
        ]);
    }

    // 保存修改地址接口
    public function editAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'tel' => 'required',
            'provence' => 'required',
            'city' => 'required',
            'area' => 'required',
            'detail_address' => 'required',
        ], [
            'name.required' => '用户名不能为空',
            'tel.required' => '电话不能为空',
            'provence.required' => '省份不能为空',
            'city.required' => '城市不能为空',
            'area.required' => '区不能为空',
            'detail_address.required' => '详细地址不能为空',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        if (!preg_match('/^1[3456789]\d{9}$/', $request->tel)) {
            return [
                'status' => 'false',
                'message' => '电话不合法',
            ];
        }
        $addre = Addre::find($request->id);
        $addre->update([
            'name' => $request->name,
            'tel' => $request->tel,
            'province' => $request->provence,
            'city' => $request->city,
            'county' => $request->area,
            'address' => $request->detail_address,
        ]);
        return json_encode([
            'status' => 'true',
            'message' => '修改成功',
        ]);
    }

    //保存购物车接口
    public function addCart(Request $request)
    {
        $user_id = Auth::user()->id;
        Cart::where('user_id', '=', "{$user_id}")->delete();
        for ($i = 0; $i < count($request->goodsList); $i++) {
            Cart::create([
                'goods_id' => $request->goodsList[$i],
                'amount' => $request->goodsCount[$i],
                'user_id' => $user_id,
            ]);
        }
        return json_encode([
            'status' => 'true',
            'message' => '添加成功',
        ]);
    }

    //获取购物车数据接口
    public function cart()
    {
        $goods_list = [];
        $f=0;
        $user_id = Auth::user()->id;
        $goods = Cart::where("user_id", '=', "{$user_id}")->get();

        foreach ($goods as $v) {
        $good = Menu::find($v->goods_id);
            $goods_list[]=
                [
                    'goods_id'=>$good->id,
                    'goods_name'=>$good->goods_name,
                    'goods_img'=>$good->goods_img,
                    'amount'=>$v->amount,
                    'goods_price'=>$good->goods_price,
                ];
            $f+=($v->amount)*$good->goods_price;

        }
        return[
            'goods_list'=>$goods_list,
            'totalCost'=>$f

        ];

//        "goods_list": [{
//        "goods_id": "1",
//        "goods_name": "汉堡",
//        "goods_img": "http://www.homework.com/images/slider-pic2.jpeg",
//        "amount": 6,
//        "goods_price": 10
//      },{
//        "goods_id": "1",
//        "goods_name": "汉堡",
//        "goods_img": "http://www.homework.com/images/slider-pic2.jpeg",
//        "amount": 6,
//        "goods_price": 10
//      }],
//     "totalCost": 120
//    }
    }

    //添加订单接口
    public function addOrder(Request $request)
    {
        $user_id=Auth::user()->id;
        $goods_id=Cart::where('user_id',$user_id)->first();
        $shop_id=Menu::where('id',$goods_id->goods_id)->first();
        //dd($shop_id->shop_id);
        $sn=date('Ymd',time()).rand(1000,9999);
        $address_id=$request->address_id;
        $addre=Addre::where('id',$address_id)->first();
        //dd($addre);
        $status=0;
        $created_at=time();
        $out_trade_no=uniqid();

        $goods=Cart::where('user_id',$user_id)->get();
        //dd($goods);
        $total=0;
        $goods_ids=[];
        $amounts=[];
        foreach($goods as $v){
            $goods_id=$v->goods_id;
            $amount=$v->amount;
            $goods_price=Menu::where('id',$goods_id)->first()->goods_price;
            $total+=($amount)*($goods_price);
            $goods_ids[]=$goods_id;
            $amounts[]=$amount;

        }
        DB::beginTransaction();
        try{
            $order=Order::create([
                'user_id'=>$user_id,
                'shop_id'=>$shop_id->shop_id,
                'sn'=>$sn,
                'province'=>$addre->province,
                'city'=>$addre->city,
                'county'=>$addre->county,
                'address'=>$addre->address,
                'tel'=>$addre->tel,
                'name'=>$addre->name,
                'total'=>$total,
                'status'=>$status,
                'create_at'=>$created_at,
                'out_trade_no'=>$out_trade_no,
            ]);
            $order_id=$order->id;
            foreach ($goods_ids as $k=>$goods_id){
                $goods=Menu::where('id',$goods_id)->first();
                OrderGood::create([
                    'order_id'=>$order_id,
                    'goods_id'=>$goods_id,
                    'goods_name'=>$goods->goods_name,
                    'goods_price'=>$goods->goods_price,
                    'goods_img'=>$goods->goods_img,
                    'amount'=>$amounts[$k],
                ]);
            }

                DB::commit();
//发邮件
            $shop_users=Shop_user::where('shop_id',$shop_id)->first();
            //dd($shop_users);
            $_SERVER['email']=$shop_users->email;
            Mail::raw("可爱的老哥:您的商家账号已经审核通过，请前往邮箱查看",function ($message){
                $message->subject("商家审核通过");
                $message->to('cgj245zijizou@163.com');
                //$message->to( $_SERVER['email']);
                $message->from('cgj245zijizou@163.com','cgj245zijizou');});
//发短信
            //$tel =Member::where('id',$user_id)->select('tel')->first();
            $tel=16605925195;
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
            $params["TemplateCode"] = "SMS_141270029";

            // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项

            $params['TemplateParam'] = Array(
                "name" => "123"
                //"product" => "阿里通信"
            );
//        Redis::set('sms' . $tel, $code);
//        Redis::expire('sms' . $tel, 300);
            // fixme 可选: 设置发送短信流水号
            $params['OutId'] = "12345";

            // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
            $params['SmsUpExtendCode'] = "1234567";


            // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
            if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
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


            return json_encode([
                "status"=> "true",
                "message"=> "添加成功",
                "order_id"=>"{$order_id}"
            ]);
            }catch (\Exception $e){
            DB::rollback();
            return json_encode([
                "status"=> "false",
                "message"=> "添加失败",
            ]);
        }


    }

    //获得指定订单接口
    public function order(Request $request)
    {
        $order_id=$request->id;
        $shop_id=Order::where('id',$order_id)->first()->shop_id;

        $shops=Shop::where('id',$shop_id)->first();
        $shop_name=$shops->shop_name;
        $shop_img=$shops->shop_img;

        $orders=Order::where('id',$order_id)->first();
        $order_code=$orders->sn;
        $order_birth_time=date('Y-m-d H:i',$orders->create_at);
        $order_status=$orders->status;
        $order_price=$orders->total;
        $order_address=$orders->pronince.$orders->city.$orders->county.$orders->address;
        
        $goods=OrderGood::where('order_id',$order_id)->get();
        $goods_list=[];
        foreach ($goods as $good) {

            $goods_list[]=[
                'goods_id'=>$good->goods_id,
                'goods_name'=>$good->goods_name,
                'goods_img'=>$good->goods_img,
                'goods_price'=>$good->goods_price,
                'amount'=>$good->amount,
            ];
        }
        $data=[
            "id"=>$order_id,
        "order_code"=> $order_code,
        "order_birth_time"=> $order_birth_time,
        "order_status"=> $order_status,
        "shop_id"=> $shop_id,
        "shop_name"=> $shop_name,
        "shop_img"=> $shop_img,
        "goods_list"=> $goods_list,
        "order_price"=> $order_price,
        "order_address"=> $order_address
        ];
        return json_encode($data);
    }
    
    //获得订单列表接口
    public function orderList()
    {
        $user_id=Auth::user()->id;
        $orders=Order::where('id',$user_id)->get();
        $data=[];
        foreach ($orders as $order){
            $order_id=$order->id;
            $shop_id=Order::where('id',$order_id)->first()->shop_id;

            $shops=Shop::where('id',$shop_id)->first();
            $shop_name=$shops->shop_name;
            $shop_img=$shops->shop_img;


            $order_code=$order->sn;
            $order_birth_time=date('Y-m-d H:i',$order->create_at);
            $order_status=$order->status;
            $order_price=$order->total;
            $order_address=$order->pronince.$order->city.$order->county.$order->address;
            //$goods=OrderGood::where('order_id',$order_id)->first();
            $goods=OrderGood::where('order_id',$order_id)->get();
            $goods_list=[];
            //dd($goods);
            foreach ($goods as $good) {
                //dd($good);
                $goods_list[]=[
                    'goods_id'=>$good->goods_id,
                    'goods_name'=>$good->goods_name,
                    'goods_img'=>$good->goods_img,
                    'goods_price'=>$good->goods_price,
                    'amount'=>$good->amount,
                ];
            }
            $data[]=[
                "id"=>$order_id,
                "order_code"=> $order_code,
                "order_birth_time"=> $order_birth_time,
                "order_status"=> $order_status,
                "shop_id"=> $shop_id,
                "shop_name"=> $shop_name,
                "shop_img"=> $shop_img,
                "goods_list"=> $goods_list,
                "order_price"=> $order_price,
                "order_address"=> $order_address
            ];

        }

        return json_encode($data);
    }

    //忘记密码接口
    public function changePassword(Request $request)
    {
        $oldPassword=$request->oldPassword;
        $newPassword=bcrypt($request->newPassword);
        $user_id=Auth::user()->id;
        $dbPassword=Member::where('id',$user_id)->first()->password;

        if(!Hash::check($oldPassword,$dbPassword)){
            return json_encode([
                "status"=> "false",
      "message"=> "旧密码错误"
            ]);
        }
//        $member::update([
//            'newPassword'=>$newPassword
//        ]);
        DB::table('members')->where('id',$user_id)
            ->update(['password'=>$newPassword]);
        return json_encode([
            "status"=> "true",
            "message"=> "修改成功"
        ]);

    }

    //重置密码接口
    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tel' => 'required|unique:members',
            'password' => 'required',
        ], [
            'tel.required' => '电话不能为空',
            'password.required' => '密码不能为空',
            'tel.unique' => '电话已存在',
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => 'false',
                'message' => $validator->errors()->first(),
            ]);
        }
        $sms = $request->sms;
        $tel = $request->tel;
        $password=bcrypt($request->password);

        $dbtel=Member::where('tel',$tel)->first();
        if($dbtel==null){
          return json_encode([
              'status' => 'false',
              'message' => '电话号码不存在',
          ])  ;
        }
        $redis = Redis::get('sms' . $tel);

        if ($redis != $sms) {
            return json_encode([
                'ststus' => 'false',
                'message' => '验证码错误',
            ]);
        }

        DB::table('members')->where('tel',$tel)
            ->update(['password'=>$password]);

        return json_encode([
            'status' => 'true',
            'message' => '重置密码成功'
        ]);
}



}

