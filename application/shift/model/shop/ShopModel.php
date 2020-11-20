<?php
namespace app\shift\model\shop;

use app\shift\model\ShiftbaseModel;
use think\Model;
use think\Cache;
use think\Db;

class ShopModel extends ShiftbaseModel
{	
	protected $table = 'tf_shop';

	public function getLevelStandardAttr($val){
		if( empty($val) ){
			return '';
		}
		return json_decode($val,true);
	}
}