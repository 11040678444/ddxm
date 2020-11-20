<?php

// +----------------------------------------------------------------------
// | 商品库存
// +----------------------------------------------------------------------
namespace app\stock\model\shop;

use think\Model;
use think\Db;
class ShopItem extends Model
{
   public function stock_list($data){
       if( !empty($data['bar_code']) && empty($data['item_type']) ){
           return json(['code'=>200,'msg'=>'条形码搜索时必须选择线上或线下商品']);
       }
       $page = !empty($data['page'])?$data['page']:1;
       $limit = !empty($data['limit'])?$data['limit']:10;
       $where = [];
       if( !empty($data['item_name']) ){
           $where[] = ['b.title','like','%'.$data['item_name'].'%'];
       }
       if ( isset($data['stock']) && ($data['stock'] == 1) ){
           //库存大于0
           $where[] = ['a.stock','>',0];
       }elseif ( isset($data['stock']) && ($data['stock'] == 2) ){
           //库存等于0
           $where[] = ['a.stock','=',0];
       }
       if( !empty($data['bar_code']) && $data['item_type'] == 2 ){
           $where[] = ['b.bar_code','like','%'.$data['bar_code'].'%'];
       }
       if ( isset($data['item_type']) && ($data['item_type'] == 1) ){
           $where[] = ['b.item_type','eq',1];
       }elseif ( isset($data['item_type']) && ($data['item_type'] == 2) ){
           $where[] = ['b.item_type','in','2,3'];
       }
       if ( isset($data['shop_id']) && $data['shop_id'] != '' ){
           $where[] = ['a.shop_id','eq',$data['shop_id']];
       }
       if( empty($data['bar_code']) || $data['item_type'] == 2 ){
           $list = $this ->alias('a')
               ->join('item b','a.item_id=b.id')
               ->where($where)
               ->field('a.shop_id as shop_name,a.shop_id,a.item_id,a.stock,a.attr_ids,a.attr_name,b.title,b.item_type,b.bar_code')
               ->page($page,$limit)
               ->select();
           if( isset($data['stock']) && ($data['stock'] == 1) ) {
               $list1 = $this->alias('a')
                   ->join('item b', 'a.item_id=b.id')
                   ->where($where)
                   ->field('a.shop_id as shop_name,a.shop_id,a.item_id,a.stock,a.attr_ids,a.attr_name,b.title,b.item_type,b.bar_code')
                   ->select();
           }
           $count = $this ->alias('a')
               ->join('item b','a.item_id=b.id')
               ->where($where)
               ->count();
       } else{
            if( $data['item_type'] == 1 ){
                //查询线上商品
                $list = $this ->alias('a')
                    ->join('item b','a.item_id=b.id')
                    ->where($where)
                    ->where('a.item_id', 'IN', function($query) use (&$name) {
                        $query->table('ddxm_specs_goods_price')->whereOr('bar_code','eq',$name)
                            ->where('status','eq',1)
                            ->field('gid');
                    })
                    ->field('a.shop_id as shop_name,a.shop_id,a.item_id,a.stock,a.attr_ids,a.attr_name,b.title,b.item_type')
                    ->page($page,$limit)
                    ->select();
                if( isset($data['stock']) && ($data['stock'] == 1) ){
                    $list1 = $this ->alias('a')
                        ->join('item b','a.item_id=b.id')
                        ->where($where)
                        ->where('a.item_id', 'IN', function($query) use (&$name) {
                            $query->table('ddxm_specs_goods_price')->whereOr('bar_code','eq',$name)
                                ->where('status','eq',1)
                                ->field('gid');
                        })
                        ->field('a.shop_id as shop_name,a.shop_id,a.item_id,a.stock,a.attr_ids,a.attr_name,b.title,b.item_type')
                        ->select();
                }
                $count = $this ->alias('a')
                    ->join('item b','a.item_id=b.id')
                    ->where($where)
                    ->where('a.item_id', 'IN', function($query) use (&$name) {
                        $query->table('ddxm_specs_goods_price')->whereOr('bar_code','eq',$name)
                            ->where('status','eq',1)
                            ->field('gid');
                    })
                    ->count();
            }
       }
       //$list 数据
       foreach ( $list as $k=>$v ){
           if( $v['item_type'] == 1 ){
               $list[$k]['bar_code'] = Db::name('specs_goods_price')->where('gid',$v['attr_ids'])->where('key',$v['attr_ids'])
                   ->where('status',1)->value('bar_code');
           }
       }
       //查询成本
        foreach ( $list as $k=>$v ){
            $map = [];
            $map[] = ['item_id','=',$v['item_id']];
            if( !empty($v['attr_ids']) ){
                $map[] = ['attr_ids','=',$v['attr_ids']];
            }
            $map[] = ['shop_id','=',$v['shop_id']];
            $all_cost = self::getCost($map);
            if( ($all_cost == 0) || ($v['stock'] == 0) ){
                $list[$k]['one_cost'] = 0;
            }else{
                $list[$k]['one_cost'] = sprintf("%.2f",substr(sprintf("%.3f",  ($all_cost/$v['stock'])), 0, -2));
            }
            $list[$k]['all_cost'] = $all_cost;
        }

        //$list 为展示数据，$list1 为查询总成本
        if( count($where) > 0 ){
            if( isset($data['stock']) && ($data['stock'] == 2) ){
                $all_costs = 0;
            }else{
                if( count($list1)>0 ){
                    foreach ( $list1 as $k=>$v ){
                        $where = [];
                        $where[] = ['item_id','=',$v['item_id']];
                        if( !empty($v['attr_ids']) ){
                            $where[] = ['attr_ids','=',$v['attr_ids']];
                        }
                        $where[] = ['shop_id','=',$v['shop_id']];
                        $all_cost = self::getCost($where);
                        $list1[$k]['all_cost1'] = $all_cost;
                    }
                    $all_costs = 0;
                    foreach ( $list1 as $k=>$v ){
                        $all_costs += $v['all_cost1'];
                    }
                }else{
                    $all_costs = 0;
                }
            }
        }else{
            $all_costs = 0;
        }
        return json(['code'=>200,'msg'=>'获取成功','all_costs'=>$all_costs,'count'=>$count,'data'=>$list]);
   }

   //获取成本
    public function getCost($where){
       $where[] = ['stock','>',0];
       $list = Db::name('purchase_price')->where($where) ->select();
       if( count($list) >0 ){
            $all_cost = 0;  //总成本
            foreach ( $list as $k=>$v ){
                $all_cost += ($v['store_cose'] * $v['stock']);
            }
            return $all_cost;
       }
       return 0;
    }

   //店铺名
    public function getShopNameAttr($val){
        if( !$val ){
            return '门店错误';
        }
        return Db::name('shop')->where('id',$val)->value('name');
    }
}