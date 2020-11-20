<?php

// +----------------------------------------------------------------------
// | 盘点单模型
// +----------------------------------------------------------------------
namespace app\index\model\check;

use think\Model;
use think\Db;

class STockLogModel extends Model
{
	protected $table = 'ddxm_stock_log';

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
			'1'	=>'盘点待确认',
			'2'	=>'库存待确认',
			'3'	=>'已完成',
		);
		return $array[$val];
	}
}