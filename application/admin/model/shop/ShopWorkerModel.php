<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\shop;

use think\Model;
use think\Db;

class ShopWorkerModel extends Model
{
	protected $table = 'ddxm_shop_worker';

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

	//获取门店名称
	public function getSidAttr($val){
		if( $val == 0 ){
			return 0;
		}
		return Db::name('shop')->where('id',$val)->value('name');
	}

	//获取职位名称
	public function getPostIdAttr($val){
		if( $val == 0 ){
			return '暂未分配职位';
		}
		return Db::name('shop_post')->where('id',$val)->value('title');
	}

	//获取最大id
    public function maxId()
    {
        return $this->max('id');
    }
}