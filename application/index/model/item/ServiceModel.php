<?php

// +----------------------------------------------------------------------
// | 服务商品
// +----------------------------------------------------------------------
namespace app\index\model\item;

use think\Model;
use think\Db;

class ServiceModel extends Model
{
	protected $table = 'ddxm_service';

	//获取列表
	public function getList($Ids=null,$shop_id,$levelId){
		$where = [];
		if( !empty($Ids) ){
			$where[] = ['a.id','in',$Ids];
		}
		$where[] = ['b.shop_id','eq',$shop_id];
		$where[] = ['b.status','eq',1];
		$where[] = ['b.level_id','eq',$levelId];
		$where[] = ['a.status','eq',1];

		$list = $this 
			->alias('a')
			->where($where)
			->join('service_price b','a.id=b.service_id')
			->field('a.id,a.sname,b.price as cost_price')
			->select();
		return $list;
	}

	//获取服务分类的名称
	public function getCategoryAttr($val,$data){
		$type = $data['type'];
		if( $type == 0 ){
			return '分类发生错误';
		}
		return Db::name('item_category')->where('id',$type)->value('cname');
	}
	public function getCoverAttr($val){
		return "http://picture.ddxm661.com/".$val;
	}
}