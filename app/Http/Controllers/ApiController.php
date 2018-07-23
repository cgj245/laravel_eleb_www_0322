<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuCate;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
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
}
