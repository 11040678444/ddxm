<?php
/**
	公共方法
*/
namespace app\common\controller;
use app\common\model\UtilsModel;
use think\Db;
use think\Controller;
use app\common\model\UserToken;

class Common extends Controller
{

	/*
		生成token
		$userId 用户id
		@return string 用户 token
	*/	
	protected function initialize(){
		// 处理跨域问题
        header('Content-Type:application/json;charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
	}

	public function ddxm_user_token($userId){
		$UserToken = new UserToken();
	    $findUserToken  = $UserToken->getUserToken($userId)->find();
	    $currentTime    = time();
	    $expireTime     = $currentTime + 24 * 3600 * 180;
	    $token          = md5(uniqid()) . md5(uniqid());
	    // dump($findUserToken);die;
	    if (empty($findUserToken)) {
	    	$userData = [
	            'token'       => $token,
	            'user_id'     => $userId,
	            'expire_time' => $expireTime,
	            'create_time' => $currentTime
	        ];
	        $UserToken ->addToken($userData);
	    } else {
	    	$userData = [
	                'token'       => $token,
	                'expire_time' => $expireTime,
	                'create_time' => $currentTime
	            ];
	        $UserToken ->editToken($userData,$userId);
	    }
        //生成登陆日志
        $userInfo = Db::name('shop_worker')->where('id',$userId)->find();
        self::setLoginLog(['id'=>$userId,'user_login'=>$userInfo['mobile'],'user_name'=>$userInfo['name']]);

	    return $token;
	}

    /***
     *  生成登陆日志
     */
    public function setLoginLog($memberInfo){
        if( empty($memberInfo['id']) || empty($memberInfo['user_login']) ){
            return false;
        }
        $data = array(
            'user_id'   =>$memberInfo['id'],
            'user_login'   =>$memberInfo['user_login'],
            'ip'   => (new UtilsModel()) ->getUserIpAddr() ,
            'time'   =>time(),
            'user_name'   =>!empty($memberInfo['user_name'])?$memberInfo['user_name']:$memberInfo['user_login'],
            'type'   =>2,
            'info'  =>!empty($memberInfo['user_name'])?$memberInfo['user_name']:$memberInfo['user_login'].'在'.date('Y-m-d H:i:s').' 登陆了门店'
        );
        $res = Db::name('login_log')->insert($data);
        if( !$res ){
            return false;
        }
        return true;
    }

	/**
		登录密码验证
	*/
	function cmf_password($pw)
	{
	    $authCode = 'OV4w80Ndr23wt4yW1j';
	    $result = "###" . md5(md5($authCode . $pw));
	    return $result;
	}
}