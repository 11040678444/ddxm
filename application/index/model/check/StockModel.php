<?php

// +----------------------------------------------------------------------
// | 盘亏，盘盈单模型
// +----------------------------------------------------------------------
namespace app\index\model\check;

use think\Model;
use think\Db;

class STockModel extends Model
{
	protected $table = 'ddxm_stock';

	//
	public function getCreateTimeAttr($val){
		if( $val == 0 ){
			return 0;
		}
		return date('Y-m-d H:i:s',$val);
	}

	public function getEndTimeAttr($val){
		if( $val == 0 ){
			return 0;
		}
		return date('Y-m-d H:i:s',$val);
	}

	//获取门店名称
	public function getShopIdAttr($val){
		if( $val == 0 ){
			return 0;
		}

		return Db::name('shop')->where('id',$val)->value('name');
	}

	//状态
	public function getStatusAttr($val){
		$array = array(
			'1'	=>'待确认',
			'2'	=>'已确认',
		);
		return $array[$val];
	}
}