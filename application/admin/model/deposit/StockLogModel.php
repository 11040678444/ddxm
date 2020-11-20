<?php

// +----------------------------------------------------------------------
// | 盘点单
// +----------------------------------------------------------------------
namespace app\admin\model\deposit;

use think\Model;
use think\Db;

class StockLogModel extends Model
{
	protected $table = 'ddxm_stock_log';

	//仓库
	public function getShopIdAttr($val){
		if( $val == 0 ){
			return '门店错误';
		}
		return Db::name('shop')->where('id',$val)->value('name');
	}

	// //状态
	// public function getStatusAttr($val){
	// 	$array = array(
	// 		'1'	=>'盘点待确认',
	// 		'2'	=>'库存待确认',
	// 		'3'	=>'已完成',
	// 	);
	// 	return $array[$val];
	// }

	//盘点时间
	public function getCreateTimeAttr($val){
		if( $val == 0 ){
			return '时间错误';
		}
		return date('Y-m-d H:i:s',$val);
	}
	//盘点时间
	public function getEndTimeAttr($val){
		if( $val == 0 ){
			return '时间错误';
		}
		return date('Y-m-d H:i:s',$val);
	}
}