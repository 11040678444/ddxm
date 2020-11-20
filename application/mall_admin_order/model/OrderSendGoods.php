<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/27 0027
 * Time: 下午 17:24
 *
 * 订单发货模型类
 */

namespace app\mall_admin_order\model;
use think\Model;
use app\mall_admin_order\model\Order;
use app\mall_admin_order\controller\Order as C_Order;
use app\mall_admin_order\model\OrderGoods;
use app\mall_admin_order\model\OrderService;
use app\mall_admin_order\controller\OrderSendItem;
use think\Db;

class OrderSendGoods extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 添加商品发货数据
     * @param $data 数据集
     * @return int|string
     */
    public function addSendGoods($data)
    {
        $model = new OrderSendGoods(); 
        $model->startTrans();
        try{
            //查询商品详细数据
            $OrderGoods = new OrderGoods();
            $info = $OrderGoods->whereIn('id',$data['goods_id'])->all();

            //组装表数据,由于可能是多件商品因此这里处理了下数据
            $datas = [];
            foreach ($info as $key=>$value)
            {
                //遍历拼装
                $datas[] = [
                    'service_id'=>$data['service_id'],
                    'og_id'=>$value['id'],//$info['id'],
                    // 'og_id'=>'',//$info['id'],
                    's_subtitle'=>$value['subtitle'],
                    's_attr_pic'=>$value['pic'],
                    's_num'=>$value['num'],
                    's_price'=>$value['real_price'],
                    'item_id'=>$value['item_id'],
                    's_key'=>$value['attr_ids'],
                    's_key_name'=>empty($value['attr_name'])?'单规格':$value['attr_name'],
                    'shop_id'=>$data['shop_id'],
                    'create_time'=>time()
                ];
            }
            //保存
            $this->startTrans();
            
            $res = $this->allowField(true)->insertAll($datas);
            if(!$res){$this->rollback();return false;}

            $this->commit();
            return $res;
        }catch (\Exception $e){
            $this->rollback();$model->rollback();
            return !empty($data['kill']) ? false :return_error('操作成功');
        }
    }

    /**
     * 待发送订单操作
     * @param $operate_type 操作类型
     * @param $id  主键ID
     * @param $order_id order表主键ID
     * @param $os_id 服务表主键ID
     */
    public function operateOrder($operate_type,$id,$order_id,$os_id)
    {
        try{
            //获取当前商品详细
            $info = $this->findSendGoods($id);

            if($info['s_status']!=0)return_error('该订单不能在操作！');

            //如果是多商品换购处理下更新状态主键ID
            if(count($info)>1){
                $where['id'] = array_column($info,'id');
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

                case 'xzfh'://是否选择发货（仓库->流程2）
                    if(!$info['is_xzck']||$info['is_xzfh'])return_error('请先选择仓库或重复操作');
                    $up['is_xzfh'] = 1;
                break;

                case 'xzqr'://是否选择确认（电商部->流程3）
                    if(!$info['is_xzfh']||$info['is_xzqr'])return_error('请先发货或重复操作');
                    $up['xzqr'] = 1;
                    $up['s_status'] = 1;

                    $this->startTrans();
                    $res = $this->operate($up);
                    //判断下是否存在流程异常
                    if(!$info['is_xzck'] || !$info['is_qrdh'] || !$info['xzqr'])
                    {
                        return_error('流程异常');
                    }
                    //到此步骤处理完成，代表流程已走完，直接修改主表订单(order)状态
                    if($res){
                        $order->startTrans();
                        $res = $order->upOrder(['id'=>$order_id,'order_status'=>'1','sendtime'=>time()]);
                    }

                    //调用处理库存、股东数据、订单日志、成本
                    $level = Db::name('shop')->where(['id'=>$info['shop_id']])->value('level');
                    //接口调用数据拼接
                    $postData = [
                        'type'=>1,
                        'shop_id'=>$level!=1 ? $level:'',//1线上仓库，2地方仓库
                    ];
                    foreach ($info as $key=>$value)
                    {
                        //$data
                    }
                    $shopitem = (new OrderSendItem())->orderSendItem($postData);
                    //修改库存
//                    $shopitem = new ShopItem();
//                    $shopitem->startTrans();
//                    if($res)
//                    {
//                        $shopitem->startTrans();
//                        $res = $shopitem->upItemStock($os_id,$info['s_id'],1);
//                    }

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
            $this->startTrans();
            $res = $this->operate($up,$where['id']);

            //修改中间表level
            $OrderService = new  OrderService();
            $OrderService->upOrderService(['id'=>$os_id],['level'=>1]);

            //保存物流信息
            if($operate_type=='xzck' && $res)
            {
                //保存物流数据
                $c_order = new C_Order();
                $res = $c_order->setOrderExpress();
            }

            !empty($res) ? $this->commit():$this->rollback();
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * @param $id 主键ID
     * @return mixedde
     */
    protected function findSendGoods($id)
    {
        try{
            //根据主键id查询发货商品
           $data = OrderSendGoods::whereIn('id',$id)->all();

           return $data;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * @param $up条件数组
     */
    public function operate($up,$where)
    {
        try{
            //根据条件更新流程状态
            $res = OrderSendGoods::whereIn('id',$where)->update($up);
            return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

}