<?php


namespace app\mall\controller;

use app\wxshop\wxpay\WxPayApi;
use app\wxshop\wxpay\WxPayConfig;
use app\wxshop\wxpay\WxPayException;
use app\wxshop\wxpay\WxPayMicroPay;
use app\wxshop\wxpay\WxPayOrderQuery;
use http\Exception;
use think\facade\Log;

/**
 * 测试 微信支付
 * Class TestWx
 * @package app\mall\controller
 */
class Testwx
{

    public function index(){

        try {
            // 支付授权码
            $auth_code = input('auth_code');
            $input = new WxPayMicroPay();
            $input->SetAuth_code($auth_code);
            $input->SetBody("刷卡测试样例-支付");
            $input->SetTotal_fee("1");//订单金额  订单单位 分
            $input->SetOut_trade_no("sdkphp".date("YmdHis"));
            dump($this->pay($input));
        } catch(Exception $e) {
            dump($e->getMessage());
        }
    }
    /**
     *
     * 提交刷卡支付，并且确认结果，接口比较慢
     * @param WxPayMicroPay $microPayInput
     * @throws WxpayException
     * @return 返回查询接口的结果
     */
    public function pay($microPayInput)
    {

        //①、提交被扫支付
        $config = new WxPayConfig();

        $result = WxPayApi::micropay($config, $microPayInput, 5);
        //如果返回成功
        if(!array_key_exists("return_code", $result)
            || !array_key_exists("result_code", $result))
        {
            echo "接口调用失败,请确认是否输入是否有误！";
            throw new WxPayException("接口调用失败！");
        }

        //取订单号
        $out_trade_no = $microPayInput->GetOut_trade_no();

        //②、接口调用成功，明确返回调用失败
        if($result["return_code"] == "SUCCESS" &&
            $result["result_code"] == "FAIL" &&
            $result["err_code"] != "USERPAYING" &&
            $result["err_code"] != "SYSTEMERROR")
        {
            return false;
        }

        //③、确认支付是否成功
        $queryTimes = 10;
        while($queryTimes > 0)
        {
            $succResult = 0;
            $queryResult = $this->query($out_trade_no, $succResult);
            //如果需要等待1s后继续
            if($succResult == 2){
                sleep(2);
                continue;
            } else if($succResult == 1){//查询成功
                return $queryResult;
            } else {//订单交易失败
                break;
            }
        }

        //④、次确认失败，则撤销订单
        if(!$this->cancel($out_trade_no))
        {
            throw new WxpayException("撤销单失败！");
        }

        return false;
    }

    /**
     *
     * 查询订单情况
     * @param string $out_trade_no  商户订单号
     * @param int $succCode         查询订单结果
     * @return 0 订单不成功，1表示订单成功，2表示继续等待
     */
    public function query($out_trade_no, &$succCode)
    {
        $queryOrderInput = new WxPayOrderQuery();
        $queryOrderInput->SetOut_trade_no($out_trade_no);
        $config = new WxPayConfig();
        try{
            $result = WxPayApi::orderQuery($config, $queryOrderInput);
        } catch(\Exception $e) {
            \Log::ERROR(json_encode($e));
        }
        if($result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            //支付成功
            if($result["trade_state"] == "SUCCESS"){
                $succCode = 1;
                return $result;
            }
            //用户支付中
            else if($result["trade_state"] == "USERPAYING"){
                $succCode = 2;
                return false;
            }
        }

        //如果返回错误码为“此交易订单号不存在”则直接认定失败
        if($result["err_code"] == "ORDERNOTEXIST")
        {
            $succCode = 0;
        } else{
            //如果是系统错误，则后续继续
            $succCode = 2;
        }
        return false;
    }

}