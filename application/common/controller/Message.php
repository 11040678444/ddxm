<?php
/**发送短信调用的方法*/
namespace app\common\controller;
use think\Db;
use think\Controller;

class Message extends Controller
{
    /***
     * 模拟post请求
     * @param $url
     * @param $post_data
     * @return bool|string
     */
    function request_post($url, $post_data) {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    /***
     * @param $length
     * @param string $type
     * @return string|null
     */
    function getRandChar($length,$type = 'numeric')
    {
        $str = null;
        switch ($type){
            case 'numeric':
                $strPol = "0123456789";
                break;
            default:
                $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        }
        $max = strlen($strPol) - 1;

        for ($i = 0;
             $i < $length;
             $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

    public function api_notice_increment($url, $data){
        $ch = curl_init();
        $header=array('Accept-Language:zh-CN','x-appkey:114816004000028',
            'x-apsignature:933931F9124593865313864503D477035C0F6A0C551804320036A2A1C5DF38297C9A4D30BB1714EC53214BD92112FB31B4A6FAB466EEF245710CC83D840D410A7592D262B09D0A5D0FE3A2295A81F32D4C75EBD65FA846004A42248B096EDE2FEE84EDEBEBEC321C237D99483AB51235FCB900AD501C07A9CAD2F415C36DED82','x-apversion:1.0','Content-Type:application/x-www-form-urlencoded','Accept-Charset: utf-8','Accept:application/json','X-APFormat:json');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }else{
            return $tmpInfo;
        }
    }


}