<?php
// +----------------------------------------------------------------------
// | 订单商品明细表
// +----------------------------------------------------------------------

namespace app\mall_admin_order\model\send_item;

use app\mall_admin_order\model\send_item\PurchasePrice;
use app\mall_admin_order\model\send_item\ShopItem;
use app\mall_admin_order\model\send_item\OrderGoodsCost;
use think\Model;
use think\Db;
class OrderGoods extends Model
{
    //订单商品发货
    public function sendItem( $data )
    {
        if( empty($data['type']) ){
            return json_encode(['code'=>300,'msg'=>'type为空']);
        }
        if( empty($data['data']) || (count($data['data']) == 0) ){
            return json_encode(['code'=>300,'msg'=>'订单明细表id为空']);
        }
        $item = $data['data'];
        $or_go_ids = [];
        foreach ( $item as $k=>$v ){
            if( empty($v['order_goods_id']) ){
                return false;
            }
            if( $data['type'] == 1 && empty($v['oprice']) ){
                return json_encode(['code'=>300,'msg'=>'非线上商城请输入成本']);
            }
            array_push($or_go_ids,$v['order_goods_id']);
        }
        $map = [];
        $map[] = ['a.id','in',implode(',',$or_go_ids)];
        $items = $this
            ->alias('a')
            ->join('order b','a.order_id=b.id')
            ->where($map)
            ->field('a.id,a.order_id,b.shop_id,b.sn as order_sn,b.pay_way,a.num,a.item_id,a.attr_ids')
            ->select();
        if( count($items) == 0 ){
            return json_encode(['code'=>300,'msg'=>'明细表id为空']);
        }
        if( $data['type'] == 1 ){
            //线上商城发货
            foreach ( $items as $k=>$v ){
                foreach ( $item as $k1=>$v1 ){
                    if( $v['id'] == $v1['order_goods_id'] ){
                        $items[$k]['oprice'] = $v1['oprice'];
                        $items[$k]['all_oprice'] = $v1['oprice']*$v['num'];
                    }
                }
            }
            $itemList = $items;
        }else if(  $data['type'] == 2 ){
            //界石仓库发货
            $itemL = (new PurchasePrice()) ->getItemPurchasePrice($data['shop_id'],$items);
            $itemList = json_decode($itemL,true);
            if( $itemList['code'] != 200 ){
                return $itemL;
            }
            $itemList = $itemList['data'];
        }else{
            return json_encode(['code'=>300,'msg'=>'发货类型错误']);
        }
        $statistics_log_data = [];  //股东数据
        $purchase_price_data = [];  //成本表数据
        $order_goods_cost_data = [];  //结账商品成本表数据
        foreach ( $itemList as $k=>$v ){
            $arr = [];
            $arr = [
                'order_id'  =>$v['order_id'],
                'shop_id'  =>$v['shop_id'],
                'order_sn'  =>$v['order_sn'],
                'type'  =>8,
                'data_type'  =>1,
                'pay_way'  =>$v['pay_way'],
                'price'  =>$v['all_oprice'],
                'create_time'  =>time(),
                'title'  =>'商城商品成本',
            ];
            array_push($statistics_log_data,$arr);
            if( !empty($v['purchase_list']) ){
                foreach ( $v['purchase_list'] as $k1=>$v1 ){
                    array_push($purchase_price_data,$v1);   //成本表数据
                    $new_arr = [];
                    $new_arr = [
                        'order_id'  =>$v['order_id'],
                        'order_goods_id'  =>$v['id'],
                        'item_id'  =>$v['item_id'],
                        'shop_id'  =>$data['shop_id'],
                        'num'  =>$v1['edit_stock'],
                        'purchase_price_id'  =>$v1['id'],
                    ];
                    array_push($order_goods_cost_data,$new_arr);
                }
            }
        }
        Db::startTrans();//开启事务
        try{
            //修改明细表
            foreach ( $itemList as $k=>$v ){
                $this ->where('id',$v['id'])->update(['oprice'=>$v['oprice'],'all_oprice'=>$v['all_oprice']]);
            }
            //添加股东数据
            Db::name('statistics_log') ->insertAll($statistics_log_data);
            //修改成本表
            if( $data['type'] == 2 ){
                if( count($purchase_price_data) > 0 ){
                    foreach ( $purchase_price_data as $k=>$v ){
                        Db::name('purchase_price')  ->where('id',$v['id']) ->setField('stock',$v['stock']); //更改成本表库存
                    }
                }
                foreach ( $itemList as $k=>$v ){
                    //更改库存表
                    //更改库存表
                    $map = [];
                    $map[] = ['shop_id','eq',$data['shop_id']];
                    $map[] = ['item_id','eq',$v['item_id']];
                    if( !empty($v['attr_ids']) ){
                        $map[] = ['attr_ids','eq',$v['attr_ids']];
                    }
                    (new ShopItem()) ->where($map) ->setDec('stock',$v['num']);
                }
                (new OrderGoodsCost()) ->insertAll($order_goods_cost_data);   //添加结账商品的成本表
            }
            Db::commit();//提交事务
        }catch (\Exception $e){
            Db::rollback(); //事务回滚
            return json_encode(['code'=>300,'msg' =>$e->getMessage()]);
        }
        return json_encode(['code'=>200,'msg' =>'发货成功']);
    }

