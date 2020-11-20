<?php
/*
	结算控制器
*/
namespace app\index\model\order;

use think\Model;
use think\Cache;
use think\Db;

class OrderGoodsModel extends Model
{
	protected $table = 'ddxm_order_goods';
	public function order_list($order_id){
		return $this ->where('order_id',$order_id);
	}
}
