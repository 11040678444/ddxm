<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/21 0021
 * Time: 下午 16:58
 *
 * 订单模型
 */
namespace app\mall_admin_order\model;
use think\Model;
use think\Db;

class Order extends Model
{
    protected $autoWriteTimestamp = true;

    /*
     * 所有订单列表
     * @param $where 查询条件
     * @param $type 查询类型 0待发货、2退货、3换货、all所有
     */
    public function getOrder($type,$where)
    {

        $filed = 'o.id,o.sn,o.amount,FROM_UNIXTIME(o.add_time,"%Y年%m月%d日 %H:%i:%S") add_time,FROM_UNIXTIME(o.paytime,"%Y年%m月%d日 %H:%i:%S") paytime,
                o.realname,o.mobile o_mobile,o.detail_address,o.sendtime,o.order_status,m.nickname,o.shop_id,m.mobile as m_mobile,s.name';

         //$userinfo = cache('f1fe2b401e08cb60b26feeeef9b2ec0bf1fe2b401e08cb60b26feeeef9b2ec0b');//电商部
        $userinfo = cache('a0ff96ebcf0e40e9f529d15fc8b82b8fa0ff96ebcf0e40e9f529d15fc8b82b8f');//仓库
        //dump($userinfo);die;
        //连表查询获取数据
        $db = Order::field($filed)
                ->alias('o')
                ->join([['member m','o.member_id = m.id'],['shop s','m.shop_code = s.code']])
                ->where($where)
                ->order('o.id desc')
                ->group('o.id');

        //不同账号显示不同数据，level：2线上仓库、1地方仓库
        if($userinfo['level']!=2)
        {
            //$where[] = ['os.shop_id','eq',$userinfo['shop_id']];
            $where[] = ['osg.shop_id','eq',$userinfo['shop_id']];
        }

        //根据不同的类型组装查询条件
        switch ($type)
        {
            case '0'://待发货

               //列表查询默认条件
               $where[] = ['o.order_status','eq',0];
               $where[] = ['osg.s_status','eq',0];

                $db->field('os.id os_id');
                $db->join([
                    ['order_service os','o.id = os.order_id and os.os_type = 1'],
                    ['order_send_goods osg','os.id = osg.service_id'],
                    ]);

                $list = $db->where($where)->paginate(10);
                $datas = $list->toArray();

                empty($datas['data']) ? return_succ([],'ok') :'';
                //处理订单主键id
                $ids = array_column($datas['data'],'os_id');

                //根据订单id,查询订单商品购买详细（一个订单可能有多个商品）
                 $s_data = OrderSendGoods::field('osg.*,oe.title,oe.sn,s.name,s_key,s_key_name')
                           ->alias('osg')
                           ->join('order_express oe','osg.id = oe.order_goods_id','left')
                           ->join('order_service os','osg.service_id = os.id')
                           ->join('shop s','osg.shop_id = s.id','left')
                           ->where(['osg.shop_id'=>$userinfo['shop_id']])
                           ->whereIn('service_id',$ids)
                           ->select();

                //合并数据
                foreach ($s_data as $key=>$value)
                {
                    $k = array_search($value['service_id'],$ids);
                    if($k >=0 )
                    {
                        $datas['data'][$k]['order_goods'][] = $value;
                    }
                }
            break;

            case '2'://退货
                $where[] = ['o.order_status','eq','-1'];
                $where[] =['osg.status','eq',0];

                $db->field('os.id os_id');
                $db->join([
                    ['order_service os','o.id = os.order_id and os.os_type = 1'],
                    ['order_refund_goods osg','os.id = osg.refund_id']
                ]);

                $list = $db->where($where)->paginate(10);
                $datas = $list->toArray();
                //没有退单直接返回空对象
                empty($datas['data']) ? return_succ($list,'ok') : '';
                //获取当前订单退货商品详情
                $ids = array_column($datas['data'],'os_id');
                $e_data = OrderRefundGoods::field('*')->whereIn('refund_id',$ids)->select();
                //合并数据
                foreach ($e_data as $key=>$value)
                {
                    $k = array_search($value['id'],$ids);
                    if($k >=0)
                    {
                        $datas['data'][$k]['order_goods'][] = $value;
                    }
                }
            break;

            case '3'://换货
                $where[] = ['o.order_status','eq','10'];
                $where[] =['osg.e_status','eq',0];

                $db->field('os.id os_id');

                $db->join([
                    ['order_service os','o.id = os.order_id and os.os_type = 1'],
                    ['order_send_goods osg','os.id = osg.service_id']
                ]);
                $db->field('os.id os_id');
                $list = $db->where($where)->paginate(10);
                $datas = $list->toArray();
                //没有换货直接返回空对象
                empty($datas['data']) ? return_succ($list,'ok') : '';
                //获取当前订单换货商品详情
                $ids = array_column($datas['data'],'os_id');
                $c_data = OrderRefundGoods::field('*')->whereIn('refund_id',$ids)->select();
                //合并数据
                foreach ($c_data as $key=>$value)
                {
                    $k = array_search($value['id'],$ids);
                    if($k >=0)
                    {
                        $datas['data'][$k]['order_goods'][] = $value;
                    }
                }
            break;

            case 'all'://查询所有

                //优化下查询方式
                if(!empty(input('search_param')))
                {
                    //搜索查询
                    //这里处理下查询兼容
                    if($k = array_search('osg.shop_id',array_column($where,0)))
                    {
                        unset($where[$k]);
                    }
                    $list = $db->alias('o')
                        ->join('order_goods osg','o.id = osg.order_id')
                        ->join('order_send_goods sg','osg.id = sg.og_id','left')
                        ->where($where)
                        ->paginate(10);
                }else{
                    //列表查询
                    $list = $db->alias('o')
                        ->join('order_service os','o.id = os.order_id','left')
                        ->join('order_send_goods sg','os.id = sg.service_id','left')
                        ->paginate(10);
                }

                $datas = $list->toArray();

                empty($datas['data']) ? return_succ([],'ok') :'';
                //处理订单主键id
                $ids = array_column($datas['data'],'id');

                //根据订单id,查询订单商品购买详细（一个订单可能有多个商品）
                $order_goods = OrderGoods::getOrderGoodes($ids);

                //合并数据
                foreach ($order_goods as $key=>$value)
                {
                    $k = array_search($value['order_id'],$ids);
                    if($k >=0 )
                    {
                        $datas['data'][$k]['order_goods'][] = $value;
                    }
                }
            break;
        }

        return $datas;
    }

    /**
     * 数据更新
     * @param $up 更新条件集
     * @return static
     */
    public function upOrder($up)
    {
        try{
           $res = $this->update($up);
           return $res;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}