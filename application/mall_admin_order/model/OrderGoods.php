<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/23 0023
 * Time: 上午 10:34
 *
 * 订单详细模型
 */

namespace app\mall_admin_order\model;
use think\Model;
use think\Db;
class OrderGoods extends Model
{

    protected $autoWriteTimestamp = true;

    /**
     * @param $ids 订单主表id(可以是数组)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOrderGoodes($ids)
    {
       try{
           empty($ids)?return_error('参数错误'):'';

           $field = 'id,subtitle,(real_price*num) sum_real_price,num,order_id,oprice,item_id';

           //根据ids查询出对应订单的商品
           $list = OrderGoods::field($field)->whereIn('order_id',$ids)->select()->toArray();

           return $list;
       }catch (\Exception $e){
           abort($e->getMessage());
       }
    }

    /**
     * @param $order_id 订单id
     * @return mixed 订单详细查询
     */
    public static function getOrderDetail($order_id)
    {
        try{
            $field = 'o.id,o.sn,FROM_UNIXTIME(o.paytime,"%Y年%m月%d日 %H:%i:%S") paytime,m.shop_code,m.nickname,ml.level_name,mm.money,s.name,o.order_status,waiter';
            $data = Order::field($field)
                ->alias('o')
                ->join([['member m','o.member_id = m.id'],['member_level ml','m.level_id = ml.id'],
                        ['member_money mm','o.member_id = mm.member_id'],['shop s','m.shop_code = s.code']
                ])
                ->where(['o.id'=>$order_id])
                ->find();

            //调用商品详细
            $orderGoodes = self::getOrderGoodes($data['id'],$data['order_status']);

            //合并数据数组
            $data['order_goods'] = $orderGoodes;
            $data['sum_oprice'] = array_sum(array_column($orderGoodes,'oprice'));

            return $data;
        }catch (\Exception $e){
            abort($e->getMessage());
        }
    }

    /**
     * 添加更换商品到订单商品详细
     * @param $id 原订单商品详细主键id
     * @param $item_id  更换商品id
     * @param $num  更换数量
     */
    public  function addChangeGoods($id,$item_id,$num)
    {
        try{
            //获取商品详细记录
            $info = $this->get($id);
            $change_item = $this->getItemInfo($item_id);

            if(!empty($info))
            {
                //组装商品数据
                $data = [
                    'order_id'=>$info['order_id'],
                    'subtitle'=>$change_item['title'],
                    'pic'=>$change_item['imgurl'],
                    'item_id'=>$item_id,
                    'num'=>$num,
                    'price'=>$change_item['price'],
                    'oprice'=>$change_item['cost'],
                    'all_oprice'=>$change_item['cost']*$num,
                    'real_price'=>$change_item['price'],//实际支付价钱，还需了解下‘modify_price’修改价格是怎么回事
                    'status'=>1,
                    'ratio'=>$change_item['commission']
                    //'two_ratio'  什么是er佣金
                ];

                //执行新增
                $this->startTrans();
                $res = $this->allowField(true)->insert($data);

                if($res)
                {
                    $this->commit();return $res;
                }else{
                    $this->rollback();return false;
                }
            }
        }catch (\Exception $e){
            $this->rollback();
            return_error(abort($e->getMessage()));
        }
    }

    //临时写一个获取单件商品详细查询用方便测试，合并代码时在对应model中处理
    private function getItemInfo($id)
    {
        $info = Db::name('item')
                ->alias('i')
                ->field('i.title,sgp.cost,sgp.imgurl,price,commission')
                ->join([['specs_goods_price sgp','i.id = sgp.gid','left']])
                ->where(['i.id'=>$id,'i.item_type'=>['in','1,3'],'i.status'=>1,'sgp.status'=>1])
                ->find();

        return $info;
    }


    public function sendGoods($data)
    {
        try{

            //判断下仓库类型是否为电商部仓库
            $is_true = Db::name('shop')->where(['code'=>0])->count();

            //获取库存数量
            $is_send = Db::name('shop_item')->where(['shop_id'=>$data['shop_id'],'item_id'=>'2153'])->value('stock');

            //判断库存是否够发货
            ($is_send-$data['num'])<0 ? return_error('库存不足') : '';


            if($is_true)
            {
                //修改商品发货状态
                $this->startTrans();
                $res = $this->update(['id'=>$data['goods_id'],'deliver_status'=>1]);
                if(!$res){$this->rollback();return false;}

                //修改订单主表发货状态
                if($res)
                {
                    Order::startTrans();
                    $res = Order::where(['id'=>$data['order_id']])->update(['sendtime'=>time(),'order_status'=>1]);
                    if(!$res){Order::rollback();return false;}
                }

                //修改order_service确认状态
                if($res)
                {
                    OrderService::startTrans();
                    $res = OrderService::update(['id'=>$data['service_id'],'order_status'=>1,'is_confirm'=>1]);
                    if(!$res){OrderService::rollback();return false;}
                }
                //扣除库存
                if($res)
                {
                    //item_id 等待仓功能修改好了变为动态的
                    $res = Db::name('shop_item')->where(['shop_id'=>$data['shop_id'],'item_id'=>'2153'])->setDec('stock',$data['num']);
                }
            }

            if($res)
            {
                $this->commit();Order::commit();OrderService::commit();
            }
            return $res;
        }catch (\Exception $e){
            $this->rollback();Order::rollback();OrderService::rollback();
            abort($e->getMessage());
        }
    }
}