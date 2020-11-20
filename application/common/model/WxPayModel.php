<?php
/**
 * 微信小程序支付
 * Author: chenjing
 * Date: 2017/11/13
 * Description:
 */
namespace app\common\model;

use app\common\model\UtilsModel;
use app\wxshop\wxpay\JsApiPay;
use app\wxshop\wxpay\WxPayApi;
use app\wxshop\wxpay\WxPayConfig;
use app\wxshop\wxpay\WxPayException;
use app\wxshop\wxpay\WxPayJsApiPay;
use app\wxshop\wxpay\WxPayRefund;
use app\wxshop\wxpay\WxPayUnifiedOrder;
use http\Exception;
use think\facade\Cache;
use think\Validate;
use think\Model;
use think\Db;

class WxPayModel extends Model
{
    /**
     * 微信 支付
     * @param $data
     * @return \app\wxshop\wxpay\json数据，可直接填入js函数作为参数|bool
     * @throws \app\wxshop\wxpay\WxPayException
     */
    public function pay($data){
        try{
            $tools = new JsApiPay();
            //②、统一下单
            $input = new WxPayUnifiedOrder();
            $input->SetBody("购买商品");//设置商品或支付单简要描述
//            $input->SetBody($data['title']);//设置商品或支付单简要描述
            $input->SetAttach('购买商品');//设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            $input->SetOut_trade_no($data['order_sn']);//设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
//            $input->SetTotal_fee(round(0.01*100));//支付金额
            $input->SetTotal_fee(round($data['amount']*100));//支付金额
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetNotify_url($data['notify_url']);
            $input->SetTrade_type("JSAPI");
//            $input->SetOpenid('oVQ0O5Nf9qWxPBxJH2Nwr7YTytQg');
            $input->SetOpenid($data['openId']);
            $config = new WxPayConfig();
            $order = WxPayApi::unifiedOrder($config, $input);
            $jsApiParameters = $tools->GetJsApiParameters($order);
            return $jsApiParameters;
        } catch(Exception $e) {
//            foreach($order as $key=>$value){
//                echo "<font color='#00ff55;'>$key</font> :  ".htmlspecialchars($value, ENT_QUOTES)." <br/>";
//            }
            return false;
        }
    }

