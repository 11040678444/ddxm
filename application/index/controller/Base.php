<?php
namespace app\index\controller;

use think\Db;
use think\Request;
use app\common\controller\Common;
use app\index\model\UserTokenModel;
use app\index\model\UserModel;

class Base extends Common
{
	protected function initialize()
    {
    	header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
    	$token = $this ->request->header('XX-Token');
    	if( empty($token) ){
    		exit(json_encode(['code'=>'-1','msg'=>'用户未登陆','data'=>'']));
    	}

    	$UserToken = new UserTokenModel();
		$userTokenInfo = $UserToken->getUserId($token);
		if ( time() > $userTokenInfo['expire_time'] ) {
			exit(json_encode(['code'=>'-2','msg'=>'登陆已过期','data'=>'']));
		}
    }

    /*
		获取个人用户id
    */
	public function getUserId(){
		$token = $this ->request->header('XX-Token');
		$UserToken = new UserTokenModel();
		$userTokenInfo = $UserToken->getUserId($token);
		$userId = $userTokenInfo['user_id'];
		return $userId;
	}

	/**
		h获取用户个人信息
	*/
	public function getUserInfo(){
		$userId = $this ->getUserId();
		$User = new UserModel();
		$userInfo = $User->findUser($userId);
		$userInfo['shop_id'] = $userInfo['sid'];
		return $userInfo;
	}

	/**
		退出登录
	*/
	public function outLogin(){
		$token = $this ->request->header('XX-Token');
		$UserToken = new UserTokenModel();
		return $UserToken->outLogin($token);
	}
}
