<?php
/*
	结算控制器
*/
namespace app\index\controller;
use app\common\model\PayModel;

use app\wxshop\wxpay\WxPayMicroPay;
use think\Request;
use think\Db;
class Pay
{
    /***
     * 支付宝支付
     */
    public function AliPay( $auth_code, $outTradeNo,$title,$price){
        $PayModel = new PayModel();
        if( !$auth_code ){
            echo '没得支付码';die;
            return ['code'=>100,'msg'=>'请传入付款码'];
        }
        $res = $PayModel ->AliCodePay($auth_code,$outTradeNo,$title,$price);
        return json($res);
    }

    /***
     * weixin
     */
    public function wxpay(){
        try {
            // 支付授权码
            $auth_code = input('auth_code');
            if( !$auth_code ){
                echo '缺少付款码';die;
            }
            $input = new WxPayMicroPay();
            $input->SetAuth_code($auth_code);
            $input->SetBody('门店');
            $input->SetTotal_fee(1);//订单金额  订单单位 分
            $input->SetOut_trade_no(time().time());
            $PayModel = new PayModel();
            $resPay = $PayModel ->pay($input);
            if( $resPay == false ){
                echo 1;
            }else{
                echo 2;
            }
            die;
            dump($resPay);
            dump(1);
            die;
        } catch(Exception $e) {
            dump(12312);die;
//            dump($e->getMessage());die;
        }
//        $auth_code = input('auth_code');
//        if( !$auth_code ){
//            echo '缺少付款码';die;
//        }
//        $input = new WxPayMicroPay();
//        $input->SetAuth_code($auth_code);
//        $input->SetBody('门店');
//        $input->SetTotal_fee(1);//订单金额  订单单位 分
//        $input->SetOut_trade_no(time().time());
//        $PayModel = new PayModel();
//        $resPay = $PayModel ->pay($input);
////        dump($resPay);
        echo 123;
    }
}
