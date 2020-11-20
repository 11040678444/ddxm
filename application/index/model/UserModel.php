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

class UserModel extends Model
{
 //    protected $connect = [
 //        // 数据库类型
 //        'type'        => 'mysql',
 //        // 服务器地址
 //        //'hostname'    => '120.55.63.230',
 // //       'hostname'    => 'localhost',
 //        'hostname'    => '120.79.5.57',
 //        // 数据库名
 //        'database'    => 'ddxm_admin',
 //        // 数据库用户名
 //        'username'    => 'root',
 //        // 数据库密码
 //        'password'    => 'ddxm2019',
 //        // 数据库连接端口
 //        'hostport'    => '3306',
 //        // 数据库编码默认采用utf8
 //        'charset'     => 'utf8',
 //        // 数据库表前缀
 //        'prefix'      => 'ddxm_',
 //        // 数据集返回类型
 //        'resultset_type' => '',
 //    ];
     protected $table = 'ddxm_shop_worker';

    //查找用戶信息
    public function findUser($userId){
        // $db = Db::connect($this->connect);
        $where['id'] = $userId;
        return $this ->where($where)->find();
    }

    //第一版本
    // public function do_login($emp){
    //     $db = Db::connect($this->connect);
    //     $result = $db->name('user') ->where($emp)->find();
    //     return $result;
    // }

    //第二版本登陆验证
    public function do_login($emp){
        $result = $this ->where($emp)->find();
        return $result;
    }

    //1：查看用戶角色是否為店長
    /*public function user_role( $user_id ){
        $where['role_id'] = 5;
        $where['user_id'] = $user_id;
        $db = Db::connect($this->connect);
        $result = $db ->name('role_user')->where($where)->find();
        // dump($result);die;
        if( $result ){
            return 1;
        }else{
            return 2;
        }
    }*/

    //修改用戶信息
    public function editUser($user_id,$data){
        $db = Db::connect($this->connect);
        $where['id'] = $user_id;
        return $db->name('user') ->where($where)->update($data);
    }
}
