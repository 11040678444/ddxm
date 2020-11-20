<?php

// +----------------------------------------------------------------------
// | 普通商品的展示库存
// +----------------------------------------------------------------------
namespace app\index\model\item;

use think\Model;
use think\Db;

class ShopItemModel extends Model
{
	protected $table = 'ddxm_shop_item';

	public function getStock($itemIds,$shop_id){
		$where = [];
		$where[] = ['item_id','in',$itemIds];
		$where[] = ['shop_id','eq',$shop_id];
		$list = $this
			->where($where)
			->field('item_id,stock')
			->select();
		return $list;
	}
}