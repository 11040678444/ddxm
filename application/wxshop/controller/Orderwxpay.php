<?php
namespace app\wxshop\controller;

use app\common\controller\FinancialFlow;
use app\mall_admin_market\model\exclusive\NewExclusive;
use app\mall_admin_market\model\exclusive\StPayLog;
use think\Controller;
use think\Db;
use app\common\model\WxPayModel;
use app\mall_admin_order\controller\OrderService;
use app\wxshop\model\coupon\CouponReceiveModel;
use think\Exception;

/**
支付
 */
class Orderwxpay extends Token
{
    /***
     * pay_way:1微信，3钱包
     * 支付
     */
    public function pay(){
        $data = $this ->request ->post();
        $memberId = self::getUserId();
        if( empty($data['order_id']) || empty($data['pay_way']) ){
            return json(['code'=>100,'msg'=>'请选择支付方式或选择订单','data'=>array('id'=>'','sn'=>'')]);
        }
        $order = Db::name('order')->where(['id'=>$data['order_id'],'member_id'=>$memberId])->find();      //订单信息
        if( !$order ){
            return json(['code'=>100,'msg'=>'订单号错误','data'=>array('id'=>'','sn'=>'')]);
        }
        if( empty($order['amount']) || empty($order['member_id']) ){
            return json(['code'=>100,'msg'=>'订单出现错误，请重新下单','data'=>array('id'=>'','sn'=>'')]);
        }
        if( $order['pay_status'] == '-1' ){
            return json(['code'=>100,'msg'=>'订单已取消','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
        }
        if( $order['pay_status'] == 1 ){
            return json(['code'=>100,'msg'=>'订单已支付,请勿重复支付！','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
        }
        //使用优惠券
        if( !empty($order['coupon_id']) && !empty($order['c_receive_id']) ){
            $coupon = (new CouponReceiveModel()) ->where('id',$order['c_receive_id'])->find();
            if( $coupon['is_use'] != 1 ){
                return json(['code'=>100,'msg'=>'该订单优惠券已使用！请重新下单！','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
            }
        }
        $item = Db::name('order_goods') ->where('order_id',$order['id'])->select();//订单商品表
        foreach ( $item as $k=>$v ){
            //判断库存是否不足
            $where = [];
            $where[] = ['key','eq',$v['attr_ids']];
            $where[] = ['gid','eq',$v['item_id']];
            $where[] = ['status','eq',1];
            $stock = Db::name('specs_goods_price')->where($where)->field('id,store')->find();
            if ( $stock['store'] != '-1' ) {    //不是无限制库存
                if ( $v['num'] > $stock['store'] ) {
                    return json(['code'=>100,'msg'=>'商品:'.$v['subtitle'].'库存不足','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                }
            }
        }
        //判断是否为活动订单，如果为活动订单,判断活动是否结束
        if( $order['order_distinguish'] == 1 || $order['order_distinguish'] == 2 || $order['order_distinguish'] == 3 ){
            $map = [];
            $map[] = ['id','eq',$order['event_id']];
            $active = Db::name('flash_sale') ->where($map)->find();
            if( !$active ){
                return json(['code'=>100,'msg'=>'活动错误','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
            }
            if( $active['type'] != 4 ){
                if( ($active['status'] != 1) && (time() > $active['end_time']) ){
                    return json(['code'=>100,'msg'=>'活动已结束','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                }
            }
            if( $order['order_distinguish'] == 1 ){ //表示拼团订单
                $order_assemble_log = Db::name('order_assemble_log') ->where('order_id',$order['id'])->find();    //当时拼团时的金额
                $map = [];
                $map[] = ['flash_sale_id','eq',$order['event_id']];
                $map[] = ['item_id','eq',$order_assemble_log['item_id']];
                $map[] = ['specs_ids','eq',$order_assemble_log['attr_ids']];
                $flash_sale_attr = Db::name('flash_sale_attr')->where($map)->find();    //当前此活动的状态
                if( $flash_sale_attr['status'] != 1 ){
//                        return json(['code'=>100,'msg'=>'活动已结束','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                }
                if( $order['commander_type'] == 1 ){
                    //团长、判断金额是否变动
                    if( $order_assemble_log['commander_price'] != $flash_sale_attr['commander_price'] ){
                        return json(['code'=>100,'msg'=>'商品金额有变动,请重新下单','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                    }
                }else{
                    //团员、判断金额是否变动
                    if( $order_assemble_log['price'] != $flash_sale_attr['price'] ){
                        return json(['code'=>100,'msg'=>'商品金额有变动,请重新下单','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                    }
                    //判断组是否结束
                    $assemble_list_info = Db::name('assemble_list')->where('id',$order_assemble_log['assemble_list_id'])->find();
                    if( $assemble_list_info['status'] != 1 ){
                        return json(['code'=>100,'msg'=>'该团已结束，请重新下单','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                    }
                }
            }
        }
        //如果是新人专享订单
        if ( $order['order_distinguish'] == 6 )
        {
            $payStatus = ( new StPayLog() ) ->userPayLog(['member_id'=>$this->getUserId()]);
            if( $payStatus == 1 )
            {
                return json(['code'=>100,'msg'=>'您已购买过新人专享商品啦','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
            }
            $map = [];
            $map[] = ['a.item_id','eq',$item[0]['item_id']];
            $map[] = ['a.is_delete','eq',0];
            $map[] = ['a.ng_id','eq',$order['event_id']];
            $map[] = ['b.is_delete','eq',0];
            $exclusive_goods = ( new NewExclusive() ) ->alias('a')
                ->join('st_exclusive_goods b','a.ng_id=b.id')
                ->where($map)
                ->field('a.id as se_id,b.id as seg_id,a.attr_ids,a.item_id,a.price')->find();
            if( !$exclusive_goods )
            {
                return json(['code'=>100,'msg'=>'新人专享商品已下架','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
            }
        }
        $memberInfo = Db::name('member')->where('id',$memberId)->field('id,openid,wechat_nickname,mobile,nickname,retail')->find();
        if( $data['pay_way'] == 1 ){
            //微信
            if( !$memberInfo && empty($memberInfo['openid']) ){
                return json(['code'=>100,'msg'=>'请先绑定微信','data'=>['id'=>$order['id'],'sn'=>$order['sn']]]);
            }
            $WxPayModel = new WxPayModel();
            $payData = array(
                'title' =>'购买商品',
                'amount'   =>$order['amount'],
                'order_sn' =>$order['sn'],
                'openId'    =>$memberInfo['openid'],
                'notify_url'    =>config('notify_url').'index'//'https://www.ddxm661.com/wxshop/Wxnotify/index',
            );
            $res = $WxPayModel ->pay($payData);
            $res = json_decode($res,true);
            $res['id'] = $order['id'];
            $res['sn'] = $order['sn'];
            $res['order_distinguish'] = $order['order_distinguish'];
            $res['money'] = $order['amount'];
            $res['nickname'] = $order['realname'];
            $res['address'] = $order['detail_address'];
            if( $res ){
                return json(['code'=>200,'msg'=>'支付成功','data'=>$res]);
            }
            return json(['code'=>101,'msg'=>'支付发生错误','data'=>['id'=>$order['id'],'sn'=>$order['sn']],'order_distinguish'=>$order['order_distinguish']]);
        }else if( $data['pay_way'] == 3 ) {
            /**
             * 预判可能存在网络延迟，导致重复点击操作
             * 处理方法1：防止重复支付，添加安全验证'非阻塞排它锁'进行处理（目前选择处理方式）
             * 处理方法2：添加支付token验证码，启用文件缓存验证（法1失败启用法2，双重验证）
             * 处理方法3：启用Redis优先存入支付订单号，付款成功后进行释放此方法与法2类似
             */
            $file = fopen('paylock.txt', 'w+');
            if (flock($file, LOCK_EX | LOCK_NB)) {

                //钱包
                $memberMoney = Db::name('member_money') ->where('member_id',$order['member_id'])->field('money,mobile,online_money')->find();   //会员余额信息
                $allMoney = $memberMoney['money']+$memberMoney['online_money'];
                //获取会员还未激活的限时余额与已过期但是还未改状态得限时余额
                $allMoney = $allMoney - self::getNotUsePrice($order['member_id']);
                $is_eq = bccomp($order['amount'],$allMoney);
                if($is_eq == 1){
                    return json(['code'=>100,'msg'=>'钱包余额不足','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                }
                // 启动事务
                Db::startTrans();
                try {
                    $moneyExpire_logMoney = 0;	//使用的限时余额
                    $xianlogMoney = 0;	//使用的线上余额
                    $puTonglogMoney = 0;	//使用的普通余额
                    //判断是否有限时余额，如果存在则扣限时余额
                    $moneyExpire = self::gerMember($order['amount'],$order['member_id']);
                    if( $moneyExpire ){
                        $moneyExpire_logMoney = 0;	//使用的限时余额
                        foreach ($moneyExpire as $k=>$v){
                            Db::name('member_money_expire') ->where('id',$v['id'])->setField('use_price',$v['use_price']);
                            //添加限时余额使用记录表
                            $arr = [];
                            $arr = [
                                'member_id' =>$memberId,
                                'order_id' =>$data['order_id'],
                                'price' =>$v['consume_price'],
                                'money_expire_id' =>$v['id'],
                                'order_sn' =>$order['sn'],
                                'create_time' =>time(),
                                'reason' =>'小程序购买商品',
                            ];
                            Db::name('member_expire_log') ->insert($arr);
                            $moneyExpire_logMoney += $v['consume_price'];	//总共使用的限时余额
                        }
                    }

                    if( $moneyExpire_logMoney >= $order['amount'] ){
                        //表示全部都是用

                        Db::name('member_money')->where('member_id',$order['member_id'])->setDec('money',$order['amount']);
                    }else{
                        //表示限时余额用了一部分
                        $lisMoney = $order['amount']-$moneyExpire_logMoney; //除了限时余额还剩余该扣的金额（普通与线上该扣的金额）
                        if( $lisMoney <= $memberMoney['online_money'] ){
                            $xianlogMoney = $lisMoney;
                            //剩余的钱小于线上余额，就全部扣线上余额
                            Db::name('member_money')->where('member_id',$order['member_id'])->setDec('money',$moneyExpire_logMoney);	//扣除总余额中的限时余额
                            Db::name('member_money')->where('member_id',$order['member_id'])->setDec('online_money',$lisMoney);	//扣除线上余额余额
                        }else{
                            //限时余额和线上余额都不够扣
                            $lasMoney = $order['amount'] - $moneyExpire_logMoney - $memberMoney['online_money'];	//最后该扣的总金额
                            $puTonglogMoney = $lasMoney;
                            $xianlogMoney = $memberMoney['online_money'];	//线上余额
                            Db::name('member_money')->where('member_id',$order['member_id'])->setDec('money',$moneyExpire_logMoney);	//扣除总余额中的限时余额
                            Db::name('member_money')->where('member_id',$order['member_id'])->setDec('online_money',$xianlogMoney);	//扣除线上余额余额
                            Db::name('member_money')->where('member_id',$order['member_id'])->setDec('money',$lasMoney);	//扣除总余额的普通余额
                        }
                    }
                    //将订单是否使用的金额做个记录
                    $order_money_log = [];
                    $order_money_log = [
                        'order_id'	=>$order['id'],
                        'putong_money'	=>$puTonglogMoney,
                        'xianshi_money'	=>$moneyExpire_logMoney,
                        'xianshang_money'	=>$xianlogMoney,
                    ];
                    Db::name('order_money_log')->insert($order_money_log);
                    //生成消费明细
                    $detailsArr = array(
                        'member_id'     =>$order['member_id'],
                        'mobile'     =>$memberMoney['mobile'],
                        'remarks'     =>'',
                        'reason'     =>'小程序购买商品',
                        'addtime'     =>time(),
                        'amount'     =>$order['amount'],
                        'type'     =>3,
                        'order_id'     =>$order['id'],
                    );
                    Db::name('member_details')->insert($detailsArr);

                    //更改订单状态
                    Db::name('order')->where('id',$order['id'])->update(['pay_status'=>1,'pay_way'=>3,'paytime'=>time(),'overtime'=>time()]);
                    //添加商品的销量
                    $oneRetailPrice = 0;    //一级分销金额
                    $twoRetailPrice = 0;    //二级分销金额
                    $ownRetailPrice = 0;    //自购分销金额
                    $retailStatus = 0;      //用来判断订单是否参与分销：0未参与，1参与分销
//                $item = Db::name('order_goods') ->where('order_id',$order['id'])->select(); //订单商品表
                    $retailInfo = [];       //分销佣金对应商品
                    foreach ($item as $k=>$v){
                        $map = [];
                        $map[] = ['gid','eq',$v['item_id']];
                        $map[] = ['key','eq',$v['attr_ids']];
                        $map[] = ['status','eq',1];
                        $specGoods = Db::name('specs_goods_price') ->where($map) ->find();
                        if( $specGoods['store'] != '-1' ){
                            Db::name('specs_goods_price') ->where($map)->setDec('store',$v['num']);
                        }
                        Db::name('specs_goods_price') ->where($map)->setInc('reality_sales',$v['num']);
                        //增加商品表的销量
                        Db::name('item') ->where('id',$v['item_id'])->setInc('reality_sales',$v['num']);
                        //计算分销金额分销
                        $itemInfo = Db::name('item') ->where('id',$v['item_id']) ->field('ratio_type,ratio,two_ratio,own_ratio')->find();
                        if( $itemInfo['ratio_type'] == 3 ){
                            Db::name('order_goods') ->where('id',$v['id']) ->update(['ratio'=>$itemInfo['ratio'],'two_ratio'=>$itemInfo['two_ratio'],'own_ratio'=>$itemInfo['own_ratio']]);
                            $oneRetailPrice += $v['num']*$itemInfo['ratio'];        //一级分销获得的金额
                            $twoRetailPrice += $v['num']*$itemInfo['two_ratio'];    //二级分销获得的金额
                            $ownRetailPrice += $v['num']*$itemInfo['own_ratio'];    //自购获得的金额
                            $arr = [];
                            $arr = [
                                'order_goods_id'    =>$v['id'],
                                'ratio'        =>$v['num']*$itemInfo['ratio'],
                                'two_ratio'    =>$v['num']*$itemInfo['two_ratio'],
                                'own_ratio'    =>$v['num']*$itemInfo['own_ratio'],
                            ];
                            array_push($retailInfo,$arr);
                            $retailStatus = 1;
                        }
                    }
                    //加入分销订单表
                    if( (count($retailInfo) >0) && ($order['order_distinguish'] != 4) ){
                        $orderRetail = [];      //分销订单表数据
                        foreach ( $retailInfo as $k=>$v ){
                            if( $memberInfo['retail'] == 1 ){
                                //是分销员,不管是不是分享,自购、一级、二级分销订单
                                $retailUser = Db::name('retail_user') ->where('member_id',$order['member_id'])->field('one_member_id,two_member_id') ->find();
                                $arr = [];
                                $arr = [
                                    'member_id' => $order['member_id'],
                                    'buy_member_id' => $order['member_id'],
                                    'order_goods_id'=>$v['order_goods_id'],
                                    'order_id' => $order['id'],
                                    'price' => $v['own_ratio'],     //自购
                                    'amount' => $order['amount'] - $order['postage'],
                                    'status' => 0,
                                    'create_time' => time()
                                ];
                                array_push($orderRetail,$arr);  //自购分销金额
                                if( !empty($retailUser['one_member_id']) ){
                                    $arr['member_id'] = $retailUser['one_member_id'];   //一级分销商
                                    $arr['price'] = $v['ratio'];   //一级分金额
                                    array_push($orderRetail,$arr);  //一级分销金额
                                }
                                if( !empty($retailUser['two_member_id']) ){
                                    $arr['member_id'] = $retailUser['two_member_id'];   //二级分销商
                                    $arr['price'] = $v['two_ratio'];   //二级分金额
                                    array_push($orderRetail,$arr);  //二级分销金额
                                }
                            }else{
                                //不是分销员，查看是否为别人分享的连接购买的商品
                                if( $order['share_id'] != 0 ){
                                    $share_info = Db::name('member')->where('id',$order['share_id'])->field('id,status,retail')->find();
                                    if( ($share_info['status'] == 1) && ($share_info['retail'] == 1) ){
                                        //分享人状态正常且为分销员,则生成分销佣金
                                        $retail_member_id = $share_info['id'];      //第一个分销员id
                                    }else{
                                        //分享过来的用户失效,则查看本身的上级分销员
                                        $retail_fans_where = [];
                                        $retail_fans_where[] = ['fans_id','eq',$order['member_id']];
                                        $retail_fans_where[] = ['status','eq',1];
                                        $retail_fans = Db::name('retail_fans') ->where($retail_fans_where)->find();
                                        if( $retail_fans ){
                                            //找到了分销员id
                                            $retail_member_id = $retail_fans['member_id']; //第一个分销员id
                                        }else{
                                            $retail_member_id = 0;      //第一个分销员id
                                        }
                                    }
                                }else{
                                    //不是分享过来的，查看是否为粉丝
                                    $retail_fans_where = [];
                                    $retail_fans_where[] = ['fans_id','eq',$order['member_id']];
                                    $retail_fans_where[] = ['status','eq',1];
                                    $retail_fans = Db::name('retail_fans') ->where($retail_fans_where)->find();
                                    if( $retail_fans ){
                                        //找到了分销员id
                                        $retail_member_id = $retail_fans['member_id'];      //第一个分销员id
                                    }else{
                                        $retail_member_id = 0;      //第一个分销员id
                                    }
                                }
                                if( $retail_member_id != 0 ){
                                    //分销员有效,加入分销数据
                                    $retailUser = Db::name('retail_user') ->where('member_id',$retail_member_id)->field('one_member_id,two_member_id') ->find();
                                    $arr = [];
                                    $arr = [
                                        'member_id' => $retail_member_id,
                                        'buy_member_id' => $order['member_id'],
                                        'order_goods_id'=>$v['order_goods_id'],
                                        'order_id' => $order['id'],
                                        'price' => $v['own_ratio'],     //自购
                                        'amount' => $order['amount'] - $order['postage'],
                                        'status' => 0,
                                        'create_time' => time()
                                    ];
                                    array_push($orderRetail,$arr);  //自购分销金额
                                    if( !empty($retailUser['one_member_id']) ){
                                        $arr['member_id'] = $retailUser['one_member_id'];   //一级分销商
                                        $arr['price'] = $v['ratio'];   //一级分金额
                                        array_push($orderRetail,$arr);  //一级分销金额
                                    }
                                    if( !empty($retailUser['two_member_id']) ){
                                        $arr['member_id'] = $retailUser['two_member_id'];   //二级分销商
                                        $arr['price'] = $v['two_ratio'];   //二级分金额
                                        array_push($orderRetail,$arr);  //二级分销金额
                                    }
                                }
                            }
                        }
                        if( count($orderRetail) > 0 ){
                            Db::name('order_retail') ->insertAll($orderRetail); //添加分销订单
                        }
                    }
                    //根据订单判断是否为拼团的订单，需要去更改拼团表订单
                    if( $order['order_distinguish'] == 1 ){
                        Db::name('order_assemble_log') ->where('id',$order_assemble_log['id'])->setField('pay_way',1);
                        if( $order['commander_type'] == 1 ){    //表示团长
                            //表示开团,拼团人数永远不可能是一个人所以不需要判断拼团是否成功
                            //结束时间
                            $set_assemble_fail_time = Db::name('overtime_set') ->where('id',1)->value('set_assemble_fail_time');
                            //$active['end_time']活动的结束时间
                            if( (time() + ($set_assemble_fail_time*60*60)) > $active['end_time'] ){
                                $end_time = $active['end_time'];
                            }else{
                                $end_time = time() + ($set_assemble_fail_time*60*60);
                            }
                            $assemble_list_data = [];   //团组数据
                            $assemble_list_data = [
                                'assemble_id'   =>$order['event_id'],
                                'create_time'   =>time(),
                                'end_time'   =>$end_time,
                                'num'   =>$order_assemble_log['num'],
                                'r_num'   =>$order_assemble_log['num'] - 1,
                                'status'   =>1,
                                'assemble_price'   =>$order_assemble_log['commander_price'],
                                'old_price'   =>$order_assemble_log['old_price'],
                                'price'   =>$order_assemble_log['price'],
                            ];
                            $assemble_list_id = Db::name('assemble_list') ->insertGetId($assemble_list_data);   //加入组
                            $assemble_list_info = [];   //拼团详情的数据
                            $assemble_list_info = [
                                'assemble_list_id'      =>$assemble_list_id,
                                'order_id'      =>$order['id'],
                                'o_sn'      =>$order['sn'],
                                'item_id'      =>$order_assemble_log['item_id'],
                                'item_name'      =>$order_assemble_log['item_name'],
                                'real_price'      =>$order_assemble_log['real_price'],
                                'commander'      =>$order['commander_type'],
                                'num'      =>$order_assemble_log['buy_num'],
                                'create_time'      =>time(),
                                'status'      =>1,  //已支付
                                'member_id'      =>$order['member_id'],
                                'attr_ids'      =>$order_assemble_log['attr_ids'],
                                'attr_name'      =>$order_assemble_log['attr_name'],
                            ];
                            Db::name('assemble_info')->insert($assemble_list_info);
                            //发送模板消息
                            $post_data = [];
                            $post_data = [
                                'touser'    =>$memberInfo['openid'],
                                'url'    =>'https://www.ddxm661.com/h5/pages/group-buy/group?id='.$order['id'],
                                'template_id'    =>'i8DYaTbacUPtLQ2p05h5HmRfmwjnYljg68PckCMGlZc',
                                'data'    =>[
                                    'first' =>['value'=>'发起拼团成功啦'],
                                    'keyword1' =>['value'=>$order_assemble_log['item_name']],
                                    'keyword2' =>['value'=>$order_assemble_log['real_price']],
                                    'keyword3' =>['value'=>$order_assemble_log['num']],
                                    'keyword4' =>['value'=>!empty($memberInfo['wechat_nickname'])?$memberInfo['wechat_nickname']:$memberInfo['nickname']],
                                    'remark' =>['value'=>'捣蛋熊猫']
                                ]
                            ];
//                        (new WxPayModel()) ->send_message($post_data);
                        }else{
                            //表示参团,参团需要判断此拼团是否成功
                            $assemble_list_info = [];   //拼团详情的数据
                            $assemble_list_info = [
                                'assemble_list_id'      =>$order_assemble_log['assemble_list_id'],
                                'order_id'      =>$order['id'],
                                'o_sn'      =>$order['sn'],
                                'item_id'      =>$order_assemble_log['item_id'],
                                'item_name'      =>$order_assemble_log['item_name'],
                                'real_price'      =>$order_assemble_log['real_price'],
                                'commander'      =>$order['commander_type'],
                                'num'      =>$order_assemble_log['buy_num'],
                                'create_time'      =>time(),
                                'status'      =>1,  //已支付
                                'member_id'      =>$order['member_id'],
                                'attr_ids'      =>$order_assemble_log['attr_ids'],
                                'attr_name'      =>$order_assemble_log['attr_name'],
                            ];
                            Db::name('assemble_info')->insert($assemble_list_info);
                            //判断拼团是否成功
                            $new_assemble_list_info = Db::name('assemble_list')->where('id',$order_assemble_log['assemble_list_id'])->find();
                            $orders = Db::name('assemble_info') //方便平台成功或者失败的一起用
                            ->alias('a')
                                ->where('a.assemble_list_id',$order_assemble_log['assemble_list_id'])
                                ->join('member b','a.member_id=b.id')
                                ->field('a.member_id,a.o_sn,a.commander,a.real_price,a.order_id,b.openid,b.wechat_nickname')->select();
                            if( ($new_assemble_list_info['r_num']-1) == 0 ){
                                //表示拼团人数已满，拼团成功
                                $assemble_list_updata = [];
                                $assemble_list_updata = [
                                    'status'    =>2,
                                    'over_time'    =>time(),
                                    'r_num'    =>0,
                                ];
                                Db::name('assemble_list')->where('id',$order_assemble_log['assemble_list_id']) ->update($assemble_list_updata);
                                //修改订单表订单状态
                                $order_ids = array_column($orders,'order_id');
                                $orderWhere = [];
                                $orderWhere[] = ['id','in',implode(',',$order_ids)];
                                Db::name('order')->where($orderWhere)->setField('assemble_status',1);  //拼团成功
                                //发送模板消息，因为拼团成功，所以给每个团员都发送拼团成功消息
                                $menicknames = array_column($orders,'wechat_nickname');
                                $menickname = implode(',',$menicknames);
                                foreach ( $orders as $k=>$v ){
                                    //发送模板消息
                                    $post_data = [];
                                    $post_data = [
                                        'touser'    =>$v['openid'],
                                        'url'    =>'https://www.ddxm661.com/h5/pages/group-buy/group?id='.$v['order_id'],
                                        'template_id'    =>'5LAxRajXac7opCXdXOZbYz-OV5AuUdB834zi7TG-iOU',
                                        'data'    =>[
                                            'first' =>['value'=>'您有拼团成功啦'],
                                            'keyword1' =>['value'=>$v['o_sn']],
                                            'keyword2' =>['value'=>$menickname],
                                            'keyword3' =>['value'=>$order_assemble_log['item_name']],
                                            'keyword4' =>['value'=>$v['real_price']],
                                            'remark' =>['value'=>'捣蛋熊猫']
                                        ]
                                    ];
//                                (new WxPayModel()) ->send_message($post_data);
                                }

                            }else{
                                Db::name('assemble_list')->where('id',$order_assemble_log['assemble_list_id']) ->setDec('r_num',1);
                                //发送模板消息，因为拼团还未完，只需要给团长发送有人成功加团
                                //发送模板消息
                                foreach ( $orders as $k=>$v ){
                                    if( $v['commander'] == 1 ){
                                        $menickname = $v['wechat_nickname'];
                                        $op = $v['openid'];
                                        $op_id = $v['order_id'];
                                    }
                                }
                                $post_data = [];
                                $post_data = [
                                    'touser'    =>$op,
                                    'url'    =>'https://www.ddxm661.com/h5/pages/group-buy/group?id='.$op_id,
                                    'template_id'    =>'CMIdRnxrALTEUORfp7yxGiU1Mc6ArgDLxZoqXTKdOpk',
                                    'data'    =>[
                                        'first' =>['value'=>'您好，您有新成员加入'],
                                        'keyword1' =>['value'=>$memberInfo['wechat_nickname']],
                                        'keyword2' =>['value'=>date('Y-m-d H:i:s')],
                                        'remark' =>['value'=>'您可以到我的拼团详情查看']
                                    ]
                                ];
//                            (new WxPayModel()) ->send_message($post_data);
                            }
                        }
                        //增加活动的已拼团数量 $flash_sale_attr
                        $flash_sale_attr_new_data = [];
//                    if( $flash_sale_attr['stock'] != '-1' ){
//                        $flash_sale_attr_new_data['stock'] = $flash_sale_attr['stock'] - $order_assemble_log['buy_num'];
//                    }
                        if( $flash_sale_attr['residue_num'] != '-1' ){
                            $flash_sale_attr_new_data['residue_num'] = $flash_sale_attr['residue_num'] - $order_assemble_log['buy_num'];
                        }
                        $flash_sale_attr_new_data['already_num'] = $flash_sale_attr['already_num'] + $order_assemble_log['buy_num'];
                        $map = [];
                        $map[] = ['flash_sale_id','eq',$order['event_id']];
                        $map[] = ['item_id','eq',$order_assemble_log['item_id']];
                        $map[] = ['specs_ids','eq',$order_assemble_log['attr_ids']];
                        Db::name('flash_sale_attr') ->where($map)->update($flash_sale_attr_new_data);
                    }
                    //秒杀或抢购
                    if( $order['order_distinguish'] == 2 || $order['order_distinguish'] == 3 ){
                        //秒杀，更改秒杀表已抢购的数量（包含开始抢购的数量）
                        $order_goods = Db::name('order_goods')->where('order_id',$order['id'])->find();
                        $mamp = [];
                        $mamp[] = ['flash_sale_id','eq',$order['event_id']];
                        $mamp[] = ['item_id','eq',$order_goods['item_id']];
                        $mamp[] = ['specs_ids','eq',$order_goods['attr_ids']];
                        $assembleAttr = Db::name('flash_sale_attr')->where($mamp)->find();
                        Db::name('flash_sale_attr')->where($mamp) ->setInc('already_num',$order['number']);//增加已抢数量
                        if( $assembleAttr['residue_num'] != '-1' ){
                            Db::name('flash_sale_attr')->where($mamp)->setDec('residue_num',$order['number']);//减少可抢数量
                        }
                    }
                    //分销大礼包
                    if( $order['order_distinguish'] == 4 ){
                        $retail_member = Db::name('retail_user')->where('member_id',$order['member_id'])->find();
                        if( ($memberInfo['retail'] != 1) && (!$retail_member) ){
                            //不是分销员，则成为分销员
                            if( $order['share_id'] != 0 && $order['share_id'] != $order['member_id'] ){
                                //分享过来的
                                $share_info = Db::name('member')
                                    ->alias('a')
                                    ->where('a.id',$order['share_id'])
                                    ->join('retail_user b','a.id=b.member_id')
                                    ->field('a.id,a.status,a.retail,b.one_member_id')
                                    ->find();
                                if( ($share_info['status'] == 1) && ($share_info['retail'] == 1) ){
                                    //分享人状态正常且为分销员,则生成分销佣金
                                    $one_member_id = $share_info['id'];      //一级分销员id
                                    $two_member_id = $share_info['one_member_id'];
                                }else{
                                    //分享过来的用户失效,则查看本身的上级分销员
                                    $one_member_id = 0;     //一级分销员id
                                    $two_member_id = 0;     //二级分销员id
                                }
                            }else{
                                //不是分享过来的
                                $one_member_id = 0;     //一级分销员id
                                $two_member_id = 0;     //二级分销员id

                            }
                            $retail_user_data = [];
                            $retail_user_data = [
                                'member_id'     =>$order['member_id'],
                                'one_member_id'     =>$one_member_id,
                                'two_member_id'     =>$two_member_id,
                                'create_time'     =>time(),
                                'update_time'     =>time(),
                                'mobile'     =>$memberInfo['mobile'],
                                'name'     =>$memberInfo['wechat_nickname']
                            ];
                            Db::name('retail_user') ->insert($retail_user_data);
                            Db::name('retail_fans') ->where('fans_id',$order['member_id'])->setField('status',2);
                            Db::name('member') ->where('id',$order['member_id'])->setField('retail',1);
                            if( $one_member_id != 0 ){
                                //别人邀请成为的分销员,添加一条50已提现的分销订单
                                $new_arr = [];
                                $new_arr = [
                                    'member_id' => $one_member_id,
                                    'buy_member_id' => $order['member_id'],
                                    'order_goods_id'=>$item[0]['id'],
                                    'order_id' => $order['id'],
                                    'price' => 50,     //获得50
                                    'amount' => $order['amount'] - $order['postage'],
                                    'status' => 1,
                                    'create_time' => time(),
                                    'cut_of_time' => time()
                                ];
                                Db::name('order_retail') ->insert($new_arr);    //添加一条可提现
                                Db::name('member_money') ->where('member_id',$one_member_id)->setInc('retail_money',50);//添加50的可提现
                            }
                        }
                    }
                    //加入股东数据4
                    $statisticsData = [];   //股东数据
                    $arr = [];
                    $arr = [
                        'order_id'  =>$order['id'],
                        'shop_id'  =>$order['shop_id'],
                        'order_sn'  =>$order['sn'],
                        'type'  =>4,
                        'data_type'  =>1,
                        'pay_way'  =>3,
                        'price'  =>$order['amount']
                    ];
                    array_push($statisticsData ,$arr);
                    $res = controller('Base') ->addToStatistics($statisticsData);      //加入股东数据
                    if( $res['code'] != 200 ){
                        throw new \Exception('股东数据加入错误');     //php抛出异常
                    }else{
                        $res = 1;
                    }

                    //使用优惠券
                    if( !empty($order['coupon_id']) && !empty($order['c_receive_id']) ){
                        (new CouponReceiveModel()) ->where('id',$order['c_receive_id'])->update(['is_use'=>2,'use_time'=>time(),'order_id'=>$order['id']]);
                    }
                    //是否为新人专享
                    if ( $order['order_distinguish'] == 6 )
                    {
                        $pay_log_data = [];
                        $pay_log_data = [
                            'se_id' =>$exclusive_goods['se_id'],
                            'seg_id' =>$exclusive_goods['seg_id'],
                            'price' =>$order['amount'],
                            'item_id' =>$exclusive_goods['item_id'],
                            'attr_ids' =>$exclusive_goods['attr_ids'],
                            'member_id' =>$order['member_id'],
                            'order_id' =>$order['id'],
                            'create_time' =>time()
                        ];
                        $res = ( new StPayLog() ) ->insert($pay_log_data);
                        if( !$res )
                        {
                            throw new \Exception('加入购买记录表出错PAGE663');
                        }
                    }

                    //获取记录主键ID
                    $flow = Db::name('st_recharge_flow')->field('id,rec_id,discount_price')->where(['type'=>3,'order_id'=>$order['id'],'member_id'=>$order['member_id']])->select();
                    //是否使用抵扣（充值送抵扣金额活动）
                    if(!empty($flow))
                    {
                        //修改状态：3未支付->使用1
                        $res = Db::name('st_recharge_flow')->whereIn('id',array_column($flow,'id'))->setField('type',1);

                        //获取充值送余额
                        $flow_where[] = ['member_id','eq',$order['member_id']];
                        $flow_where[] = ['expires_time','egt',time()];
                        $flow_where[] = ['remain_price','gt',0];
                        $remain_price = Db::name('st_recharge')->where($flow_where)->sum('remain_price');

                        //修改使用金额
                        if($res)
                        {
                            //这里暂时不考虑优化
                            foreach ($flow as $key=>$value)
                            {
                                $res = Db::name('st_recharge')->where(['id'=>$value['rec_id']])->setDec('remain_price',$value['discount_price']);

                                if(!$res)
                                {
                                    return json(['code'=>100,'msg'=>'支付失败','data'=>'修改使用金额']);
                                }
                            }

                        }

                        //添加流水记录
                        if($res)
                        {
                            //拼接流水数据[3,4]依照money_type备注
                            foreach ([3,4] as $key=>$value)
                            {

                                $flow_data[]= [
                                    'member_id'=>$order['member_id'],
                                    'flow_code'=>'DDXM'.date('Ymd').rand('11111111','99999999'),//流水编号（格式：DDXM2020031288888888）
                                    'order_code'=>$order['sn'],
                                    'flow_type'=>1,
                                    'change_money'=>$value == 3 ? $order['amount'] : array_sum(array_column($flow,'discount_price')),
                                    'pre_change_money'=>$value == 3 ? $memberMoney['online_money'] : $remain_price,
                                    'after_change_money'=>$value == 3 ? bcsub($memberMoney['online_money'],$order['amount'],2) : bcsub($remain_price,array_sum(array_column($flow,'discount_price')),2),
                                    'pay_type'=>3,
                                    'money_type'=>$value//1普通余额、2限时余额、3线上余额、4抵扣余额（充值送活动）
                                ];
                            }

                            $res = (new FinancialFlow())->addFlow($flow_data);

                            $res = $res['status'] == 200 ? 1 : 0;
                        }
                    }
//                    //添加erp系统的待代购单
//                    if ( $res )
//                    {
//                        $result = Controller('Base')->getItemStore($order['id']);
//                        $result = json_decode($result,true);
//                        $res = $result['code']==200? 1:0;
//                    }
                    if ($res && ($order['wuyi_ok'] == 1) )
                    {
                        $time = time();
                        //赠送优惠券
                        if ( ($time>=1587571200) || ($time<=1588780800) )
                        {
                            $res = (new CouponReceiveModel())->giveCoupon2($order['wuyi_item_amount'],$order['member_id']);
                        }
                    }
                    // 提交事务
                    if($res)
                    {
                        Db::commit();
                    }else{
                        Db::rollback();
                        return json(['code'=>100,'msg'=>'支付失败','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                    }
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return json(['code'=>100,'msg'=>'服务器内部错误','data'=>array('id'=>$order['id'],'sn'=>$order['sn'])]);
                }
                $result = [
                    'id'=>$order['id'],
                    'sn'=>$order['sn'],
                    'order_distinguish'=>$order['order_distinguish'],
                    'money' =>$order['amount'],
                    'nickname' =>$order['realname'],
                    'address' =>$order['detail_address']
                ];

                //并发开锁
                flock($file,LOCK_UN);

                //动态修改Redis商品库存
                $k = $item[0]['item_id'].'_'.$item[0]['attr_ids'];
                if(redisObj()->exists($k))
                {
                    redisObj()->rpop($k);
                }

                return json(['code'=>200,'msg'=>'支付成功','data'=>$result]);
            } else {
                flock($file,LOCK_UN);
                return json(['code' => 100, 'msg' => '请勿重复操作', 'data' => array('id' => $order['id'], 'sn' => $order['sn'])]);
            }

            fclose($file);
        }
    }

    /***
     * @param $memberId
     * @param $allPrice
     * @return array|bool,其中根据数组的id修改use_price值,consume_price每条记录使用的金额
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function gerMember( $allPrice , $memberId  ){
        //查询限时余额
        $expireMoneyWhere = [];
        $expireMoneyWhere[] = ['member_id','eq',$memberId];
        $expireMoneyWhere[] = ['status','eq',1];
        $expireMoneyWhere[] = ['expire_time','>=',time()];
        $expireList = Db::name('member_money_expire')->where($expireMoneyWhere)->order('id asc')->field('id,price,use_price')->select();
        if( count($expireList) <= 0 ){
            return false;
        }
        foreach ($expireList as $k=>$v){
            if( $v['price'] == $v['use_price'] ){
                unset($expireList[$k]);
            }
        }
        $res = [];
        foreach ( $expireList as $k=>$v ){
            $arr = [];
            if( ($v['price']-$v['use_price']) < $allPrice ){
                $arr = array(
                    'id'    =>$v['id'],
                    'use_price' =>$v['price'],
                    'consume_price' =>$v['price']-$v['use_price']
                );
                array_push($res,$arr);
                $allPrice = $allPrice - ($v['price']-$v['use_price']);
            }else{
                $arr = array(
                    'id'    =>$v['id'],
                    'use_price' =>$v['use_price'] + $allPrice,
                    'consume_price' =>$allPrice
                );
                array_push($res,$arr);
                break;
            }
        }
        return $res;
    }

    /***
     * 获取会员不可用的限时余额
     * @param $member_id
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNotUsePrice($member_id)
    {
        $where = [];
        $where[] = ['member_id','eq',$member_id];
        $where[] = ['status','neq',2];
        $list = Db::name('member_money_expire')->where($where)->select();
        if ( count($list) == 0 )
        {
            return 0;
        }
        $price = 0;
        foreach ( $list as $k=>$v ){
            if ( $v['status']==0 || ($v['expire_time']<time()) )
            {
                //未激活的限时余额不可用,已激活但是还未改变状态得限时余额不可用
                $price += $v['price']-$v['use_price'];
            }
        }
        return $price;
    }
}