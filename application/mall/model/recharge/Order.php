<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/24
 * Time: 18:01
 * 充值订单模型，与老代码分开的
 */

namespace app\mall\model\recharge;
use app\wxshop\wxpay\WxPayMicroPay;
use app\common\model\PayModel;
use app\mall\model\order\OrderModel;
use app\common\controller\FinancialFlow;
use think\Model;
use think\Db;

class Order extends Model
{
    protected $autoWriteTimestamp = true;

    /***
     * 获取活动充值列表
     */
    public function recharge($data)
    {
        $where = [];
        if( !empty($data['name']) ){
            $name = $data['name'];
            $where[] = ['m.mobile|a.sn','like',"%$name%"];
        }
        if( !empty($data['shop_id']) ){
            $where[] = ['a.shop_id','=',$data['shop_id']];
        }
        if( !empty($data['pay_way']) ){
            $where[] = ['a.pay_way','=',$data['pay_way']];
        }
        if( !empty($data['time']) ){
            $time = strtotime($data['time']);
            $where[] = ['a.add_time','>=',$time];
        }
        if( !empty($data['end_time']) ){
            $end_time = strtotime($data['end_time']);
            $where[] = ['a.add_time','<=',$end_time];
        }
        //订单对账状态搜索
        if(isset($data['is_examine'])  && $data['is_examine'] != ''  ){
            $where[] = ['a.is_examine',"=",$data['is_examine']];
        }
        $where[] = ['type','eq',8];
        $where[] = ['pay_status','eq',1];
        $res = $this->alias('a')
            ->join('shop s','a.shop_id=s.id')
            ->join('member m','a.member_id=m.id')
            ->where($where)
            ->page($data['page'],$data['limit'])
            ->field('a.id,a.sn,s.name,m.mobile,m.nickname,a.amount,a.pay_way,a.add_time,a.remarks,a.is_examine')
            ->select()->toArray();
        $count = $this ->alias('a')
            ->join('shop s','a.shop_id=s.id')
            ->join('member m','a.member_id=m.id')->where($where)->count();
        return ['code'=>0,'count'=>$count,'data'=>$res];
    }

    /***
     * 获取时间
     */
    public function getAddTimeAttr($val)
    {
        return date('Y-m-d H:i:s',$val);
    }

    /***
     * 获取支付方式
     */
    public function getPayWayAttr($value)
    {
        if (empty($value)) {
            return '未支付';
        }
        $status = array(
            1 => '微信',
            2 => '支付宝',
            3 => '余额',
            4 => '银行卡',
            5 => '现金支付',
            6 => '美团',
            7 => '赠送',
            8 => '门店自用',
            9 => '兑换',
            10 => '包月服务',
            11 => '定制疗程',
            12 => '超级汇买',
            13 => '限时余额',
            14 => '云客赞',
            15  =>'框框宝',
            16  =>'公司转门店',
            99 => '异常充值'
        );
        return $status[$value];
    }

