<?php
/**
 * 微信小程序登陆
 * Author: chenjing
 * Date: 2017/11/13
 * Description:
 */
namespace app\common\model;

use think\Validate;
use app\common\model\UtilsModel;
use think\Model;
use think\Db;

class WechatModel extends Model
{
    protected $Appid;
    protected $AppSecret;
    public function __construct() {
        $this->Appid = config('appid');
        $this->AppSecret = config('AppSecret');
    }

    /***
     * 获取access_token
     * @return \think\response\Json
     */
    public function getToken(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->Appid."&secret=".$this->AppSecret;
        $result = file_get_contents($url);
        $result = json_decode($result,true);
        return $result;
    }

    /***
     * 获取openid
     * @param null $js_code
     * @return false|mixed|string
     */
    public function getOpendId($js_code=null){
        $url = "https://api.weixin.qq.com/sns/jscode2session?";
        $grant_type = "authorization_code";
        $url = $url.'appid='.$this ->Appid.'&secret='.$this ->AppSecret.'&js_code='.$js_code.'&grant_type='.$grant_type;

        $result = (new UtilsModel())->httpGet($url);
        $result = json_decode($result,true);
        return $result;
    }
}
