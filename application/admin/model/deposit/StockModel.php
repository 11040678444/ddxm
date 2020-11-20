<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\deposit;

use think\Model;
use think\Db;

class StockModel extends Model
{
	protected $table = 'ddxm_stock';

	//仓库
	public function getShopIdAttr($val){
		if( $val == 0 ){
			return '门店错误';
		}
		return Db::name('shop')->where('id',$val)->value('name');
	}

	// //盘点人
	// public function getCreatorIdAttr($val){
	// 	if( $val == 0 ){
	// 		return '盘点人错误';
	// 	}
	// 	$t =  Db::name('admin')->where('userid',$val)->value('username');
	// }

	//盘点时间
	public function getTimeAttr($val){
		if( $val == 0 ){
			return '时间错误';
		}
		return date('Y-m-d H:i:s',$val);
	}
}