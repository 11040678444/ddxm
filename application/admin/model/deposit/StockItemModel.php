<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\deposit;

use think\Model;
use think\Db;

class StockItemModel extends Model
{
	protected $table = 'ddxm_stock_item';

	/**
	获取商品详情
	$stockIds 表示为stock表的id组，用 "," 隔开,前后不能有逗号
	*/
	public function getItemList($stockIds,$data=null){
		if( $stockIds == '' ){
			return 'stock_id错误';
		}	
		if( !empty($data['type_id']) ){
			$where[] = ['b.type_id','=',$data['type_id']];
		}
		if( !empty($data['type']) ){
			$where[] = ['b.type','=',$data['type']];
		}
		if( !empty($data['title']) ){
			$where[] = ['b.title','like','%'.$data['title'].'%'];
		}
		$where[] = ['a.stock_id','in',$stockIds];
		$list = $this 
			->alias('a')
			->join('item b','a.item_id=b.id')
			->where($where)
			->field('a.stock,a.num,a.remarks,a.id,a.item_id,b.type_id,b.type,b.title');
		return $list;
	}

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