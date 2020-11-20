<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\member;

use think\Model;
use think\Db;

class MemberModel extends Model
{
	protected $table = 'ddxm_member';

	public function getAddtimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s',$val);
		}
	}

	public function getUpdateTimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s',$val);
		}
	}

	//会员等级
	public function getLevelIdAttr($val){
		if( empty($val) ){
			return '等级错误';
		}
		return Db::name('member_level')->where('id',$val)->value('level_name');
	}

	//所属门店
	public function getShopCodeAttr($val){
		if( empty($val) ){
			return '门店错误';
		}
		return Db::name('shop')->where('code',$val)->value('name');
	}
}