    //订单商品退货
    public function refundItem( $data ){
        if( empty($data) || (count($data) == 0) ){
            return json_encode(['code'=>300,'msg'=>'缺少商品明细表ID']);
        }
        $order_goods_ids = [];  //商品明细表id
        $orderGoodsCosts = [];  //界石仓库发货明细表id
        foreach ( $data as $k=>$v ){
            if( empty($v['order_goods_id']) ){
                return json_encode(['code'=>300,'msg'=>'缺少商品明细表ID']);
            }
            if( empty($v['is_xzck']) ){
                return json_encode(['code'=>300,'msg'=>'缺少发货类型']);
            }
            array_push($order_goods_ids,$v['order_goods_id']);
            if( $v['is_xzck'] == 2 ){
                array_push($orderGoodsCosts,$v['order_goods_id']);
            }
        }
        $map = [];
        $map[] = ['a.id','in',implode(',',$order_goods_ids)];
        $items = $this
            ->alias('a')
            ->join('order b','a.order_id=b.id')
            ->where($map)
            ->field('a.id,a.order_id,b.shop_id,b.sn as order_sn,b.pay_way,oprice,all_oprice,a.num,a.item_id,a.attr_ids')
            ->select(); //商品明细

        if( count($orderGoodsCosts) > 0 ){
            //表示有商品从界石发货
            $purchase = (new OrderGoodsCost())
                ->alias('a')
                ->join('purchase_price b','a.purchase_price_id=b.id')
                ->where([['a.order_goods_id','in',implode(',',$orderGoodsCosts)]])
                ->field('a.id,a.order_id,a.order_goods_id,a.shop_id,a.num,purchase_price_id,b.store_cose')
                ->select()
                ->toArray();
            $purchase_price_data = [];  //成本表需要添加的数据
            $shop_item_data = [];  //成本表需要添加的数据
            foreach ( $purchase as $k=>$v ){
                foreach ( $items as $k1=>$v1 ){
                    if( $v['order_goods_id'] == $v1['id'] ){
                        $arr = [];
                        $arr = [
                            'shop_id'   =>$v['shop_id'],
                            'type'   =>7,
                            'pd_id'   =>$v1['order_id'],
                            'item_id'   =>$v1['item_id'],
                            'md_price'   =>$v['store_cose'],
                            'store_cose'   =>$v['store_cose'],
                            'stock'   =>$v['num'],
                            'time'   =>time(),
                            'sort'   =>0,
                            'attr_ids'   =>$v1['attr_ids']
                        ];
                        array_push($purchase_price_data,$arr);

                        $new_arr = [];
                        $new_arr = [
                            'shop_id'   =>$v['shop_id'],
                            'item_id'   =>$v1['item_id'],
                            'stock'   =>$v['num'],
                            'attr_ids'   =>$v1['attr_ids'],
                        ];
                        array_push($shop_item_data,$new_arr);
                    }
                }
            }
        }
        //$purchase_price_data需要添加的库存成本表的商品,$shop_item_data根据商品id和规格id需要增加的库存
        $statistics_log_data = [];  //股东数据
        foreach ( $items as $k=>$v ){
            $arr = [];
            $arr = [
                'order_id'  =>$v['order_id'],
                'shop_id'  =>$v['shop_id'],
                'order_sn'  =>$v['order_sn'],
                'type'  =>8,
                'data_type'  =>2,
                'pay_way'  =>$v['pay_way'],
                'price'  =>'-'.$v['all_oprice'],
                'create_time'  =>time(),
                'title'  =>'商城商品成本退单',
            ];
            array_push($statistics_log_data,$arr);
        }
        $ShopItem = new ShopItem();
        Db::startTrans();   //开启事务
        try{
            Db::name('statistics_log') ->insertAll($statistics_log_data);
            if( isset($purchase_price_data) ){
                (new PurchasePrice()) ->insertAll($purchase_price_data);
            }
            if( isset($shop_item_data) ){
                foreach ( $shop_item_data as $k=>$v ){
                    $map = [];
                    $map[] = ['shop_id','eq',$v['shop_id']];
                    $map[] = ['item_id','eq',$v['item_id']];
                    if( !empty($v['attr_ids']) ){
                        $map[] = ['attr_ids','eq',$v['attr_ids']];
                    }
                    $ShopItem ->where($map)->setInc('stock',$v['stock']);
                }
            }
            Db::commit();//提交事务
        }catch (\Exception $e){
            Db::rollback(); //事务回滚
            return json_encode(['code'=>300,'msg' =>$e->getMessage()]);
        }
        return json_encode(['code'=>200,'msg' =>'退货成功']);
    }
}