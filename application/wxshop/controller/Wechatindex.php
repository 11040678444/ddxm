<?php


namespace app\wxshop\controller;

use http\Exception;
use mikkle\tp_wechat\WechatApi;
use think\Log;

/**
 * //https://www.kancloud.cn/mikkle/thinkphp5_study/450540
 * 微信公众号 --相关
 * Class Wechatindex
 * @package app\wxshop\controller
 */
class Wechatindex   extends WechatApi
{

    protected $options=[
        'token'=>'i40gagjEJIZAj8EQXg7j0Ax68Dic6XaA',
        'appid'=>'wxb5ee49b69efc2429',
        'appsecret'=>'f775096b8d02d3a34761ccef9796c8f4',
        'encodingaeskey'=>'ZJ16TV9y1z9BxrYANM8yBx3ByuaxVBKRdaXTj8JVZ8J',
    ];


    /****************************注意 第一次 匹配   ****************************/
    protected $valid = true;  //网站第一次匹配 true 1为匹配
    protected $isHook = false; //是否开启钩子

    public function index(){
        try{
            //   Log::notice($this->request);

            parent::index();
        }catch (Exception $e){
            Log::error($e->getMessage());
        }
    }
}