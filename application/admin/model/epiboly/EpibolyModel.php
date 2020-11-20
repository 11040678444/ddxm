<?php

// +----------------------------------------------------------------------
// | 外包分润模型
// +----------------------------------------------------------------------
namespace app\admin\model\epiboly;

use think\Model;
use think\Db;
class EpibolyModel extends Model
{
	protected $table = 'ddxm_epiboly';

	public function getShopIdAttr($val){
        return Db::name('shop')->where("id",$val)->value("name");
	}

	public function getTimeAttr($val){
		return date('Y-m-d H:i:s',$val);
	}

	public function getUserIdAttr($val){
	    return Db::name('admin')->where('userid',$val) ->value('nickname');
    }

	//操作人
	/*public function getUserIdAttr($val){
		$db  = db::connect(config('ddxm_admin'));
		$user_login = $db ->name('user')->where('id',$val)->value('user_login');	//查询用户名称
		$role_user = $this ->getRoleUser($val);		//查询用户是否有角色

		if( empty($role_user) ){
			return $user_login;
		}

		$new = [];
		foreach ($role_user as $key => $value) {
			array_push($new, $value['role_id']);
		}
		$new = implode(',', $new);

		$where['id'] = array($new);
		$role = $this ->getRole($where);		//查询用户的角色名称

		return $user_login.'('.$role.')';
	}
	public function getRoleUser($user_id){
		$db  = db::connect(config('ddxm_admin'));
		$role_user = $db->name('role_user')->where('user_id',$user_id)->select();
		return $role_user;
	}
	public function getRole($where){
		$db  = db::connect(config('ddxm_admin'));
		$role =  $db->name('role')->where($where)->field('name')->select();
		$new = [];
		foreach ($role as $key => $value) {
			array_push($new, $value['name']);
		}
		$new = implode(',', $new);
		return $new;
	}*/
}