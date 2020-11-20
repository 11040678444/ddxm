<?php

// +----------------------------------------------------------------------
// | 门店服务人员
// +----------------------------------------------------------------------
namespace app\index\model\shop;

use think\Model;
use think\Db;

class ShopWorkerModel extends Model
{
	protected $table = 'ddxm_shop_worker';

	//获取职位名称
	public function getTypeAttr($val){
		return Db::name('shop_post')->where('id',$val)->value('title');
	}
}