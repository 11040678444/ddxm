<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/18 0018
 * Time: 下午 13:54
 * 订单退货
 */

namespace app\wxshop\model\n_order;
use think\Model;

class OrderReturn extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 售后列表查询
     * @param array $param 查询条件
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getList($param = [])
    {
        $where = array_merge(['or.is_delete'=>0],$param);

        $field = 'or.id,og_goods_pic,og_goods_title,og_num,og_price,og_goods_key,og_goods_pic,ot_status,pics,
                ot_reason,ot_sn,or.create_time,o_receiving_mobile,o_receiving_realname,o_sn,ot_remarks,ot_number,
                or.order_id,ot_type,o_pay_type,member_id,order_goods_id,warehouse_id,ot_entry_num,or.desc';

        $list = OrderReturn::alias('or')
            ->field($field)
            ->join([['n_order_goods og','og.id = or.order_goods_id'],['n_order o','or.order_id = o.id']])
            ->where($where)
            ->order('or.create_time desc')
//            ->paginate(10)
            ->count();

        return $list;
    }

    //商城看到的状态
    protected function getShopStatusAttr($val,$data)
    {
        $status = [
            '-1'=>'已拒绝',
            '0'=>'待处理',
            '10'=>'待处理',
            '20'=>'待寄货',
            '30'=>'寄货中',
            '40'=>'寄货中',
            '50'=>'寄货中',
            '60'=>'已完成'
        ];

        return $status[$data['ot_status']];
    }

    //图片拼接七牛云地址
    protected function getOgGoodsPicAttr($val)
    {
        return config('config.qiNiu_picture').$val;
    }

    //后台看的状态
    protected function getSystemStatusAttr($val,$data)
    {
        $status = [
            '-1'=>'已拒绝',
            '0'=>'待处理',
            '10'=>'待电商确认',
            '20'=>'待寄货',
            '30'=>'寄货中',
            '40'=>'待仓库确认',
            '50'=>'待确认打款',
            '60'=>'已完成'
        ];

        return $status[$data['ot_status']];
    }

    /**
     * 修改表字段（ddxm_order_return）
     * @param array $param 修改参数 ['id'=>'必有','需修改字段'=>value]
     * @return array|false
     * @throws \Exception
     */
    public function upOrderReturn($param)
    {
        if(getmaxdim($param)<1)
        {
            $param = [$param];
        }
        $res = OrderReturn::allowField(true)->saveAll($param);

        return $res;
    }

    /**
     * 电商确认退单
     * @param array $param ['id'=>退单ID,'ot_status'=>退单状态]
     * @return $this|array|false|int|true
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function orderTrueHandle($param)
    {
        OrderReturn::startTrans();

        //退货信息
        $info = $this->getList(['or.id'=>$param['id']])->toArray()['data'][0];

        if(!empty($res))
        {
            //修改ot_status状态
            $res = $this->upOrderReturn($param)->toArray();

            //请勿重复操作
            $info['ot_status'] == $param['ot_status']? return_error('该订单已完成或正在处理中，请勿重复操作') : '';

            //如果不是拒绝操作走对应流程，反之拒绝直接返回
            if($param['ot_status'] != -1 && !empty($res))
            {
                //修改主表退货状态（ddxm_n_order->o_return_status）
                if($info['ot_number'] == $info['og_num'])
                {
                    $o_return_status = 2;
                }elseif(bccomp($info['og_num'],$info['ot_number']) == 1)
                {
                    $o_return_status = 1;
                }
                !isset($o_return_status) ? return_error('数据故障') : '';

                $res = Order::update(['o_return_status'=>$o_return_status,'id'=>$info['order_id']]);

                //根据退货类型进行是否走仓库确认还是直接打款【1退货退款->仓库确认、2退款->根据付款方式退款，订单则直接完成】
                if(!empty($res))
                {
                    //查询支付流水
                    $flow = FinancialFlow::all(['order_code'=>$info['o_sn']])->toArray();

                    empty($flow) ? return_error('支付流水异常,请核实！'):'';

                    //根据支付流水进行退款
                    if($info['o_pay_type'] == 3)
                    {
                        //余额支付
                        $res = (new MemberMoney())->returnBalance($info,$flow);

                    }else{
                        //微信支付
                    }

                    //添加股东数据
                    if($res)
                    {
                        //只退款，只记录用户实际支付价格
                        $res = (new StatisticsLog())->addStatisticsLog([
                            'price'=>$info['og_price'],
                            'pay_way'=>$info['o_pay_type'],
                            'join_id'=>$info['order_id'],
                            'shop_id'=>$info['warehouse_id'],
                            'order_sn'=>$info['o_sn']
                        ],2);

                        //退货退款，记录成本数据
                        if($res['code'] == 200 && $info['ot_type'] == 1)
                        {
                            //退货退款，状态值必须处于仓库确认状态以及退货数量=入库数量
                            $info['ot_status'] != 40 && $info['ot_entry_num'] != $info['ot_number'] ? return_error('非法操作！') : '';

                            $url = 'http://www.ddxm-shop.cc/index.php/api/';
//                            $url = 'http://ddxm661.com:8088/index.php/api/';
                            $data = ['join_id'=>$param['id'],'specs_id'=>$info['og_goods_key'],'goods_id'=>$info['order_goods_id']];
                            //获取成本
                            $goods_cost = sendPost($url.'warehouse_past_cost/getOrderGoodsCost/',[],$data);
                            //成本不能为空
                            empty($goods_cost) ? return_error('商品成本不存在') : '';

                            $res = (new StatisticsLog())->addStatisticsLog([
                                'price'=>array_sum(array_column($goods_cost,'wpc_cost')),
                                'pay_way'=>$info['o_pay_type'],
                                'join_id'=>$info['order_id'],
                                'shop_id'=>$info['warehouse_id'],
                                'order_sn'=>$info['o_sn']
                            ],3);

                            //修改退单状态为60（已完成）
                            if($res['code'] == 200)
                            {
                                $res = $this->upOrderReturn(['ot_status'=>60,'id'=>$param['id']])->toArray();
                                $res = $res['code'] == 200 ? 1 : 0;
                            }
                        }
                    }
                }
            }

            //添加订单操作日志
            if(!empty($res))
            {
                $desc = '售后流程';
                switch ($param['ot_status'])
                {
                    case -1:
                        $desc.='(电商部拒绝:'.$param['desc'].')';
                        break;

                    case 10:
                        $desc.='(电商部同意)';
                        break;

                    case 50:
                        $desc.='(库房确认收货)';
                        break;
                }
//                $res = (new OrderOperateLog())->createOrderLog(
//                    $info['order_id'],session('system.user_info')['id'],
//                    session('system.user_info')['nickname'],1,$desc
//                );
            }

            !empty($res) ? $this->commit():$this->rollback();
        }else{
            return_error('操作失败，未找到退货信息');
        }

        return $res;
    }

}