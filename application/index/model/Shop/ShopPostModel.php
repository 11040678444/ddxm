<?php

// +----------------------------------------------------------------------
// | 门店岗位
// +----------------------------------------------------------------------
namespace app\admin\model\shop;

use think\Model;

class ShopPostModel extends Model
{
	protected $table = 'ddxm_shop_post';

	public function getCreatetimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s');
		}
	}

	public function getUpdateTimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s');
		}
	}

	
}