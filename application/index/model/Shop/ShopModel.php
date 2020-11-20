<?php
/*
	订单控制器
*/
namespace app\index\model\shop;

use think\Model;
use think\Cache;
use think\Db;

class ShopModel extends Model
{
	protected $table = 'ddxm_shop';

	//获取门店列表
	public function getShopList($where){
//		$db = Db::connect(config('ddxx'));
		return $this->name('shop')->where($where);
	}
}