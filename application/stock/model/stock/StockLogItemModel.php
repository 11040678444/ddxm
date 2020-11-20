<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\stock\model\stock;

use think\Model;
use think\Db;

class StockLogItemModel extends Model
{
	protected $table = 'ddxm_stock_log_item';

	/**
	获取商品详情
	$stockIds 表示为stock表的id组，用 "," 隔开,前后不能有逗号
	*/
	public function getItemList($stockIds,$data=null){
		if( $stockIds == '' ){
			return 'stock_id错误';
		}
		$where[] = ['a.log_id','in',$stockIds];
		$list = $this 
			->alias('a')
			->where($where)
			->field('a.id,a.log_id,a.item_id,a.item_title,a.stock_reality,a.stock_now,a.attr_ids,a.attr_name');
		return $list;
	}
}