<?php
namespace app\stock\controller;

use think\Controller;
use think\Db;
use app\common\model\admin\Admin;
/**
进销存登陆
 */
class Login extends Controller
{
    /***
     * 账号密码快速登陆
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function Login(){
        $data = $this ->request ->param();
        if( empty($data['username']) || empty($data['password']) ){
            return json(['code'=>100,'msg'=>'请输入账号或密码']);
        }
        $userInfo = (new Admin()) ->login($data);
        if( !$userInfo ){
            return json(['code'=>100,'msg'=>'密码错误']);
        }
        return json(['code'=>200,'msg'=>'登陆成功','data'=>$userInfo]);
    }
}