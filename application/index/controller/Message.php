<?php
/**
 * Author: chenjing
 * Date: 2017/11/13
 * Description:
 */

namespace app\index\controller;



use AlibabaCloud\Client\AlibabaCloud;
use Curl\Curl;
use think\Cache;
use think\Controller;
use think\Loader;
use think\Validate;
use app\index\controller\Base;
use think\Db;

class Message extends Controller
{
    /***
     * 第一种
     * @param $mobile
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function sendMessage($mobile)
    {
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $mobile);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return $result;
        }
        $code = controller('common')->getRandChar(4,'numeric');
        $msgtext = $code."（捣蛋熊猫商城身份验证），有效期为10分钟。提示：请勿泄露给他人";
        //检查验证码是否小于1分钟
        $mobile_code = Db::name('verification_code')->where('mobile',$mobile)->order('send_time desc')->find();
        if( time()-$mobile_code['send_time']<=60 ){
            $res = ['code'=>100,'msg'=>'发送太频繁'];
            return $res;
        }
        $t = controller('common')->getRandChar($length = 8,$type = 'numeric');
        $res = self::requestMessagePlant($mobile,$msgtext,$t);
        if( $res['code'] == 200 ){
            //添加最后一次成功记录到数据库
            $arr = array(
                'send_time' =>time(),
                'expire_time'   =>time()+10*60,
                'code'      =>$code,
                'mobile'    =>$mobile
            );
            Db::name('verification_code')->insert($arr);
        }
        return $res;
    }

    //发送信息2

    /***
     * 阿里发送短信
     * @param $mobile
     * @return \AlibabaCloud\Client\Result\Result|array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function sendMessage2($mobile)
    {
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $mobile);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return $result;
        }
        $code = controller('common')->getRandChar(4,'numeric');


        //检查验证码是否小于1分钟
        $mobile_code = Db::name('verification_code')->where('mobile',$mobile)->order('send_time desc')->find();
        if( time()-$mobile_code['send_time']<=60 ){
            $res = ['code'=>100,'msg'=>'发送太频繁'];
            return $res;
        }

        $code1 = json_encode(['code'=>$code]);
        $accessKeyId = 'your accessKeyId';
        $accessKeySecret = 'gtLq74lXFCIYbXNOAmgFFDtZnil9HD';
        $tplCode = 'SMS_172365016';
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId('cn-hangzhou') // replace regionId as you need
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "default",
                        'PhoneNumbers' => $mobile,
                        'SignName' => "捣蛋熊猫",
                        'TemplateCode' => $tplCode,
                        'TemplateParam' => $code1,
                    ],
                ])
                ->request();
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }
        $res = $result->toArray();
        if( $res['Code'] == 'OK' ){
            //添加最后一次成功记录到数据库
            $arr = array(
                'send_time' =>time(),
                'expire_time'   =>time()+10*60,
                'code'      =>$code,
                'mobile'    =>$mobile
            );
            Db::name('verification_code')->insert($arr);
            return ['code'=>200,'msg'=>'发送成功'];
        }else{
            return ['code'=>100,'msg'=>'发送发送失败'];
        }
    }

    //发送其他短信通知
    public static function sendMessageAll($mobile,$msgtext)
    {
        $result = (new BaseValidate())->check(['mobile' => $mobile],['mobile' => 'isMobile']);
        if (!$result) {
            throw new ApiException(301,'手机号码错误!!!');
        }
        //检查一天的发送次数(暂时不做)
        //检查验证码是否小于1分钟
//        $sendMessageTime = Cache::store('file')->get($mobile.'_time') ? Cache::store('file')->get($mobile.'_time') : 0;
//        if (time() - $sendMessageTime < 60) {
//            throw new ApiException(301,'发送验证信息时间小于1分钟');
//        }
//        //缓存验证码
//        $result = Cache::store('file')->set($mobile,$msgtext,600);
//        if ($result) {
//            Cache::store('file')->set($mobile.'_time',time());
//        }
        return self::requestMessagePlants($mobile,$msgtext);
    }


    //请求短信平台(大周短信平台 网上没文档)-- 发送验证码专用
    public static function requestMessagePlant($mobile,$msgtext,$MsgId)
    {
        /*$res['success'] = 1;
        $res['msg'] = '验证码发送成功2';
        $res['msg2'] = $msgtext;
        return $res;
        http://web.jianzhou.sh.cn:8080/WebSMP/login.jsp?error=true*/
        //建周短信平台http接口
        $postdata = array();
        $postdata['userId'] = 'J25888';
        $postdata['password'] = '851239';
        $postdata['pszMobis'] = $mobile;
        $postdata['pszMsg'] = $msgtext;   //自动添加签名
        $postdata['iMobiCount'] = 1;
        $postdata['pszSubPort'] = "*";
        $postdata['MsgId'] = $MsgId;
        $url = 'http://61.145.229.26:8086/MWGate/wmgw.asmx/MongateSendSubmit';
        $o="";
        foreach ($postdata as $k=>$v)
        {
            if($k =='content')
                $o.= "$k=".urlencode($v)."&";
            else
                $o.= "$k=".($v)."&";
        }
        $postdata=substr($o,0,-1);

        //去除xml标签
        $result = strip_tags(controller('Base')->request_post($url,$postdata));
        $res['code'] = 0;
        if(strlen($result)>=15){
            $res['code'] = 200;
            $res['msg'] = '验证码发送成功';
        }else{
            $res['msg'] = '验证码发送失败';
        }
        return $res;
    }


    //请求短信平台(大周短信平台 网上没文档) --- 发送通用信息专用
    public static function requestMessagePlants($mobile,$msgtext)
    {
        //建周短信平台http接口
        $postdata = array();
        $postdata['userId'] = 'J25888';
        $postdata['password'] = '851239';
        $postdata['pszMobis'] = $mobile;
        $postdata['pszMsg'] = $msgtext;   //自动添加签名
        $postdata['iMobiCount'] = 1;
        $postdata['pszSubPort'] = "*";
        $postdata['MsgId'] = getRandChar($length = 8,$type = 'numeric');
        $url = 'http://61.145.229.26:8086/MWGate/wmgw.asmx/MongateSendSubmit';
        $o="";
        foreach ($postdata as $k=>$v)
        {
            if($k =='content')
                $o.= "$k=".urlencode($v)."&";
            else
                $o.= "$k=".($v)."&";
        }
        $postdata=substr($o,0,-1);
        //去除xml标签
        $resutl = strip_tags((new Curl())->post($url,$postdata)->response);
        if(strlen($resutl)>=15){
            return true;
        }else{
           return false;
        }
    }

    //验证验证码是否正确
    public static function checkCode($mobile = '',$code = '')
    {
        //测试环境不检测
        // if(config('app_debug') === true){
        //     return true;
        // }
        if ($_SERVER['TESTENV']) {
            return true;
        }
        if(empty($mobile) || empty($code)){
            return outPut(301,'数据错误');
        }
        $mobileCode = Cache::store('file')->get($mobile);
        if ($code == $mobileCode) {
            return true;
        }
        return false;
    }
}
