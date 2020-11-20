<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\shop;

use think\Model;
use think\Db;

class ServiceModel extends Model
{
	protected $table = 'ddxm_service';

	public function getCreatetimeAttr($val){
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

	public function getTypesAttr($val,$data){
		$val = $data['type'];
		if( $val == 0 ){
			return '暂无分类';
		}
		return Db::name('item_category')->where('id',$val)->value('cname');
	}

	//更新人
	public function getUpdateIdAttr($val){
		if( $val == 0 ){
			return 0;
		}
		return Db::name('admin')->where('userid',$val)->value('username');
	}
}