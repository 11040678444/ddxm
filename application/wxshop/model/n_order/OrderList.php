<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/11 0011
 * Time: 下午 14:56
 * 订单表(RenKunHong 作于订单列表使用)
 */
namespace app\wxshop\model\n_order;
use think\Model;
class OrderList extends Model
{
    protected $table = 'ddxm_n_order';
    protected $autoWriteTimestamp = true;

    /***
     * 获取订单列表
     * @param $data array
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($data)
    {
        if ( !empty($data['page']) && !empty($data['limit']) )
        {
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '1,10';
        }
        $where = [];
        //搜索功能
        if ( isset($data['o_pay_status'])  )
        {
            //支付状态
            $where['o.o_pay_status'] = $data['o_pay_status'];
        }
        if ( isset($data['o_id']) && $data['o_id'] != '' )
        {
            //订单ID
            $where['o.id'] = $data['o_id'];
        }

        if ( isset($data['og_id']) && $data['og_id'] != '' )
        {
            //订单商品主键ID
            $where['og.id'] = $data['og_id'];
        }

        if ( isset($data['o_is_delete']) && $data['o_is_delete'] != '' )
        {
            //订单删除状态
            $where['o.is_delete'] = $data['o_is_delete'];
        }

        if ( !empty( $data['o_pay_type'] ) )
        {
            //支付方式
            $where['o.o_pay_type'] = $data['o_pay_type'];
        }

        if ( isset($data['o_type']) && $data['o_type'] != '' )
        {
            //订单类型
            $where['o.o_type'] = $data['o_type'];
        }

        if ( isset($data['warehouse_id']) && $data['warehouse_id'] != '' )
        {
            //订单归属门店
            $where['o.warehouse_id'] = $data['warehouse_id'];
        }

        if ( !empty($data['o_pay_time_start']) && empty($data['o_pay_time_end']) )
        {
            //订单支付时间
            $where['o.o_pay_time'] = ['>=',strtotime($data['o_pay_time_start'])];
        }

        if ( !empty($data['o_pay_time_end']) && empty($data['o_pay_time_start']) )
        {
            //订单支付时间
            $where['o.o_pay_time'] = ['<=',strtotime($data['o_pay_time_end'].' 23:59:59')];
        }

        if ( !empty($data['o_pay_time_start']) && !empty($data['o_pay_time_end']) )
        {
            //订单支付时间
            $where['o.o_pay_time'] = ['between',strtotime($data['o_pay_time_start']).','.strtotime($data['o_pay_time_end'].' 23:59:59')];
        }

        if ( !empty($data['memberSearch']) )
        {
            //收货人信息：收货人姓名、收货手机号码、收货地址
            $where['o.o_receiving_realname|o.o_receiving_mobile|o.o_receiving_address'] = ['like','%'.$data['memberSearch'].'%'];
        }

        if ( !empty($data['orderSearch']) )
        {
            //订单信息：订单编号、商品名称、商品条形码
            $where['o.o_sn|og.og_goods_title|og.og_goods_code'] = ['like','%'.$data['orderSearch'].'%'];
        }

        if ( isset($data['o_send_status']) && $data['o_send_status'] != '' )
        {
            //仓库：发货状态
            $where['o.o_send_status'] = $data['o_send_status'];
        }

        if ( !empty($data['orderAllStatus']) )
        {
            //商城状态：1待发货、2待收货
            $where['o.o_pay_status'] = 1;
            if ($data['orderAllStatus'] == 1)
            {
                $where['o.o_send_status'] = ['eq',0];
            }else if ($data['orderAllStatus'] == 2)
            {
                $where['o.o_send_status'] = ['neq',0];
                $where['o.o_receiving_status'] = ['neq',2];
            }

            //$data['orderAllStatus'] == 1 ? $where['o.o_send_status']=['in','0,1']:$where['o.o_receiving_status']=['in','0,1'];
        }
        if ( !empty($data['member_id']) )
        {
            $where['o.member_id'] = $data['member_id'];
        }

        $field = 'o.id,o.o_sn,o.o_type,o.o_type as type_name,o.o_freight,o.o_pay_type,o.o_pay_type as pay_type_name,
        o.o_pay_time,o.o_total_num,o.o_pay_status,
        o.o_pay_status as pay_status_name,o.o_send_status,o.o_send_status as send_status_name,
        o.o_deduct_type,o.o_pay_amount,o.o_check_status,
        o.o_return_status,o.o_total_amount,o.o_order_source,o.o_order_source as order_source_name,
        o.o_complete_time,o.o_receiving_status,o.o_receiving_mobile,
        o.o_discount_amount,o.o_receiving_address,
        o.o_receiving_realname,o.desc,o.member_id,o.create_time,
        o.warehouse_id,o.id as order_process_status_name,o.share_id,o.id as items,o_deduct_id,og_goods_key_name';

        $list = $this ->alias('o')
            ->where($where)
            ->join('n_order_goods og','o.id=og.order_id')
            ->field($field)
            ->page($page)
            ->group('o.id')
            ->order('o_pay_time desc o.id desc')
            ->select();

        $count = $this ->alias('o')
            ->where($where)
            ->join('n_order_goods og','o.id=og.order_id')
            ->group('o.id')
            ->count();
        return ['count'=>$count,'data'=>$list];
    }

    /***
     * 订单确认收货
     * @param $data array $data['o_id'] : 订单ID
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmReceipt($data)
    {
        if ( empty($data['o_id']))
        {
            return_error('请选择订单');
        }
        $order = self::getList(['o_id'=>$data['o_id']]);
        if ( $order['count'] == 0 )
        {
            return_error('订单不存在');
        }

        if ( $order['data'][0]['o_send_status'] == 0 )
        {
            return_error('订单未发货');
        }
        if ( $order['data'][0]['o_receiving_status'] == 2 )
        {
            return_error('订单已收货!请勿重复操作');
        }
        $this->startTrans();
        $res = $this ->allowField(true)
            ->isUpdate(true)
            ->save(['o_complete_time'=>time(),'o_receiving_status'=>2],['id'=>$data['o_id']]);

        //添加订单操作日志
        if(!empty($res))
        {
            $desc = '手动确认收货';
            $res = (new OrderOperateLog())->createOrderLog($data['o_id'],$data['user_info']['id'],$data['user_info']['nickname'],0,$desc);

            !empty($res) ? $this->commit() : $this->rollback();
        }

        return $res;
    }

    /***
     * 删除订单
     * @param $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delOrder($data)
    {
        if ( empty($data['o_id']))
        {
            return_error('请选择订单');
        }
        $order = self::getList(['o_id'=>$data['o_id']]);
        if ( $order['count'] == 0 )
        {
            return_error('订单不存在');
        }
        $this->startTrans();
        $res = $this ->allowField(true)
            ->isUpdate(true)
            ->save(['is_delete'=>1],['id'=>$data['o_id']]);

        //添加订单操作日志
        if(!empty($res))
        {
            $desc = '手动删除订单';
            $res = (new OrderOperateLog())->createOrderLog($data['o_id'],$data['user_info']['id'],$data['user_info']['nickname'],0,$desc);

            !empty($res) ? $this->commit() : $this->rollback();
        }

        return $res;
    }

    /***
     * 关闭订单
     * @param $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function closeOrder($data)
    {
        if ( empty($data['o_id']))
        {
            return_error('请选择订单');
        }
        $order = self::getList(['o_id'=>$data['o_id']]);
        if ( $order['count'] == 0 )
        {
            return_error('订单不存在');
        }
        $this->startTrans();
        $res = $this ->allowField(true)
            ->isUpdate(true)
            ->save(['o_pay_status'=>2],['id'=>$data['o_id']]);

        //添加订单操作日志
        if(!empty($res))
        {
            $desc = '手动关闭订单';
            $res = (new OrderOperateLog())->createOrderLog($data['o_id'],$data['user_info']['id'],$data['user_info']['nickname'],0,$desc);

            !empty($res) ? $this->commit() : $this->rollback();
        }

        return $res;
    }

    /**
     * 进销存的发货单
     */
    public function deliverOrder($data)
    {
        $data['page'] = !empty($data['page']) ? $data['page'] : 1;
        $data['limit'] = !empty($data['limit']) ? $data['limit'] : 10;
        $sql = "SELECT
                    *
                FROM
                    (
                        SELECT
                            (
                                CASE 
				
                                WHEN stock - buy_num >= 0 THEN
                                    1 
                                WHEN stock = 0 THEN
                                    0
                                ELSE
                                    2
                                END
                            ) c_con,
                            o_sn,
                            id,
                            o_receiving_mobile,
                            o_receiving_realname,
                            o_receiving_address,
                            o_send_status,
                            o_receiving_status
                        FROM
                            (
                                SELECT
                                    a.o_sn,
                                    b.og_goods_title,
                                    og_num,
                                    a.id,
                                    a.o_receiving_mobile,
                                    a.o_receiving_realname,
                                    a.o_receiving_address,
                                    a.o_send_status,
                                    a.o_receiving_status,
                                    SUM(b.og_num) buy_num,
                                    SUM((w_stock - w_stock_freeze)) stock
                                FROM
                                    ddxm_n_order a
                                INNER JOIN ddxm_n_order_goods b ON a.id = b.order_id
                                INNER JOIN ddxm_warehouse_goods c ON b.goods_id = c.goods_id
                                AND b.og_goods_key = c.specs_id
                                WHERE
                                    a.o_pay_status = 1
                                AND a.is_delete = 0";

        if ( !empty($data['o_send_status']) )
        {
            $sql .= " AND a.o_send_status = ".$data['o_send_status'];
        }
        if ( !empty($data['search_val']) )
        {
            $sql .= " AND a.o_sn|b.og_goods_title|b.og_goods_code LIKE "."'".$data['search_val']."'";
        }
        if ( !empty($data['shop_id']) )
        {
            //发货仓库
            $sql .= " AND c.warehouse_id = ".$data['shop_id'];
        }

        $sql .= "   GROUP BY
                                    id
                            ) AS now
                    ) AS two";
        if ( isset($data['c_con']) && $data['c_con'] != '' )
        {
            $sql .= " WHERE c_con =  ".$data['c_con'];
        }
//        $list = Db::query($sql);
//
//        dump($list);

        dump($sql);die;
    }







