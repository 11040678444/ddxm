<?php
// +----------------------------------------------------------------------
// | 订单商品成本库存表
// +----------------------------------------------------------------------

namespace app\mall_admin_order\model\send_item;
use think\Model;
use think\Db;
use app\mall_admin_order\model\send_item\ShopItem;
class PurchasePrice extends Model
{
    /***
     * @param $data
     * @param  shop_id/item_id/attr_ids/num
     * @return bool
     * 根据商品id与规格组id获取商品库存所消耗的成本
     */
    public function getItemPurchasePrice( $shopId,$data ){
        $ShopItem = new ShopItem();
        foreach ( $data as $k=>$v ){
            $map = [];
            $map[] = ['shop_id','eq',$shopId];
            $map[] = ['item_id','eq',$v['item_id']];
            if( !empty($v['attr_ids']) ){
                $map[] = ['attr_ids','eq',$v['attr_ids']];
            }
            $stock = $ShopItem ->where($map) ->value('stock');
            if( !$stock || $stock < $v['num'] ){
                return json_encode(['code'=>300,'msg'=>'库存不足']);
            }
            $purchase_list = self::getPurchase($map,$v['num']);
            if( $purchase_list === false ){
                return json_encode(['code'=>300,'msg'=>'库存不足']);
            }
            $data[$k]['purchase_list'] = $purchase_list;    //商品消耗的库存表
        }
        foreach ( $data as $k=>$v ){
            $all_oprice = 0;    //总成本
            foreach ( $v['purchase_list'] as $k1=>$v1 ){
                $all_oprice += ($v1['store_cose']*$v1['edit_stock']);
            }
            if( $all_oprice == 0 ){
                $data[$k]['oprice'] = 0;
                $data[$k]['all_oprice'] = $all_oprice;
            }else{
                $data[$k]['oprice'] = sprintf("%.2f",substr(sprintf("%.3f",  ($all_oprice/$v['num'])), 0, -2));
                $data[$k]['all_oprice'] = $all_oprice;
            }
        }
        return json_encode(['code'=>200,'data'=>$data]);
    }

    //根据条件获取商品消耗的
    public function getPurchase($where,$num){
        $where[] = ['stock','>',0];
        $list = $this ->where($where) ->field('id,store_cose,stock')->order('id asc')->select();
        if( count($list) == 0 ){
            return false;
        }
        $purchase_list = [];
        foreach ( $list as $k=>$v ){
            if( $num == 0 ){
                break;  //如果数量小于0则跳出
            }
            $arr = [];
            if( $num <= $v['stock'] ){
                $arr = [
                    'id'    =>$v['id'],     //成本表id
                    'stock' =>$v['stock'] - $num,   //成本表本条数据最终剩余的值
                    'store_cose'    =>$v['store_cose'], //成本
                    'edit_stock' =>$num     //成本表本条数据减了多少成本
                ];
                array_push($purchase_list,$arr);
                $num -= $num;
            }else{
                $arr = [
                    'id'    =>$v['id'],     //成本表id
                    'stock' =>$num - $v['stock'],   //成本表本条数据最终剩余的值
                    'store_cose'    =>$v['store_cose'], //成本
                    'edit_stock' =>$v['stock']     //成本表本条数据减了多少成本
                ];
                array_push($purchase_list,$arr);
                $num -= $v['stock'];
            }
        }
        return $purchase_list;
    }
}