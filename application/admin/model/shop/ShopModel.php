<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\shop;

use think\Model;

class ShopModel extends Model
{
	protected $table = 'ddxm_shop';

	public function getAddtimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s',$val);
		}
	}

	public function getUpdateTimeAttr($val){
		if( $val == 0 ){
			return 0;
		}else{
			return date('Y-m-d H:i:s',$val);
		}
	}

	//获取最大id
    public function maxId()
    {
        return $this->max('id');
    }
    
}