<?php
namespace app\shift\model\order;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class OrderModel extends ShiftbaseModel
{	
	protected $table = 'tf_order';

	public function getList($where,$page){
		$list = $this ->where($where)->page($page);
		return $list;
	}
}