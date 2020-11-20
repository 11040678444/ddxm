<?php

// +----------------------------------------------------------------------
// | 后台控制模块
// +----------------------------------------------------------------------
namespace app\common\controller;

use app\common\model\admin\Admin;
use think\Db;
use think\Controller;

class Backendbase extends Controller
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
            exit(json_encode(['code'=>-1,'msg'=>'用户未登陆','data'=>'']));
        }
        $userTokenInfo = Db::name('admin_token')
            ->alias('a')
            ->join('admin b','a.user_id=b.userid')
            ->where('a.token',$token)
            ->find();
        if ( !$userTokenInfo || (time() > $userTokenInfo['expire_time']) ) {
            exit(json_encode(['code'=>-2,'msg'=>'登陆已过期','data'=>'']));
        }
    }

    //获取用户信息
    public function getUserInfo(){
        $token = $this ->request->header('XX-Token');
        $userId = Db::name('admin_token') ->where('token',$token)->value('user_id');

        return (new Admin()) ->getUserInfo($userId);
    }
}
