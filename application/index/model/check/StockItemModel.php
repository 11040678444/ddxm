<?php

// +----------------------------------------------------------------------
// | 盘点单模型
// +----------------------------------------------------------------------
namespace app\index\model\check;

use think\Model;
use think\Db;

class STockItemModel extends Model
{
	protected $table = 'ddxm_stock_item';

	//获取商品的分类名称
	public function getTypeIdAttr($val){
		if($val == 0){
			return '暂无一级分类';
		}
		return Db::name('item_category')->where('id',$val)->value('cname');
	}

	//获取商品的分类名称
	public function getTypeAttr($val){
		if($val == 0){
			return '暂无一级分类';
		}
		return Db::name('item_category')->where('id',$val)->value('cname');
	}
}