<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/25 0025
 * Time: 下午 15:18
 *
 * 商品换货模块类
 */

namespace app\mall_admin_order\model;
use think\Model;
use app\mall_admin_order\model\Order;
use app\mall_admin_order\model\OrderGoods;

class OrderChangeGoods extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 添加商品换货数据
     * @param $data 数据集
     * @return int|string
     */
    public function addChangeGoods($data)
    {
            $model = model('OrderGoods');$model->startTrans();
           try{
               //查询商品详细数据
               $OrderGoods = new OrderGoods();
               $info = $OrderGoods->whereIn('id',$data['goods_id'])->all();//OrderGoods::get($data['goods_id']);

               //组装表数据,由于可能是多件商品因此这里处理了下数据
               $datas = [];
               if(is_array($info['id']))
               {
                   //多件商品遍历拼装
                   foreach ($info['id'] as $key=>$value)
                   {
                       $datas[]= [
                           'service_id'=>$data['service_id'],
                           'og_id'=>$value,
                           'e_subtitle'=>$info['subtitle'],
                           'e_attr_pic'=>$info['pic'],
                           'e_num'=>$info['num'],
                           'e_price'=>$info['real_price'],
                           'item_id'=>$info['item_id'],
                           'e_item_id'=>$data['e_item_id'],
                           'e_price_diff'=>$data['e_price_diff'],
                       ];
                   }
               }else{
                   //单件商品直接接收
                   $datas[]= [
                       'service_id'=>$data['service_id'],
                       'og_id'=>$info['id'],
                       'e_subtitle'=>$info['subtitle'],
                       'e_attr_pic'=>$data['pic'],
                       'e_num'=>$data['num'],
                       'e_price'=>$info['real_price'],
                       'item_id'=>$info['item_id'],
                       'e_item_id'=>$data['e_item_id'],
                       'e_price_diff'=>$data['e_price_diff'],
                   ];
               }

               //保存
               $this->startTrans();
               $res = $this->allowField(true)->insertAll($datas);
               if(!$res){$this->rollback();return false;}

               //换货成功,添加一条商品到订到详细下order_goods
               $res = $model->addChangeGoods($info['id'],$data['e_item_id'],$data['num']);
               if(!$res){$model->rollback();return false;}

               $this->commit();$model->commit();
               return $res;
           }catch (\Exception $e){
               $this->rollback();$model->rollback();
               return_error($e->getMessage());
           }
    }

    /**
     * 换货订单操作
     * @param $operate_type 操作类型
     * @param $id  主键ID
     * @param $order_id order表主键ID
     * @param $os_id  服务表主键ID
     */
    public function operateOrder($operate_type,$id,$order_id,$os_id)
    {
        try{
            //获取当前商品详细
            $info = $this->findChangeGoods($id);

            if($info['e_status']!=0)return_error('该订单不能在操作！');

            //如果是多商品换购处理下更新状态主键ID
            if(count($info)>1){
                $where['id'] = ['in',array_column($info,'id')];
            }else{
                $where['id'] = $id;
            }

            //首先判断下是否存在差价
            if($info['is_sfcj'])
            {
                //需要财务审核的流程
                switch ($operate_type)
                {
                    case 'xzck'://是否选择仓库（电商部->流程1）
                        if(empty($info)||$info['is_xzck'])return_error('对象不存在或重复操作');
                        $up['is_xzck'] = 1;
                        break;

                    case 'cwqr'://是否财务确认（财务->流程3)
                        if(!$info['is_sfcj']||$info['is_cwqr'])return_error('不是差价订单或重复操作');
                        $up['is_cwqr'] = 1;
                        break;

                    case 'qrdh'://是否确认到货（仓库->流程4）
                        if(!$info['is_cwqr']||$info['is_qrfh'])return_error('请财务确认或重复操作');
                        $up['is_qrfh'] = 1;
                        break;

                    case 'qrfh'://是否确认发货(电商部->流程5)
                        if(!$info['is_qrdh']||$info['is_qrfh'])return_error('请仓库确认发货或重复操作');
                        $up['is_qrfh'] = 1;
                        //调用公共函数处理
                        $res = $this->commonException($info,$order_id,$os_id,$up);
                        return $res;exit;
                        break;

                    default:
                        return_error('参数错误');
                        exit;
                }
            }else{
                //执行流程1、4、5
                switch ($operate_type)
                {
                    case 'xzck'://是否选择仓库（电商部->流程1）
                        if(empty($info)||$info['is_xzck'])return_error('对象不存在或重复操作');
                        $up['is_xzck'] = 1;
                        break;

                    case 'qrdh'://是否确认到货（仓库->流程4）
                        if(!$info['is_xzck']||$info['is_qrdh'])return_error('请先选择仓库或重复操作');
                        $up['is_qrdh'] = 1;
                        break;

                    case 'qrfh'://是否确认发货(电商部->流程5)
                        if(!$info['is_qrdh']||$info['is_qrfh'])return_error('请先发货或重复操作');
                        $up['is_qrfh'] = 1;

                        //调用公共函数处理
                        $res = $this->commonException($info,$order_id,$os_id,$up);
                        return $res; exit;
                        break;
                    default:
                        return_error('参数错误');
                        exit;
                }
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
    protected function findChangeGoods($id)
    {
        try{
            //根据主键id查询发货商品
            $data = OrderChangeGoods::get($id);

            return $data;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * @param $up条件数组
     */
    protected function operate($up)
    {
        try{
            //根据条件更新流程状态
            $res = $this->update($up);
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    //公共处理
    private function commonException($info,$order_id,$os_id,$up)
    {
        try{
            $this->startTrans();
            $up['e_status'] = 1;
            $res = $this->operate($up);
            //判断下是否存在流程异常
            if(!$info['is_xzck'] || !$info['is_qrdh'])
            {
                return_error('流程异常');
            }
            //到此步骤处理完成，代表流程已走完，直接修改主表订单(order)状态
            $order = new Order();
            $order->startTrans();
            if($res){
                $res = $order->upOrder(['id'=>$order_id,'order_status'=>'11']);
            }
            //修改库存
            $shopitem = new ShopItem();
            $shopitem->startTrans();
            if($res)
            {
                $res = $shopitem->upItemStock($os_id,$info['s_id'],0,$info['e_num']);
            }
            //执行事务
            if($res)
            {
                $this->commit();$order->commit();$shopitem->commit();
            }else{
                $this->rollback();$order->rollback();$shopitem->rollback();
            }
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}