    /**
     * 退单
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
     * @param $data
     * @return \app\wxshop\wxpay\json数据，可直接填入js函数作为参数|bool
     * @throws \app\wxshop\wxpay\WxPayException
     */
    public function refund($data){
        try{
//            $tools = new JsApiPay();
            $wxPayRefund = new WxPayRefund();
            $wxPayRefund->SetOut_trade_no($data['order_sn']);// 商品 订单号-- 必须和下单时 订单号 一致
            $wxPayRefund->SetOut_refund_no($data['refund_no']);// 退单 订单号
            $wxPayRefund->SetTotal_fee($data['total_fee']*100);//订单总金额，单位为分，只能为整数，详见支付金额
            $wxPayRefund->SetRefund_fee($data['refund_fee']*100);//	退款总金额，订单总金额，单位为分，只能为整数，详见支付金额
            $wxPayRefund->SetOp_user_id('1486226662');
//            $wxPayRefund->SetNo"https://ddxm661.com/wxshop/Wxnotify/refundindex");
//            refund_desc  退款原因

            $config = new WxPayConfig();
            return WxPayApi::refund($config, $wxPayRefund);
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * 获取access_token
     */
    public function getAccessToken(){
        $appId = config('WeChat')['appid'];
        $secret = config('WeChat')['AppSecret'];
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appId.'&secret='.$secret;
        $post_data = [];
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 20 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        try{
            $arr = json_decode($result);
            $arr2 = json_decode(json_encode($arr), true);
            Cache::set('access_token',$arr2['access_token'],$arr2['expires_in']);       //存储access_token
            return $arr2['access_token'];
        }catch (\think\Exception $e){
            return false;
        }
    }

    /***
     * 获取jsapi_ticket
     */
    public function getJsApiTicket( $access_token ,$toUrl){
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
        $post_data = [];
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 20 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $arr = json_decode($result);
        $arr2 = json_decode(json_encode($arr), true);
        if( $arr2['errmsg'] != 'ok' ){
            return false;
        }
        $wx = [];
        //生成签名的时间戳
        $wx['timestamp'] = time();
        //生成签名的随机串
        $wx['noncestr'] = 'Wm3WZYTPz0wzccnW';
        //jsapi_ticket是公众号用于调用微信JS接口的临时票据。正常情况下，jsapi_ticket的有效期为7200秒，通过access_token来获取。
        $wx['jsapi_ticket'] = $arr2['ticket'];
        //分享的地址，注意：这里是指当前网页的URL，不包含#及其后面部分，曾经的我就在这里被坑了，所以小伙伴们要小心了
        $wx['url'] = $toUrl;
        $string = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wx['jsapi_ticket'], $wx['noncestr'], $wx['timestamp'], $wx['url']);
        //生成签名
        $wx['signature'] = sha1($string);
        $wx['appid'] = config('WeChat')['appid'];		//appid
        $wx['expires_in'] = $arr2['expires_in'];		//jsapi_ticket过期时间
        return $wx;
    }

    /***
     * 获取临时素材
     * @param $mediaId
     * @return array
     */
    public function getDownloadMedia( $mediaId ){
        if( empty($mediaId) ){
            return ['code'=>100,'msg'=>'服务ID错误'];
        }
        $access_token = Cache::get('access_token');
        if( !Cache::has('access_token') ){
            $access_token = $this ->getAccessToken();
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token.'&media_id='.$mediaId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $result = array_merge(array('header' => $httpinfo), array('body' => $package));		//素材文件
        $arr = json_decode($result['body']);
        $res = json_decode(json_encode($arr), true);
        if( is_array($res) ){
            return ['code'=>100,'msg'=>'服务ID错误'];
        }
        return $result['body'];
    }

    /***
     * array(3) {
     *       ["errcode"] => int(0)  //错误码
     *       ["errmsg"] => string(2) "ok"   //错误信息
     *       ["msgid"] => int(1093201563310637056)  //
     *   }
     * 获取模板消息
     */
    public function send_message( $post_data ){
        $access_token = Cache::get('access_token');
        if( !$access_token ){
            $access_token = self::getAccessToken();
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        $post_data = json_encode($post_data);
        $result = (new UtilsModel()) ->httpPost($url,$post_data);
        $res = json_decode($result,true);
        return $res;
    }

    /***
     * 发送客户消息
     */
    public function sed_custom_message($post_data){
        $access_token = Cache::get('access_token');
        if( !$access_token ){
            $access_token = self::getAccessToken();
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
        $options = array(
            'http' => array(
                'method' => 'post',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $post_data,
                'timeout' => 20 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    /****
     * 企业付款到用户个人
     */
    public function transfers($data){
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $post_data = [
            'mch_appid'     =>config('WeChat')['appid'],
            'mchid'     =>config('WeChat')['MerchantId'],
            'nonce_str'     =>md5(uniqid(microtime(true),true)),
            'partner_trade_no'     =>$data['sn'],
            'openid'     =>$data['openid'],
            'check_name'     =>'NO_CHECK',
            'amount'     =>$data['amount']*100,
            'desc'     =>'用户提现申请',
            'spbill_create_ip'     =>(new UtilsModel()) ->getUserIpAddr()
        ];

        //排序
        ksort($post_data);
        //生成sign
        $str 	= urldecode(http_build_query($post_data)).'&key='.config('WeChat')['key'];
        $sign 	= strtoupper(md5($str));
        $post_data['sign'] = $sign;
        $str='<xml>';
        foreach ( $post_data as $k=>$v ){
            $str.='<'.$k.'>'.$v.'</'.$k.'>';
        }
        $str.='</xml>';
        $res = (new UtilsModel()) ->wx_curl($str,$url);
        $res = (new UtilsModel()) ->xmlToArray($res);
        return $res;
    }

    /***
     * 查询企业付款到个人付款结果
     */
    public function gettransferinfo($data){
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
        $post_data = [
            'nonce_str' =>md5(uniqid(microtime(true),true)),
            'partner_trade_no' =>$data['sn'],
            'mch_id' =>config('WeChat')['MerchantId'],
            'appid' =>config('WeChat')['appid'],
        ];
        //排序
        ksort($post_data);
        //生成sign
        $str 	= urldecode(http_build_query($post_data)).'&key='.config('WeChat')['key'];
        $sign 	= strtoupper(md5($str));
        $post_data['sign'] = $sign;
        $str='<xml>';
        foreach ( $post_data as $k=>$v ){
            $str.='<'.$k.'>'.$v.'</'.$k.'>';
        }
        $str.='</xml>';
        $res = (new UtilsModel()) ->wx_curl($str,$url);
        $res = (new UtilsModel()) ->xmlToArray($res);
        return res;
    }
}
