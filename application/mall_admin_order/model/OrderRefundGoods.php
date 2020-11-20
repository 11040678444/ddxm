<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/25 0025
 * Time: 上午 11:22
 *
 * 商品退货模型类
 */

namespace app\mall_admin_order\model;
use think\Model;
use app\mall_admin_order\model\OrderGoods;

class OrderRefundGoods extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 商品退货数据添加
     * @param $data 数据集
     * @return bool
     */
    public function addRefundGoods($data)
    {
        try{
            //查询商品详细数据
            $OrderGoods = new OrderGoods();
            $info = $OrderGoods->whereIn('id',$data['goods_id'])->all();

            //组装表数据,由于可能是多件商品因此这里处理了下数据
            $data = [];
            if(is_array($info['id']))
            {
                //多件商品遍历拼装
                foreach ($info['id'] as $key=>$value)
                {
                    $data[]= [
                        'refund_id'=>$data['service_id'],
                        'og_id'=>$value,
                        'r_subtitle'=>$info['subtitle'],
                        'r_attr_pic'=>$info['pic'],
                        'r_num'=>$info['num'],
                        'r_price'=>$info['real_price'],
                        'is_service_goods'=>0,
                        's_id'=>$info['item_id']
                    ];
                }
            }else{
                //单件商品直接接收
                $data[]= [
                    'refund_id'=>$data['service_id'],
                    'og_id'=>$info['id'],
                    'r_subtitle'=>$info['subtitle'],
                    'r_attr_pic'=>$info['pic'],
                    'r_num'=>$info['num'],
                    'r_price'=>$info['real_price'],
                    'is_service_goods'=>0,
                    's_id'=>$info['item_id']
                ];
            }


            //保存
            $this->startTrans();
            $res = $this->allowField(true)->insertAll($data);
            if(!$res){$this->rollback();return false;}

            $this->commit();
            return $res;
        }catch (\Exception $e){
            abort($e->getMessage());
        }
    }

    /**
     * 待发送订单操作
     * @param $operate_type 操作类型
     * @param $id  表主键ID
     * @param $order_id order表主键ID
     * @param $os_id OrderService主键ID
     */
    public function operateOrder($operate_type,$id,$order_id,$os_id)
    {
        try{
            //$id = [1,2];
            //获取当前商品详细
            $info = $this->findRefundGoods($id);

            if($info['status']!=0)return_error('该订单不能在操作！');

            //如果是多商品换购处理下更新状态主键ID
            if(count($info)>1){
                $where['id'] = ['in',array_column($info,'id')];
            }else{
                $where['id'] = $id;
            }

            $order = new Order();
            //根据不同的处理类型判断是否越级操作
            switch ($operate_type)
            {
                case 'xzck'://是否选择仓库（电商部->流程1）
                    if(empty($info)||$info['is_xzck'])return_error('对象不存在或重复操作');
                    $up['is_xzck'] = 1;
                    break;

                case 'qrdh'://是否确认到货（仓库->流程2）
                    if(!$info['is_xzck']||$info['is_qrdh'])return_error('请先选择仓库或重复操作');
                    $up['is_qrdh'] = 1;
                    break;

                case 'cwqr'://是否财务确认（财务->流程3)
                    if(!$info['is_qrdh']||$info['is_cwqr'])return_error('请先发货或重复操作');
                    $up['xzqr'] = 1;
                    $up['status'] = 1;

                    $this->startTrans();
                    $res = $this->operate($up);
                    //判断下是否存在流程异常
                    if(!$info['is_xzck'] || !$info['is_qrdh']||!$info['cwqr'])
                    {
                        return_error('流程异常');
                    }
                    //到此步骤处理完成，代表流程已走完，直接修改主表订单(order)状态
                    if($res){
                        $order->startTrans();
                        $res = $order->upOrder(['id'=>$order_id,'order_status'=>'-2','returntime'=>time()]);
                    }
                    //修改库存
                    $shopitem = new ShopItem();
                    $shopitem->startTrans();
                    if($res)
                    {
                        $res = $shopitem->upItemStock($os_id,$info['s_id']);
                    }
                    //执行事务
                    if($res)
                    {
                        $this->commit();$order->commit();$shopitem->commit();
                    }else{
                        $this->rollback();$order->rollback();$shopitem->rollback();
                    }
                    return $res;exit;
                    break;
                default:
                    return_error('参数错误');
                    exit;
            }

            //调用执行
            $res = $this->operate($up,$where);
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * @param $id 主键ID
     * @return mixedde
     */
    protected function findRefundGoods($id)
    {
        try{
            //根据主键id查询发货商品
            $data = OrderRefundGoods::get($id);

            return $data;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 数据状态更新
     * @param $up条件数组
     * @param $where更新条件
     */
    public function operate($up,$where)
    {
        try{
            //根据条件更新流程状态
            $res = $this->where($where)->update($up);
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}