<?php

// +----------------------------------------------------------------------
// | 商品单位
// +----------------------------------------------------------------------
namespace app\admin\model\item;

use think\Model;
use think\Db;

class ItemUnitModel extends Model
{
	protected $table = 'ddxm_item_unit';

	public function getCreateTimeAttr($val){
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

	//更新人
	public function getUpdateIdAttr($val){
		if( $val==0 ){
			return '0';
		}
		return Db::name('admin')->where('userid',$val)->value('username');
	}

	
}