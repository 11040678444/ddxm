<?php
/*
	
*/
namespace app\index\model\Expenditure;

use think\Model;
use think\Cache;
use think\Db;

class ExpenditureModel extends Model
{
	protected $table = 'ddxm_expenditure';

	public function getCreateTimeAttr($val){
		return date('Y-m-d',$val);
	}

	public function getTimeAttr($val,$data){
	    if( $val == 0 ){
            return date('Y-m-d',$data['create_time']);
        }
        return date('Y-m-d',$val);
    }

	//门店
	public function getShopIdAttr($val){
        return Db::name('shop')->where('id',$val)->value('name');
	}
	public function getTypeAttr($val,$data){
		return Db::name('expenditure_types')->where('id',$data['type_id'])->value('title');
	}
	//操作人
	public function getUserIdAttr($val,$data){

		if( $data['is_admin'] == 0 ){
			$user_login = Db::name('shop_worker')->where('id',$val)->value('name');
			$user_login = $user_login."[店长]";
		}else{
			$user_login = Db::name('admin')->where('userid',$val)->value('username');
		}
		return $user_login;
	}

	// public function getRoleUser($user_id){
	// 	$db  = db::connect(config('ddxm_admin'));
	// 	$role_user = $db->name('role_user')->where('user_id',$user_id)->select();
	// 	return $role_user;
	// }
	// public function getRole($where){
	// 	$db  = db::connect(config('ddxm_admin'));
	// 	$role =  $db->name('role')->where($where)->field('name')->select();
	// 	$new = [];
	// 	foreach ($role as $key => $value) {
	// 		array_push($new, $value['name']);
	// 	}
	// 	$new = implode(',', $new);
	// 	return $new;
	// }




	//备注
	public function getRemarksAttr($val){
		if( empty($val) ){
			return '无';
		}else{
			return $val;
		}
	}
}