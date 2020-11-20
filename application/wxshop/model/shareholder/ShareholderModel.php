<?php
/*
    股东数据统计表
*/
namespace app\wxshop\model\shareholder;
use think\Model;
use think\Cache;
use think\Db;

class ShareholderModel extends Model
{
	protected $table = 'ddxm_shareholder';

	//获取股东的门店列表
	public function getShopIdsAttr($val){
		if( $val == '' ){
			return '门店列表错误';
		}
		$shopIds = rtrim($val,','); 	//去除最后一个 逗号
		$shopIds = ltrim($shopIds,',');	//去除第一个 逗号
		$where[] = ['id','in',$shopIds];
		$shops = Db::name('shop')->where($where)->field('id,name')->select();
		return $shops;
	}
}