    /**
     * @param $orderId int 订单ID
     * 修改订单的总状态
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderStatus( $orderId )
    {
        $order = self::getList(['o_id'=>$orderId]);
        $info = $order['data']->toArray();
        $info = $info[0]['items'];

        $all_count = count($info); //总条数
        $send_0 = 0;        //未发货
        $send_1 = 0;        //部分发货
        $send_2 = 0;        //已发货

        $return_0 = 0;  //未退货
        $return_1 = 0;  //部分退货
        $return_2 = 0;  //已退货

        foreach ( $info as $k=>$v )
        {
            if ($v['og_send_status'] == 0)
            {
                $send_0 += 1;
            }else if ($v['og_send_status'] == 1)
            {
                $send_1 += 1;
            }else{
                $send_2 += 1;
            }

            if ($v['og_return_status'] == 0)
            {
                $return_0 += 1;
            }else if ($v['og_return_status'] == 1)
            {
                $return_1 += 1;
            }else{
                $return_2 += 1;
            }

        }

        $order_status = [
            'o_send_status' =>0,
            'o_return_status' =>0,
        ];
        if ( $send_0 == $all_count )
        {
            //全部都没有发货
            $order_status['o_send_status'] = 0;
        }else if( $send_2 == $all_count )
        {
            //全部都发货
            $order_status['o_send_status'] = 2;
        }else{
            //部分发货
            $order_status['o_send_status'] = 1;
        }

        if ( $return_0 == $all_count )
        {
            //全部都没有退货

        }else if( $return_2 == $all_count )
        {
            //全部都退货
            $order_status['o_return_status'] = 2;
        }else{
            //部分退货
            $order_status['o_return_status'] = 1;
        }
        $res = $this ->allowField(true)->isUpdate(true)->save($order_status,['id'=>$orderId]);
        return $res;
    }

    /***
     * @param  $val integer
     * 获取支付状态
     * @return mixed
     */
    public function getPayStatusNameAttr($val)
    {
        $status = [0=>'未支付',1=>'已支付',2=>'已关闭'];
        return $status[$val];
    }

