<?php
/**
	用户个人信息

*/
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use app\index\model\UserModel;

class User extends Base
{
	/**
		获取用户个人信息
	*/
	public function userInfo(){
		header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*'); 
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*'); 
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
		
		$userInfo = $this ->getUserInfo();
		$userList['id'] = $userInfo['id'];
		$userList['user_nickname'] = $userInfo['name'];
		$userList['shop_id'] = $userInfo['sid'];
		$userList['shop_name'] = Db::name('shop')->where('id',$userInfo['sid'])->value('name');
		return json(['code'=>'200','msg'=>'请求成功','data'=>$userList]);
	}

	/**
	退出登陆
	*/
	public function out_login(){
		header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*'); 
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*'); 
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
		
		$res = $this ->outLogin();
		if( $res ){
			return json(['code'=>'200','msg'=>'退出成功','data'=>'']);
		}else{
			return json(['code'=>'300','msg'=>'退出失败','data'=>'']);
		}
	}

	/**
     * 修改密码
     */
    public function edit()
    {
        $data = $this ->request ->param();
        if ( empty($data['password']) )
        {
            return json(['code'=>'300','msg'=>'请填写密码','data'=>'']);
        }
        $userInfo = $this ->getUserInfo();
        $cmf_password = $this ->cmf_password($data['password']);
        $res = Db::name('shop_worker') ->where('id',$userInfo['id']) ->setField('password',$cmf_password);
        if( $res ){
            return json(['code'=>'200','msg'=>'操作成功','data'=>'']);
        }else{
            return json(['code'=>'300','msg'=>'操作失败','data'=>'']);
        }
	}
}