    /***
     *活动充值操作
     *充值线上余额
     */
    public function rechargeDoPost($data)
    {
        $memberInfo = Db::name('member')
            ->alias('a')
            ->where('a.mobile',$data['mobile'])
            ->join('member_money b','a.id=b.member_id')
            ->join('shop s','a.shop_code=s.code')
            ->field('a.id,a.mobile,a.level_id,s.id as shop_id,b.money,b.online_money,s.name')
            ->find();
        if ( !$memberInfo )
        {
            return json(['code'=>0,'msg'=>'用户信息错误']);
        }
        $where = [];
        $where[] = ['member_id','eq',$memberInfo['id']];
        $where[] = ['expires_time','>=',time()];
        $where[] = ['remain_price','>',0];
        $memberCanUseRe = Db::name('st_recharge')->where($where)->sum('remain_price');  //总共的可使用的抵扣金额
        //1:订单信息
        $sn ='CZ'.(strtotime(date('YmdHis', time()))) . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
        $order = [
            'shop_id'   =>$memberInfo['shop_id'],
            'member_id' =>$memberInfo['id'],
            'sn'    =>$sn,
            'type'=>8,
            'number'=>1,
            'discount'=>0,
            'postage'=>0,
            'amount'=>$data['amount'],
            'pay_status'=>1,
            'pay_way'=>$data['pay_way'],
            'add_time'=>time(),
            'is_online'=>0,
            'order_type'=>1,
            'old_amount'=>$data['amount'],
            'user_id'=>session('admin_user_auth')['uid'],
            'remarks'=>!empty($data['remarks'])?$data['remarks']:'',
            'order_distinguish'=>0,
            'paytime'    =>time(),    //转成时间戳
            'overtime'  =>time(),
            'order_status'  =>2,
        ];
        //2:充值记录
        $recharge_log = [
            'member_id' =>$memberInfo['id'],
            'shop_id'   =>$memberInfo['shop_id'],
            'price'     =>$data['amount'],
            'pay_way'     =>$data['pay_way'],
            'create_time'     =>time(),
            'remarks'     =>'线上商城预存送充值',
            'title'     =>'线上商城预存送充值',
            'type'     =>3
//            'order_id'  =>0
        ];
        //3:会员余额变化记录
        $member_details = [
            'member_id' =>$memberInfo['id'],
            'mobile' =>$memberInfo['mobile'],
            'remarks' =>'线上商城预存送充值',
            'reason' =>'线上商城预存送充值',
            'addtime' =>time(),
            'amount' =>$data['amount'],
            'type' =>1
//            'order_id' =>$order['id']
        ];
        //4:股东数据
        $statisticsLog = array(
            'shop_id'		=>$memberInfo['shop_id'],
//            'order_id'		=>$order['id'],
            'order_sn'		=>$sn,
            'type'			=>1,
            'data_type'	=>1,
            'pay_way'		=>$data['pay_way'],
            'price'			=>$data['amount'],
            'title'			=>'线上商城预存送充值',
            'create_time'	=>time()
        );
        //5:计算等级,$new_level表示不需要修改等级，反正表示需要修改等级
        $new_level = 0;
        //计算等级
        $amount = Db::name('member_details')
            ->where('member_id',$memberInfo['member_id'])->where('type',1)
            ->sum('amount');    //累计充值
        $amount += $data['amount'];
        $levelWhere = [];
        $levelWhere[] = ['shop_id','=',$memberInfo['shop_id']];
        $levelWhere[] = ['price','<=',$amount];
        $level = Db::name('level_price')->where($levelWhere)
            ->order('price desc')->find();
        if( $level ){
            if( $level['level_id'] > $memberInfo['level_id'] ){
                //更改门店等级
                $new_level = $level['level_id'];
            }
        }
        //充值操作
        $this ->startTrans();
        $res = $this ->allowField(true)->save($order);  //充值订单
        $orderId = $this ->id;
        //如果是微信或者支付宝,先支付
        if ( $res )
        {
            if ( $data['pay_way']==1 || $data['pay_way'] == 2 )
            {
                if( $data['pay_way'] == 1 ){
                    //微信支付
                    // 支付授权码
                    $input = new WxPayMicroPay();
                    $input->SetAuth_code($data['code']);
                    $input->SetBody($memberInfo['name']);
//                    $input->SetTotal_fee(1);//订单金额  订单单位 分
                    $input->SetTotal_fee($data['amount']*100);//订单金额  订单单位 分
                    $input->SetOut_trade_no($sn);
                    $PayModel = new PayModel();
                    $res= $PayModel ->pay($input);
                }else if($data['pay_way'] == 2){
                    //支付宝支付
                    $PayModel = new PayModel();
//                    $res = $PayModel ->AliCodePay($data['code'],$sn,$memberInfo['name'],0.01);
                    $res = $PayModel ->AliCodePay($data['code'],$sn,$memberInfo['name'],$data['amount']);
                }
            }
        }
        //正常业务逻辑
        if ( $res )
        {
            $recharge_log['order_id'] = $orderId;
            $res = Db::name('member_recharge_log') ->lock(true)->insert($recharge_log);    //添加充值日志
            if ( $res )
            {
                $res = Db::name('member_money')->where('member_id',$memberInfo['id'])->setInc('online_money',$order['amount']);   //增加线上余额
                if ( $res )
                {
                    $member_details['order_id'] = $orderId;
                    $res = Db::name('member_details')->lock(true)->insert($member_details);    //会员余额明细表
                    if ( $res )
                    {
                        $statisticsLog['order_id'] = $orderId;
                        $res = Db::name('statistics_log')	->insert($statisticsLog);   //添加股东数据
                        if ( $res && ($new_level!=0) )
                        {
                            $res = Db::name('member')->where('id',$memberInfo['id'])->setField('level_id',$new_level);  //更改会员等级
                        }
                        //赠送金额
                        if ( $res )
                        {
                            //活动规则配置
                            $config = config('Recharge');
                            //获取当前充值对应赠送抵扣金额
                            $give_amount = array_search(intval($data['amount']),$config);
                            //如果存在活动配置，则新增折扣记录
                            if($give_amount)
                            {
                                //组装ddxm_st_recharge表数据
                                $recharge_data = [
                                    'member_id'=>$memberInfo['id'],
                                    'enter_price'=>$give_amount,//当时充值赠送的折扣金额
                                    'remain_price'=>$give_amount,//剩余可使用的折扣金额
                                    'order_id'=>$orderId,//订单id
                                    'create_time'=>time(),
                                    'expires_time'=>strtotime(date('Y-m-d H:i:s',strtotime('+6month')))//过期时间（6月后过期）
                                ];
                                $res = Db::name('st_recharge')->insert($recharge_data);
                                if ( $res )
                                {
                                    //添加支付流水记录financial_flow
                                    $financialFlowData = [];
                                    $arr = [];
                                    $arr = [
                                        'member_id' =>$memberInfo['id'],
                                        'flow_code' =>'DDXM'.date('Ymd').(new OrderModel()) ->nonceStr(),
                                        'order_code'   =>$sn,
                                        'flow_type' =>3,
                                        'change_money'=>$data['amount'],
                                        'pre_change_money'=>$memberInfo['online_money'],
                                        'after_change_money'=>bcadd($memberInfo['online_money'],$data['amount'],2),
                                        'pay_type'  =>$data['pay_way'],
                                        'money_type'    =>3,
                                    ];
                                    array_push($financialFlowData,$arr);
                                    $arr['flow_code'] = 'DDXM'.date('Ymd').(new OrderModel())->nonceStr();
                                    $arr['flow_type'] = 4;
                                    $arr['change_money'] = $give_amount;
                                    $arr['pre_change_money'] = $memberCanUseRe;
                                    $arr['after_change_money'] = bcadd($memberCanUseRe,$give_amount,2);
                                    $arr['pay_type'] = $data['pay_way'];
                                    $arr['money_type'] = 4;
                                    array_push($financialFlowData,$arr);
                                    $resFin = (new FinancialFlow())->addFlow($financialFlowData);
                                    $res = $resFin['status']==200?1:0;
                                }
                            }
                        }
                    }
                }
            }
        }
        !$res ? $this ->rollback():$this->commit();
        return $res;
    }
}