    /***
     * @param  $val string
     * 发货状态
     * @return mixed
     */
    public function getSendStatusNameAttr($val)
    {
        $status = [0=>'未发货',1=>'部分发货',2=>'已发货'];
        return $status[$val];
    }

    /***
     * @param  $val string
     * 支付方式
     * @return mixed
     */
    public function getPayTypeNameAttr($val)
    {
        $status = [1=>'微信支付',2=>'支付宝支付',3=>'余额支付'];
        return !$val ? '未支付':$status[$val];
    }

    /***
     * @param  $val string
     * 订单来源
     * @return mixed
     */
    public function getOrderSourceNameAttr($val)
    {
        $status = [1=>'自营',2=>'跨境购'];
        return $status[$val];
    }

    /***
     * @param  $val string
     * 订单类型
     * @return mixed
     */
    public function getTypeNameAttr($val)
    {
        $status = [0=>'普通订单',1=>'拼团订单',2=>'抢购订单',3=>'限时抢购'];
        return $status[$val];
    }

    /***
     * @param $val string
     * 获取订单商品信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItemsAttr($val)
    {
        $field = 'og.id,order_id,goods_id,og_num,og_price,og_sent_num,og_goods_pic,og_return_status as og_return_status_name,
            og_goods_title,og_goods_key_name,og_goods_code,og_goods_key,og_send_status,og_send_status as og_send_status_name,
            og_retired_num,og_return_status,sender_id';
        $goods = (new OrderGoods())
            ->alias('og')
            ->where('order_id',intval($val))
            ->join('item i','i.id = og.goods_id')
            ->field($field)
            ->select();
        return  $goods;
    }

    /***
     * 获取订单进程的状态(即订单状态) order_process_status_name
     * @param  $val string
     * @param  $data array
     * @return string
     */
    public function getOrderProcessStatusNameAttr($val,$data)
    {
        $codeNums = [
            1   =>'待付款',        //操作：关闭订单、去支付
            2   =>'待发货(部分发货)',  //操作：催促发货
            3   =>'待收货(部分收货)',  //查看物流、确认收货
            4   =>'已完成',            //操作：删除订单、评论订单商品
            5   =>'已关闭',            //操作：删除订单
        ];
        $stat = [];
        $o_pay_status = $data['o_pay_status'];      //支付状态
        $o_send_status = $data['o_send_status'];    //发货状态
        $o_receiving_status = $data['o_receiving_status'];  //收货状态
        if ( $o_pay_status != 1 )
        {
//            return $o_pay_status==0?'待支付':'已关闭';
            $stat = [
                'code'  =>$o_pay_status==0?'1':'5',
                'name'  =>$o_pay_status==0?'待支付':'已关闭',
            ];
        }else{
            if ($o_send_status != 2)
            {
//                return $o_send_status==0?'待发货':'部分发货';
                $stat = [
                    'code'  =>2,
                    'name'  =>$o_send_status==0?'待发货':'部分发货',
                ];
            }else{
                if ( $o_receiving_status != 2 )
                {
//                    return $o_receiving_status==0?'待收货':'部分收货';
                    $stat = [
                        'code'  =>3,
                        'name'  =>$o_receiving_status==0?'待收货':'部分收货',
                    ];
                }else{
//                    return '已完成';
                    $stat = [
                        'code'  =>4,
                        'name'  =>'已完成',
                    ];
                }
            }
        }
        return $stat;
    }
}