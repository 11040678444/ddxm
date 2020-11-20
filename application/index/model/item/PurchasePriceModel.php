<?php

// +----------------------------------------------------------------------
// | 普通商品的真实消耗库存
// +----------------------------------------------------------------------
namespace app\index\model\item;

use think\Model;
use think\Db;

class PurchasePriceModel extends Model
{
	protected $table = 'ddxm_purchase_price';

	////计算商品的平均成本价
	/**
		$data = array(
			0=>array(
				'id'	=>1,	//商品id
				'num'	=>3
			),
		);
	
		return
		data 已经有成本价的数据
		costPrices ddxm_purchase_price需要修改的数据
	*/
	public function itemCostPrice($data,$shop_id){
		foreach ($data as $key => $value) {
			$where = [];
			$where[] = ['item_id','eq',$value['id']];
			$where[] = ['shop_id','eq',$shop_id];
			$where[] = ['stock','neq',0];
			$data[$key]['cos'] = $this ->where($where)->order('time asc')->field('id,item_id,md_price,store_cose,stock')->select()->toArray();
		}

		foreach ($data as $key => $value) {
			$data[$key]['all_cost_price'] = 0;	//所有数量的总成本价
			$data[$key]['md_cost_price'] = 0;	//门店所有数量的总成本价
			$data[$key]['gs_cost_price'] = 0;	//公司所有数量的总成本价
			$stockIds = [];
			$num = [];
			$cost = [];
			foreach ($value['cos'] as $k => $v) {
				if( $value['num'] > $v['stock'] ){
					$value['num'] = $value['num']-$v['stock']; 
					$a = array(
							'stockIds'	=>$v['id'],
							'item_id'	=>$v['item_id'],
							'num'		=>$v['stock'],
							'md_price'		=>$v['md_price'],
							'store_cose'		=>$v['store_cose'],
							'gs_price'		=>bcsub($v['store_cose'],$v['md_price'],2),
						);
					array_push($cost, $a);
				}else{
					$a = array(
							'stockIds'	=>$v['id'],
							'num'		=>$value['num'],
							'item_id'	=>$v['item_id'],
                            'md_price'		=>$v['md_price'],
                            'store_cose'		=>$v['store_cose'],
                            'gs_price'		=>bcsub($v['store_cose'],$v['md_price'],2),
						);
					array_push($cost, $a);
					$data[$key]['cost'] = $cost;
					break;
				}
			}
		}
		$costPrices = [];	//ddxm_purchase_price需要消耗库存的数据
		foreach ($data as $key => $value) {
			foreach ($value['cost'] as $k => $v) {
				array_push($costPrices, $v);
			}
			unset($data[$key]['cost']);
			unset($data[$key]['cos']);
		}

		//计算商品成本
		foreach ( $data as $k=>$v )
        {
            $all_cost_price = 0;
            $md_cost_price = 0;
            $gs_cost_price = 0;
            foreach ( $costPrices as $k1=>$v1 )
            {
                if ( $v['id'] == $v1['item_id'] )
                {
                    $all_cost_price = bcadd($all_cost_price,bcmul($v1['num'],$v1['store_cose'],2),2);
                    $md_cost_price = bcadd($md_cost_price,bcmul($v1['num'],$v1['md_price'],2),2);
                    $gs_cost_price = bcadd($gs_cost_price,bcmul($v1['num'],$v1['gs_price'],2),2);
                }
            }
            $data[$k]['all_cost_price'] = $all_cost_price;  //总的成本
            $data[$k]['md_cost_price'] = $md_cost_price;    //门店总成本
            $data[$k]['gs_cost_price'] = $gs_cost_price;    //公司总成本
//            $data[$k]['cost_price'] = bcdiv($all_cost_price,$v['num'],2);    //暂时先计算进价的单个成本
            $data[$k]['cost_price'] = bcdiv($md_cost_price,$v['num'],2);    //暂时先计算门店的单个成本
        }
		return ['data'=>$data,'costPrices'=>$costPrices];
	}
}
