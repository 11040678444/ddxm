<?php

// +----------------------------------------------------------------------
// | 用户token
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Db;
use think\facade\Config;
use think\Model;

/**
 * 身份验证模型
 */
class CheckingIdCardModel
{
    //验证身份证
    public function checkingIdCard($imageUrl,$idCardSide=null){
        $host = "https://ocridcard.mall_admin_market.alicloudapi.com";
        $path = "/idimages";
        $method = "POST";
        $appcode = config('OCR')['appcode'];
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
        $querys = "";
        if( !$idCardSide ){
            $idCardSide = 'front';
        }
        $bodys = "image=$imageUrl"."&idCardSide=$idCardSide"; //图片 + 正反面参数 默认正面，背面请传back
        //或者base64
        //$bodys = 'image=data:image/jpeg;base64,......'.'&idCardSide=front';  //jpg图片base64 + 正反面参数 默认正面，背面请传back
        //$bodys = 'image=data:image/png;base64,......'.'&idCardSide=front';   //png图片base64 +  正反面参数 默认正面，背面请传back
        $url = $host . $path;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        //curl_setopt($curl, CURLOPT_HEADER, true);   如不输出json, 请打开这行代码，打印调试头部状态码。
        //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $out_put = curl_exec($curl);
        $res = json_decode($out_put,true);
        if( $res['code'] == 1 ){
            $res['code'] = 200;
        }
        return $res;
    }
}