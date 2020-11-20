<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\warehousing;

use think\Model;
use think\Db;

class WarehousingItemModel extends Model
{
	protected $table = 'ddxm_warehousing_item';

	//根据入库单的id获取商品列表
	public function getItem($warehousingId){
		$item = $this ->where('warehousing_id',$warehousingId)->field('id,warehousing_id,price,num,all_price,item_id,item_name')->select();
		return $item;
	}
}