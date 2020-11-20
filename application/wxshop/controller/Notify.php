<?php


namespace app\weixin\controller;

use app\api\wxpay\WxPayApi;
use app\api\wxpay\WxPayNotify;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class Notify
{


    // 微信 支付 回调 地址
    public function index(){

        $notify_data = file_get_contents("php://input");//获取由微信传来的数据
        if(!$notify_data){
            $notify_data = $GLOBALS['HTTP_RAW_POST_DATA'] ?: '';//以防上面函数获取到的内容为空
        }
        if(!$notify_data){
            exit('');
        }
        $doc = new \DOMDocument();
        $doc->loadXML($notify_data);
        $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
        $appid = $doc->getElementsByTagName("appid")->item(0)->nodeValue;

            $order = db('goods_order')->where('sn', $out_trade_no)->find();
            if($order == false){
//                $msg = "订单查询失败";
                exit(false);
            }

//            if($appid == 'wxec11bc65af80e9bf'){
//                exit(false);
//            }
        $dd=[
            'state'=>1,
            'pay_time'=>time()
        ];
       $ord =  db('goods_order')->where('id',$order['id'])->update($dd);
       if($ord == false){
           exit(false);
       }
        exit(true);
        }

}