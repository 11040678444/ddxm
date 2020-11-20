<?php

// +----------------------------------------------------------------------
// | 商品
// +----------------------------------------------------------------------
namespace app\admin\model\item;

use think\Model;
use think\Db;

class ItemModel extends Model
{
	protected $table = 'ddxm_item';

	//获取每个仓库的商品列表
	public function getItem($data){
		$where = [];
		if( !empty($data['shop_id']) ){
			$where[] = ['b.shop_id','eq',$data['shop_id']];
		}
		if( !empty($data['type_id']) ){
			$where[] = ['a.type_id','eq',$data['type_id']];
		}
		if( !empty($data['type']) ){
			$where[] = ['a.type','eq',$data['type']];
		}
		if( !empty($data['name']) ){
			$where[] = ['a.title|a.bar_code','like','%'.$data['name'].'%'];
		}
		$where[] = ['a.status','eq','1'];
		$where[] = ['b.status','eq','1'];
		return $this ->alias('a')
				->where($where)
				->join('item_price b','a.id=b.item_id','LEFT')
				->field('a.id,a.title,a.type_id,a.type,a.bar_code,b.shop_id');
	}

	// 获取商品的库存
	public function getItemStock($data){
		if( count($data) == 0 ){
			return 0;
		}

		$itemIds = [];
		foreach ($data as $key => $value) {
			$data[$key]['stock'] = 0; //赋值默认库存0
			$itemIds[] = $value['id'];
		}
		$itemIds = implode(',',$itemIds);
		$where[] = ['item_id','in',$itemIds];
		$stock = Db::name('shop_item')->where($where)->field('item_id,shop_id,stock')->select();
		foreach ($data as $key => $value) {
			foreach ($stock as $k => $v) {
				if( ($value['id'] == $v['item_id']) && ($value['shop_id'] == $v['shop_id']) ){
					$data[$key]['stock'] = $v['stock'];
				}
			}
		}
		return $data;
	}


	// 获取商品的库存不为0或者0的库存
	/*
		type = 1表示库存不为0
		type = 0 表示库存为0
	*/
	public function getItemStock1($data,$type=null){
		if( count($data) == 0 ){
			return 0;
		}

		$itemIds = [];
		foreach ($data as $key => $value) {
			$data[$key]['stock'] = 0; //赋值默认库存0
			$itemIds[] = $value['id'];
		}
		$itemIds = implode(',',$itemIds);
		$where[] = ['item_id','in',$itemIds];
		$stock = Db::name('shop_item')->where($where)->field('item_id,shop_id,stock')->select();
		foreach ($data as $key => $value) {
			foreach ($stock as $k => $v) {
				if( ($value['id'] == $v['item_id']) && ($value['shop_id'] == $v['shop_id']) ){
					$data[$key]['stock'] = $v['stock'];
				}
			}
		}
		$list = [];
		if( $type == 1 ){
			foreach ($data as $key => $value) {
				if( $value['stock']>0 ){
					array_push($list, $value);
				}
			}
		}else{
			foreach ($data as $key => $value) {
				if( $value['stock']<=0 ){
					array_push($list, $value);
				}
			}
		}
		
		return $list;
	}

	//获取仓库商品列表
	public function getgood($data)
	{
		$where = [];
		if( !empty($data['shop_id']) ){
			$where[] = ['b.shop_id','eq',$data['shop_id']];
		}
		if( !empty($data['type_id']) ){
			$where[] = ['a.type_id','eq',$data['type_id']];
		}
		if( !empty($data['type']) ){
			$where[] = ['a.type','eq',$data['type']];
		}
		if( !empty($data['name']) ){
			$where[] = ['a.title|a.bar_code','like','%'.$data['name'].'%'];
		}
		if( !empty($data['stock']) ){
		    if( $data['stock'] == 1 ){
                $where[] = ['b.stock','eq',0];
            }else{
                $where[] = ['b.stock','neq',0];
            }
        }
		return $this ->alias('a')
				->where($where)
				->join('shop_item b','a.id=b.item_id')
				->field('a.id,a.title,a.type_id,a.type,a.bar_code,b.shop_id,b.stock,b.shop_id as shop_ids');
	}

