<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\member;

use think\Model;
use think\Db;

class MemberLevelModel extends Model
{
	protected $table = 'ddxm_member_level';

	public function getAddTimeAttr($val){
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

	//更新人
	public function getUpdateIdAttr($val){
		if( $val==0 ){
			return '0';
		}
		return Db::name('admin')->where('userid',$val)->value('username');
	}

	
}