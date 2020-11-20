<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\shop;

use think\Model;
use think\Db;

class ShareholderModel extends Model
{
	protected $table = 'ddxm_shareholder';

	public function getCreatetimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s',$val);
		}
	}

	//查询门店
	public function getShopIdsAttr($val){
		if( empty($val) ){
			return '暂无门店';
		}
		$shopIds = rtrim($val,','); 	//去除最后一个 逗号
		$shopIds = ltrim($shopIds,',');	//去除第一个 逗号
		$where[] = ['id','in',$shopIds];
		$shops = Db::name('shop')->where($where)->column('name');
		return implode(',',$shops);
	}
}