	//获取合计成本
	public function costPrice($data){
		if( count($data) == 0 ){
			return 0;
		}
		$itemIds = [];
		foreach ($data as $key => $value) {
			$itemIds[] = $value['id'];
		}
		$itemIds = implode(',',$itemIds);
		$where[] = ['item_id','in',$itemIds];
		$where[] = ['stock','>',0];
		$stock = Db::name('purchase_price')->where($where)->field('item_id,shop_id,store_cose,md_price,stock')->select();
//        dump($stock);die;
//		dump(array_sum(array_column($stock,'allCost')));die;
		foreach ($data as $key => $value) {
		    $cost_price = 0;
		    $cost_price1 = 0;
            $cost_price2 = 0;
			foreach ($stock as $k => $v) {
				if( ($value['id'] == $v['item_id']) && ($value['shop_id'] == $v['shop_id']) ){
                    $cost_price = bcadd($cost_price,bcmul($v['store_cose'],$v['stock'],2),2);   //进价总成本
                    $cost_price1 = bcadd($cost_price1,bcmul($v['md_price'],$v['stock'],2),2);   //门店总成本
                    $cost_price2 = bcadd($cost_price2,bcmul(bcsub($v['store_cose'],$v['md_price'],2),$v['stock'],2),2);   //公司总成本
				}
			}
            $data[$key]['cost_price'] = $cost_price;
            $data[$key]['cost_price1'] = $cost_price1;
            $data[$key]['cost_price2'] = $cost_price2;
		}
		foreach ( $data as $k=>$v ){
		    if( $v['stock'] == 0 ){
                $data[$k]['gs_single_cost'] = 0;    //公司成本单价
                $data[$k]['single_price1'] = 0;    //门店成本单价
                $data[$k]['gs_cost'] = 0;    //公司合计成本
                $data[$k]['cost_price1'] = 0;    //门店合计成本
                $data[$k]['cost_price'] = 0;    //进价总成本
            }else{
                $data[$k]['gs_cost'] = $v['cost_price2'];    //公司合计成本
                $data[$k]['gs_single_cost'] = bcdiv($v['cost_price2'],$v['stock'],2);    //公司单价成本
                $data[$k]['cost_price1'] = $v['cost_price1'];    //门店合计成本
                $data[$k]['single_price1'] = bcdiv($v['cost_price1'],$v['stock'],2);    //门店单价成本
                $data[$k]['cost_price'] = $v['cost_price'];//进价总成本
            }
        }

		return $data;
	}

	public function getTimeAttr($val){
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

	//获取一级分类名称
	public function getTypeIdAttr($val){
		if( $val == 0 ){
			return '无一级分类';
		}
		$list = Db::name('item_category')->where('id',$val)->value('cname');
		if( $list ){
			return $list;
		}else{
			return '分类出现错误';
		}
	}

	//获取2级分类名称
	public function getTypeAttr($val){
		if( $val == 0 ){
			return '无二级分类';
		}
		$list = Db::name('item_category')->where('id',$val)->value('cname');
		if( $list ){
			return $list;
		}else{
			return '分类出现错误';
		}
	}

	//获取分区名称
	public function getCateIdAttr($val){
		if( $val == 0 ){
			return '暂未选择分区';
		}
		$list = Db::name('item_cate')->where('id',$val)->value('title');
		if( $list ){
			return $list;
		}else{
			return '分区出现错误';
		}
	}

	//获取单位名称
	public function getUnitIdAttr($val){
		if( $val == 0 ){
			return '暂未选择单位';
		}
		$list = Db::name('item_unit')->where('id',$val)->value('title');
		if( $list ){
			return $list;
		}else{
			return '单位出现错误';
		}
	}

	//获取规格名称
	public function getSpecsIdAttr($val){
		if( $val == 0 ){
			return '暂未选择规格';
		}
		$list = Db::name('item_specs')->where('id',$val)->value('title');
		if( $list ){
			return $list;
		}else{
			return '规格出现错误';
		}
	}

	//获取商品库
	public function getItemTypeAttr($val){
		if( $val == 0 ){
			return '商品库错误';
		}
		if( $val == 1 ){
			return '线上商城';
		}
		if( $val == 2 ){
			return '门店商品';
		}
		if( $val == 3 ){
			return '线上/门店商品';
		}
	}

	//分割商品图片
	public function getPicsAttr($val){
		if( $val == '' ){
			return '';
		}
		return explode(',', $val);
	}

	//获取门店的价格
    public function getPriceAttr($val)
    {
        $where = [];
        $where[] = ['status','eq',1];
        $where[] = ['item_id','eq',$val];
        return Db::name('item_price')->where($where)->value('selling_price');   //现在全部门店的价格都是一致  2020/6/15
    }
}