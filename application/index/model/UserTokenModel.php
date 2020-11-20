<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;
use think\Cache;
use think\Db;

class UserTokenModel extends Model
{
    // protected $connect = [
    //     // 数据库类型
    //     'type'        => 'mysql',
    //     // 服务器地址
    //     //'hostname'    => '120.55.63.230',
    //    // 'hostname'    => 'localhost',
    //     'hostname'    => '120.79.5.57',
    //     // 数据库名
    //     'database'    => 'ddxm_admin',
    //     // 数据库用户名
    //     'username'    => 'root',
    //     // 数据库密码
    //     'password'    => 'ddxm2019',
    //     // 数据库连接端口
    //     'hostport'    => '3306',
    //     // 数据库编码默认采用utf8
    //     'charset'     => 'utf8',
    //     // 数据库表前缀
    //     'prefix'      => 'ddxm_',
    //     // 数据集返回类型
    //     'resultset_type' => '',
    // ];
    protected $table = 'ddxm_user_token';

    //通过token获取用户id
    public function getUserId($token){
        // $db = Db::connect($this ->connect);
        return $this ->where('token',$token)->find();
    }


    //通过token退出登陆，将过期时间转为退出登陆的时间
    public function outLogin($token){
        // $db = Db::connect($this ->connect);
        return $this ->where('token',$token)->update(['expire_time'=>time()]);
    }
}
