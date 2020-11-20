<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use think\Query;
use think\Request;
/**
商城,需要登录时的统一控制器
 */
class Token extends Controller
{
    protected function initialize()
    {
//        header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
        $token = $this ->request->header('XX-Token');
        if( empty($token) ){
            exit(json_encode(['code'=>-1,'msg'=>'用户未登陆','data'=>'']));
        }
        $userTokenInfo = Db::name('member_token')
            ->alias('a')
            ->join('member b','a.user_id=b.id')
            ->where('a.token',$token)
            ->find();
        if ( !$userTokenInfo || (time() > $userTokenInfo['expire_time']) ) {
            exit(json_encode(['code'=>-2,'msg'=>'登陆已过期','data'=>'']));
        }
    }

    /*
		获取个人用户id
    */
    public function getUserId(){
        $token = $this ->request->header('XX-Token');
        $userTokenInfo = Db::name('member_token')->where('token',$token)->find();
        $userId = $userTokenInfo['user_id'];
        return $userId;
    }

    /**
    h获取用户个人信息
     */
    public function getUserInfo(){
        $userId = $this ->getUserId();
        $userInfo = Db::name('member')->where(['id'=>$userId])->find();
        $money = Db::name('member_money')->where(['id'=>$userId])->value('money');
        $userInfo['money'] = $money;
        $userInfo['shop_id'] = Db::name('shop')->where('code',$userInfo['shop_code'])->value('id');
        return $userInfo;
    }


    /**
    退出登录
     */
    public function outLogin(){
        $token = $this ->request->header('XX-Token');
        $UserToken = Db::name('member_token')->where('token',$token)->update(['expire_time'=>time()]);
        return $UserToken;
    }
}