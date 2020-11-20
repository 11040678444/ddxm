<?php
namespace app\shift\model\shop;

use think\Model;
use think\Cache;
use think\Db;

class UserModel extends Model
{	
	protected $table = 'ddxm_user';

	protected $connection = [
	        // 数据库类型
	        'type'        => 'mysql',
	        // 服务器地址
	        //'hostname'    => '120.55.63.230',
	       'hostname'    => 'localhost',
	        // 'hostname'    => '120.79.5.57',
	        // 数据库名
	        'database'    => 'ddxm_admin',
	        // 数据库用户名
	        'username'    => 'root',
	        // 数据库密码
	        'password'    => 'root',
	        // 数据库连接端口
	        'hostport'    => '3306',
	        // 数据库编码默认采用utf8
	        'charset'     => 'utf8',
	        // 数据库表前缀
	        'prefix'      => 'ddxm_',
	        // 数据集返回类型
	        'resultset_type' => '',
	    ];

	//获取门店 店长的信息
	public function getshopowner($shopIds,$role_id){
	    $db  = Db::connect($this->connection);
	    $where[] = ['a.shop_id','in',$shopIds];
	    $where[] = ['b.role_id','=',$role_id];
	    $list = $this
	    		->alias('a')
	    		->join('ddxm_role_user b','a.id=b.user_id')
	    		->where($where)
	    		->field('a.*')
	    		->select();
	    return $list;
	}

	public function getgudong($role_id){
	    $db  = Db::connect($this->connection);
	    $where[] = ['b.role_id','=',$role_id];
	    $list = $this
	    		->alias('a')
	    		->join('ddxm_role_user b','a.id=b.user_id')
	    		->where($where)
	    		->field('a.*')
	    		->select();
	    return $list;
	}


	public function getShopIdAttr($val){
		return explode(',', $val);
	}
}