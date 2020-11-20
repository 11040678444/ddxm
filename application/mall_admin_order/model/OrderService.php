<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/24 0024
 * Time: 下午 18:05
 *
 * 订单服务类型模型
 */

namespace app\mall_admin_order\model;
use think\Model;
use app\mall_admin_order\model\OrderSendGoods;
use app\mall_admin_order\model\OrderRefundGoods;
use app\mall_admin_order\model\OrderChangeGoods;

class OrderService extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * @param $data 保存的数据参数（数组）
     * @return bool
     */
    public function addService($data)
    {

        try{
            //创建服务表数据
            $res = $this->allowField(true)->save($data);

            //获取自增id到数组后面要用直接取
            $id = $this->id;

            //调用处理发货、退单、换货类
            if(!empty($data['os_type']) && $res)
            {
                $data['service_id'] = $id;
                switch ($data['os_type'])
                {
                    case 1://发货
                        $model = new OrderSendGoods();
                        //$res = $model->sendGoods($data);这个方法可以留到后面发货使用
                        $res = $model->addSendGoods($data);
                        break;

                    case 2://退货
                        $model = new OrderRefundGoods();
                        $res = $model->addRefundGoods($data);
                        break;

                    case 3://换货
                        $model =new OrderChangeGoods();
                        $model->startTrans();
                        $res = $model->addChangeGoods($data);
                        //if(!$res){$model->rollback();return false;}
                        break;
                }
            }
              //事物处理
            !empty($res) ? $this->commit() : $this->rollback();
            return $id;
        }catch (\Exception $e){
            $this->rollback();
            return !empty($data['kill']) ? false :return_error('操作成功');
        }
    }

    /**
     * 获取订单 退货、换货
     * @param $where查询条件
     */
    public function getOrder($where)
    {
        try{

            $field = 'o.id,o.sn,o.amount,FROM_UNIXTIME(o.add_time,"%Y年%m月%d日 %H:%i:%S") add_time,FROM_UNIXTIME(o.paytime,"%Y年%m月%d日 %H:%i:%S") paytime,
                o.realname,o.mobile o_mobile,o.detail_address,o.sendtime,o.order_status,m.nickname,o.shop_id,m.mobile as m_mobile,s.name,os.id os_id';

            $list = $this->alias('os')
                    ->field($field)
                    ->join([['order o','os.order_id = o.id'],['member m','o.member_id = m.id'],['shop s','m.shop_code = s.code']])
                    ->where($where)
                    ->paginate(10);

            $datas = $list->toArray();

            empty($datas['data']) ? return_succ($datas,''):'';

            $ids = array_column($datas['data'],'id');

            //根据订单id,查询订单商品购买详细（一个订单可能有多个商品）
            $order_goods = $order_goods = OrderGoods::getOrderGoodes($ids);
//            $model = model('OrderRefundGoods');
//            $r_list = $model->getOrderRefundDetail($ids);
//            dump($r_list);die;
            //合并数据
            foreach ($order_goods as $key=>$value)
            {
                $k = array_search($value['refund_id'],$ids);
                if($k >=0 )
                {
                    $datas['data'][$k]['order_goods'][] = $value;
                }
            }

            return $datas;

        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}