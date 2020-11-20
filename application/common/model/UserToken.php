<?php

// +----------------------------------------------------------------------
// | 用户token
// +----------------------------------------------------------------------
namespace app\common\model;

use think\Db;
use think\facade\Config;
use think\Model;

/**
 * 公共模型
 */
class UserToken extends Model
{
	// protected $connect = [
 //        // 数据库类型
 //        'type'        => 'mysql',
 //        // 服务器地址
 //        //'hostname'    => '120.55.63.230',
 //        // 'hostname'    => 'localhost',
 //       'hostname'    => '120.79.5.57',
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
    protected $table = 'ddxm_user_token';

    /**
		查询用户的token信息
    */
	public function getUserToken($userId){
		// $db = Db::connect($this->connect);
		return $this ->where('user_id',$userId);
	}

	/**
		添加token
	*/
	public function addToken($data){
		// $db = Db::connect($this->connect);
		return $this->insert($data);
	}

	/**
		修改token
	*/
	public function editToken($data,$userId){
		// $db = Db::connect($this->connect);
		return $this->where('user_id',$userId)->update($data);
	}
}	