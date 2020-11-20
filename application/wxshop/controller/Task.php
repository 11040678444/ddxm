<?php
namespace app\wxshop\controller;
/***
 * 商城定时任务
 */

use app\common\model\UtilsModel;
use Predis\Client;
use think\Controller;
use think\Db;
use app\common\model\WxPayModel;
use app\wxshop\model\coupon\CouponReceiveModel;

class Task extends Controller
{
    /***
     * 订单待付款超时
     */
    public function orderTimeOutTask(){
        //获取未支付的订单列表
        $where = [];
        $where[] = ['pay_status','eq',0];
        $where[] = ['is_online','eq',1];
        $where[] = ['is_online','eq',1];
        $orderList = Db::name('order') ->where($where) ->field('id,add_time,order_distinguish,member_id')->select();//商城所有未支付的订单
        //获取自动取消的时长
        $timeOut = Db::name('overtime_set') ->find();
        foreach ( $orderList as $k=>$v ) {
            /**普通订单超时**/
            if( $v['order_distinguish'] == 0 ){
                //0：普通订单
                $outTime = $timeOut['set_waitpay_time']*60*60;      //时差秒数
            }else if( $v['order_distinguish'] == 2 ){
                //2：抢购订单
                $outTime = $timeOut['set_rob_waitpay_time']*60; //时差秒数
            }else if( $v['order_distinguish'] == 1 ){
                //1：拼团订单
                $outTime = $timeOut['set_group_waitpay_time']*60*60; //时差秒数
            }
            if( time() > ($v['add_time'] + $outTime) ){
                //订单取消
                $update_order = [];
                $update_order = [
                    "pay_status" =>-1,
                    "order_status"=>-8,
                    "canceltime"=>time(),
                    "cancel_way"=>1,
                ];
                $attr = [];
                $attr = [
                    "order_id"=>$v['id'],
                    "status"  => -8,
                    "title"   => "订单取消",
                    "add_time"=>time(),
                ];
                // 启动事务
                Db::startTrans();
                try {
                    Db::name('order') ->where('id',$v['id']) ->update($update_order);
                    $result = db::name("order_attr")->insert($attr);
                    //判断是否为拼团订单,如果是拼团订单,判断此拼团是否成功
                    if( $v['order_distinguish'] == 1 ){
                        //拼团订单
                        $assemble_info_order = Db::name('assemble_info') ->where('order_id',$v['id'])
                            ->field('id,assemble_list_id,commander,status') ->find();
                        if( $assemble_info_order['status'] == 0 ){
                            //修改拼团详情订单支付状态
                            Db::name('assemble_info') ->where('order_id',$v['id'])->setField('status',2);
                            //修改拼团组表
                            if( $assemble_info_order['commander'] == 1 ){
                                //团长取消则这个组都取消
                                $assemble_list_update = [];
                                $assemble_list_update = [
                                    'status'    =>3,
                                    'reason'    =>'团长订单超时商城自动取消订单',
                                    'over_time' =>time()
                                ];
                                Db::name('assemble_list')->where('id',$assemble_info_order['assemble_list_id'])->update($assemble_list_update);
                            }else{
                                //拼团团员取消订单
                                Db::name('assemble_list')->where('id',$assemble_info_order['assemble_list_id'])->setInc('r_num');
                            }
                        }
                    }
                    if( ($v['order_distinguish'] == 2) || ($v['order_distinguish'] == 3) ){
                        $goods = Db::name('order_goods')->where('order_id',$v['id'])->find();
                        $newIds = $goods['item_id'].'_'.$goods['attr_ids'];

                        /**
                         * 处理使用redis的订单
                         */
                        $uid = $v['member_id'];
                        if(redisObj()->exists($newIds.'_'.'residue_num') && redisObj()->exists($newIds.'_'.$uid))
                        {
                            //回滚限购数量
                            redisObj()->incrby($newIds.'_'.'residue_num',$goods['num']);

                            //回滚用户购买数量
                            for ($i=0;$i<$goods['num'];$i++)
                            {
                                redisObj()->rpush($newIds.'_'.$uid,1);
                            }
                        }
                    }
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
    }

    /**
     * 未成功拼团超时
     */
    public function assembleErrorTask(){
        $assemble_list = Db::name('assemble_list')
            ->alias('a')
            ->where('a.status',1)
            ->join('flash_sale b','a.assemble_id=b.id','LEFT')
            ->field('a.*,b.end_time as assemble_end_time,b.auto')
            ->select();
        foreach ( $assemble_list as $k=>$v ){
            if( !empty($v['assemble_end_time']) && ($v['end_time'] > $v['assemble_end_time']) ){
                $assemble_list[$k]['end_time'] = $v['assemble_end_time'];
            }
        }
        foreach ( $assemble_list as $k=>$v ){
            if( $v['auto'] == 1 ){
                continue ;
            }
            if( time() > $v['end_time'] ){
                //到了结束时间,修改状态
                // 启动事务
                Db::startTrans();
                try {
                    //将拼团组的状态改为已关闭
                    $list_data = [];
                    $list_data = [
                        'reason'    =>'组团时间结束关闭',
                        'status'    =>3,
                        'over_time'    =>time()
                    ];
                    Db::name('assemble_list') ->where('id',$v['id']) ->update($list_data);
                    $where = [];
                    $where[] = ['a.assemble_list_id','eq',$v['id']];
                    $where[] = ['a.status','neq',2];        //查询此拼团组的 已支付和未支付的订单
                    $assemble_info = Db::name('assemble_info')
                        ->alias('a')
                        ->where($where)
                        ->join('order b','a.order_id=b.id')
                        ->field('a.*,b.amount')
                        ->select();
                    if( count($assemble_info) > 0 ){
                        //存在已付款的成员,退款金额
                        foreach ( $assemble_info as $k1 =>$v1 ) {
                            if( $v['status']==0 ){
                                //未支付，只需要更改订单状态
                                $update_order = [];
                                $update_order = [
                                    "pay_status" =>-1,
                                    "order_status"=>-8,
                                    "canceltime"=>time(),
                                    "cancel_way"=>1,
                                ];
                                Db::name('order') ->where('id',$v1['order_id']) ->update($update_order);
                            }else{
                                //已支付，修改订单表状态
                                $order = Db::name('order') ->where('id',$v1['order_id']) ->find();
                                $money = $order['amount'];
                                $pay_way = Db::name('order')->where('id',$v1['order_id'])->value('pay_way');
                                $member_info = Db::name('member')->where('id',$v1['member_id'])->find();
                                if( $pay_way == 1 ){
                                    //退微信
                                    $assemble_error_sn = 'TD'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                                    //退款
                                    $data =[
                                        'order_sn'=>$v1['o_sn'],//商品订单号
                                        'refund_no'=>$assemble_error_sn,//退单订单号
                                        'total_fee'=>$money,//商品支付的时候价格
                                        'refund_fee'=>$money,
                                    ];
                                    $da = (new WxPayModel())->refund($data);
                                    if(isset($da['err_code'])){
                                        return json(['code'=>100,'msg'=>'退款失败，请重试',"data"=>'']);
                                    }
                                }else{
                                    //退余额
                                    //查看是否使用了线上余额
                                    $log = Db::name('order_money_log') ->where('order_id',$v1['order_id'])->find();
                                    if( $log ){
                                        //1：返用户余额
                                        $aMoney = $log['putong_money']+$log['xianshi_money'];
                                        Db::name('member_money') ->where('member_id',$v1['member_id'])->setInc('money',$aMoney);
                                        Db::name('member_money') ->where('member_id',$v1['member_id'])->setInc('online_money',$log['xianshang_money']);
                                    }
                                    //2：增加用户的余额使用记录
                                    $detailsData = [];
                                    $detailsData = [
                                        'member_id' =>$member_info['id'],
                                        'mobile' =>$member_info['mobile'],
                                        'remarks' =>'小程序退单',
                                        'reason' =>'小程序购买商品',
                                        'addtime' =>time(),
                                        'amount' =>'-'.$money,
                                        'type' =>2,
                                        'order_id' =>$v1['order_id'],
                                    ];
                                    Db::name('member_details')->insert($detailsData);
                                    //3：判断是否是使用的限时余额
                                    $expireLog = Db::name('member_expire_log')->where('member_id',$v1['member_id'])
                                        ->where('order_id',$v1['order_id'])
                                        ->select();

                                    if( count($expireLog) > 0 ){
                                        $money1 = $money;       //单独赋值一个money1主要是为了返回限时余额使用,$money下面的操作还得用
                                        foreach ( $expireLog as $k2=>$v2 ){
                                            //1：给member_expire_log表新增一个负的退款记录
                                            if( $money1 >= 0 ){
                                                $arr = [];
                                                $expirePrice = 0;
                                                if( $money1 >= $v2['price'] ){
                                                    $expirePrice = $v2['price'];
                                                }else{
                                                    $expirePrice = $money1;
                                                }
                                                $arr = [
                                                    'member_id' =>$v1['member_id'],
                                                    'order_id' =>$v1['order_id'],
                                                    'price' =>'-'.$expirePrice,
                                                    'money_expire_id' =>$v2['money_expire_id'],
                                                    'order_sn' =>$v2['order_sn'],
                                                    'create_time' =>time(),
                                                    'reason' =>'退款:小程序退单'
                                                ];
                                                Db::name('member_expire_log')->insert($arr);
                                                //2：给member_money_expire表减少使用金额
                                                Db::name('member_money_expire') ->where('id',$v2['money_expire_id'])->setDec('use_price',$expirePrice);

                                                //判断限时余额是否过期、如果过期则生成一笔正的余额消耗
//                                                $member_money_expire = Db::name('member_money_expire')->where('id',$v2['money_expire_id'])->find();
//                                                if( $member_money_expire['status'] == 2 || (time()>$member_money_expire['expire_time']) ){
//                                                    $aee = [];
//                                                    $sn = 'WME'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$order['shop_id'];
//                                                    $aee = [
//                                                        'order_id'  =>$v1['order_id'],
//                                                        'shop_id'   =>$order['shop_id'],
//                                                        'order_sn'  =>$sn ,
//                                                        'type'      =>4,
//                                                        'data_type' =>1,
//                                                        'pay_way'   =>3,
//                                                        'price'   =>$expirePrice,
//                                                        'create_time'   =>time(),
//                                                        'title'   =>'退款时限时余额到期',
//                                                    ];
//                                                    Db::name('statistics_log')->insert($aee);   //生成股东数据
//                                                    //将限时余额过期
//                                                    Db::name('member_money_expire')->where('id',$v2['money_expire_id'])->setField('status',2);
//                                                    $ar = [];
//                                                    $ar = [
//                                                        'money_expire_id'    =>$v2['money_expire_id'],
//                                                        'member_id'    =>$v2['member_id'],
//                                                        'shop_id'    =>$order['shop_id'],
//                                                        'sn'    =>$sn,
//                                                        'price'    =>$expirePrice,
//                                                        'craete_time'    =>time(),
//                                                        'remarks'   =>'限时余额退款时已过期'
//                                                    ];
//                                                    Db::name('money_expire_log')->insert($ar);
//                                                    //1：减余额
//                                                    $res = Db::name('member_money') ->where('member_id',$v1['member_id'])->setDec('money',$expirePrice);
//                                                    //2：增加用户的余额使用记录
//                                                    $detailsData = [];
//                                                    $detailsData = [
//                                                        'member_id' =>$member_info['id'],
//                                                        'mobile' =>$member_info['mobile'],
//                                                        'remarks' =>'小程序退单时限时余额过期',
//                                                        'reason' =>'小程序退单时限时余额过期',
//                                                        'addtime' =>time(),
//                                                        'amount' =>$expirePrice,
//                                                        'type' =>2,
//                                                        'order_id' =>$v1['order_id'],
//                                                    ];
//
//                                                    Db::name('member_details')->insert($detailsData);
//                                                }

                                                //添加用户使用详情
                                                $money1 -= $expirePrice;
                                            }
                                        }
//                                        if($money1 > 0){
//                                            //1：返用户余额
//                                            Db::name('member_money') ->where('member_id',$v1['member_id'])->setInc('money',$money1);
//                                            //2：增加用户的余额使用记录
//                                            $detailsData = [];
//                                            $detailsData = [
//                                                'member_id' =>$member_info['id'],
//                                                'mobile' =>$member_info['mobile'],
//                                                'remarks' =>'小程序退单',
//                                                'reason' =>'小程序购买商品',
//                                                'addtime' =>time(),
//                                                'amount' =>'-'.$money1,
//                                                'type' =>2,
//                                                'order_id' =>$v1['order_id'],
//                                            ];
//                                            Db::name('member_details')->insert($detailsData);
//                                        }
                                    }
//                                    else{
//                                        //1：返用户余额
//                                        Db::name('member_money') ->where('member_id',$v1['member_id'])->setInc('money',$money);
//                                        //2：增加用户的余额使用记录
//                                        $detailsData = [];
//                                        $detailsData = [
//                                            'member_id' =>$member_info['id'],
//                                            'mobile' =>$member_info['mobile'],
//                                            'remarks' =>'小程序退单',
//                                            'reason' =>'小程序购买商品',
//                                            'addtime' =>time(),
//                                            'amount' =>'-'.$money,
//                                            'type' =>2,
//                                            'order_id' =>$v1['order_id'],
//                                        ];
//                                        Db::name('member_details')->insert($detailsData);
//                                    }
                                }
//            退单状态0:正常    1退款中 2 退款成功 3 退款关闭 4 待寄件 5 退款拒绝 6 退款取消（用户手动取消退款） 7 退货寄件中
                                $update_order = [
                                    "refund_status"=>2,
                                    "order_status"=>-6,
                                ];
                                $update_order_goods = [
                                    "refund_status"=>2,
                                    "status"=>2,
                                ];
                                //  更改对应的订单状态
                                Db::name("order")->where("id",$v1['order_id'])->update($update_order);

                                //更改对应的订单 明细 状态
                                Db::name("order_goods")->where("order_id",$v1['order_id'])->update($update_order_goods);

                                //根据$goods_id退回商品库存
                                $goodsList = Db::name('order_goods') ->where('order_id',$v1['order_id'])->field('num,item_id,attr_ids')->find();
                                $map = [];
                                $map[] = ['gid','eq',$goodsList['item_id']];
                                $map[] = ['key','eq',empty($goodsList['attr_ids'])?'':$goodsList['attr_ids']];
                                $map[] = ['status','eq',1];
                                $lt = Db::name('specs_goods_price') ->where($map) ->find();
                                if( $lt['store'] != '-1' ){
                                    Db::name('specs_goods_price') ->where($map) ->setInc('store',$goodsList['num']);
                                }

                                $sn = 'td'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),
                                        1))),0,8);
                                //退单信息
                                $apply = [
                                    "order_id"=>$v1['order_id'],
                                    "goods_id"=>$goodsList['id'],
                                    "remarks" =>'拼团过期自动退款',
                                    "reason" =>'拼团过期自动退款',
                                    "type"=>2,
                                    "pic"=>'',
                                    "status"=>2,
                                    "sn"=>$sn,
                                    "add_time"=>time(),
                                    'handle_time'=>time(),
                                    'money' =>$money,
                                    'operator_id'=>1
                                ];
                                Db::name('order_refund_apply')->insert($apply);

                                //股东数据
                                $statisticsInfo = [];//具体股东数据
                                $statisticsInfo = [
                                    'order_id'  =>$v1['order_id'],
                                    'shop_id'   =>$order['shop_id'],
                                    'order_sn'   =>$order['sn'],
                    //                'type'   =>4,
                                    'data_type'   =>2,
                    //                'pay_way'   =>1,
                                    'price'   =>'-'.$money,
                                    'create_time'   =>time(),
                                    'title'   =>'小程序购买商品',
                                ];
                                $statisticsData = [];//总的股东数据
                                //1:余额充值,2:购卡,3:消费收款,4:余额消耗,5消费消耗,6商品外包分润,7推拿外包分润,8商品成本,9营业费用,10外包商品成本'
                                //A先加入订单股东数据
                                if( $order['pay_way'] == 1 ){
                                    //微信
                                    $statisticsInfo['pay_way'] = 1;
                                    $statisticsInfo['type'] = 3;
                                    array_push($statisticsData,$statisticsInfo);
                                    $statisticsInfo['type'] = 5;
                                    array_push($statisticsData,$statisticsInfo);
                                }else{
                                    //余额
                                    $statisticsInfo['pay_way'] = 3;
                                    $statisticsInfo['type'] = 4;
                                    array_push($statisticsData,$statisticsInfo);
                                }
                                Db::name('statistics_log') ->insertAll($statisticsData);
                                //改变分销订单状态
                                Db::name('order_retail')->where('order_id',$order['id'])->setField('status',2);
                            }
                        }
                    }
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    dump($e->getMessage());
                }
            }
        }
    }

    /***
     * 分销订单定时结算
     */
    public function countOrderRetailTask(){
        $timeOut = Db::name('overtime_set') ->find();
        $timeOver = $timeOut['set_retai_order_time']*24*60*60;
        $where = [];
//        $where[] = ['o.order_status','eq',2];       //订单完成
        $where[] = ['o.is_online','eq',1];       //线上商城
        $where[] = ['r.status','eq',0];       //未结算
        $orderList = Db::name('order_retail')
            ->alias('r')
            ->join('order o','a.order_id=o.id')
            ->where($where)
            ->field('r.id,r.order_id,r.price,r.amount,o.overtime,o.order_status,o.refund_status')
            ->select();
        if( count($orderList) > 0 ){
            $status1 = [];      //表示已结算
            $status2 = [];      //表示已退单
            foreach ( $orderList as $k=>$v ){
                if( time() > ($v['overtime']+$timeOver) ) {
                    //时间到
                    if( $v['order_status'] == 2 && $v['refund_status'] == 0 ){
                        //已完成，未退单，表示为已结算
                        array_push($status1,$v['id']);
                    }else if( $v['refund_status'] ==2 ) {
                        //已退单
                        array_push($status2,$v['id']);
                    }
                }
            }
            if( count($status1) > 0 ){
                $map = [];
                $map[] = ['id','in',implode(',',$status1)];
                Db::name('order_retail') ->where($map)->setField(['cut_of_time'=>time(),'status'=>1]);
            }
            if( count($status2) > 0 ){
                $map = [];
                $map[] = ['id','in',implode(',',$status2)];
                Db::name('order_retail') ->where($map)->setField(['status'=>2]);
            }
        }
    }

    /***
     * 限时余额过期
     */
    public function moneyExpireTask(){
        $list = Db::name('member_money_expire')
            ->where('status',1)
            ->select(); //已激活
        foreach ( $list as $k=>$v ){
            if( $v['expire_time']<=time() ){
                // 启动事务
                Db::startTrans();
                try {
                    //修改状态
                    Db::name('member_money_expire')->where('id',$v['id'])->setField('status',2);    //改为已过期
                    $noMoney = $v['price'] - $v['use_price'];
                    if( $noMoney >0 ){
                        $memberInfo = Db::name('member')->where('id',$v['member_id'])->field('shop_code,mobile')->find();
                        if( empty($memberInfo['shop_code']) ){
                            $code = 'A00000';
                        }else{
                            $code = $memberInfo['shop_code'];
                        }
                        $shopId = Db::name('shop')->where('code',$code)->value('id');

                        //更改会员余额
                        Db::name('member_money')->where('member_id',$v['member_id'])->setDec('money',$noMoney); //减少余额
                        //添加余额明细
                        $details_data = [
                            'member_id' =>$v['member_id'],
                            'mobile' =>$memberInfo['mobile'],
                            'remarks' =>$v['id'].'限时余额到期扣款',
                            'reason' =>'限时余额到期扣款',
                            'addtime' =>time(),
                            'amount' =>$noMoney,
                            'type' =>5,
                        ];
                        Db::name('member_details')->insert($details_data);
    //                    //限时余额使用记录表
    //                    $expire_log_data = [
    //                        'member_id' =>$v['member_id'],
    //                        'order_id' =>0,
    //                        'price' =>$noMoney,
    //                        'money_expire_id' =>$v['id'],
    //                        'order_sn'  =>'',
    //                        'create_time'   =>time(),
    //                        'reason'    =>'限时余额到期扣款'
    //                    ];
                        //生成限时余额过期列表订单ddxm_money_expire_log
                        $sn = 'WME'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
                        $ar = [];
                        $ar = [
                            'money_expire_id'    =>$v['id'],
                            'member_id'    =>$v['member_id'],
                            'shop_id'    =>$shopId,
                            'sn'    =>$sn,
                            'price'    =>$noMoney,
                            'craete_time'    =>time(),
                            'remarks'   =>'限时余额过期'
                        ];
                        Db::name('money_expire_log')->insert($ar);
                        //添加股东数据/余额消耗
                        $statistics_log_data = [
                            'shop_id'   =>$shopId,
                            'order_id'=>0,
                            'order_sn'=>$sn.$shopId,
                            'type'  =>4,
                            'data_type'=>1,
                            'pay_way'   =>3,
                            'price' =>$noMoney,
                            'create_time'   =>time(),
                            'title' =>'限时余额到期'
                        ];
                        Db::name('statistics_log')->insert($statistics_log_data);
                    }
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    dump($e->getMessage());die;
                }
            }
        }
    }

    /***
     * 分销订单未结算判断订单的结算和已失效
     */
    public function orderRetailTask(){
        $list = Db::name('order_retail')
            ->where('status',0)
            ->select();
        foreach ( $list as $k=>$v ){
            $order = Db::name('order')->where('id',$v['order_id'])
                ->field('order_status,refund_type,refund_status')
                ->find();
            if( $order['refund_type'] == 1 && $order['refund_status']==2 ){   //有退货且退款成功
                //修改分销订单表的状态
                Db::name('order_retail') ->where('id',$v['id'])->setField('status',2);
            }else{

            }


        }
    }

    /***
     * 累计送
     */
    public function grandTotalTask(){
        $where = [];
        $where[] = ['end_time','<=',time()];    //已经到了结算时间
        $where[] = ['status','eq',0];       //未结算的
        $list = Db::name('grand_total')->where($where)->select();
        if( count($list)>0 ){
            foreach ( $list as $k=>$v ){
                //查询订单
                $emp =[];
                $emp[] = ['member_id','eq',$v['member_id']];
                $emp[] = ['type','neq',3];
                $emp[] = ['pay_status','eq',1];
                $emp[] = ['is_online','eq',1];
                $emp[] = ['refund_status','eq',0];
                $emp[] = ['refund_type','eq',0];
                $emp[] = ['pay_way','eq',1];
                $emp[] = ['paytime','>=',$v['start_time']];
                $emp[] = ['paytime','<=',$v['end_time']];
                $accumulative_total = Db::name('order')->where($emp)->sum('amount');    //查询会员当月累计微信消费
                $shop_code = Db::name('member')->where('id',$v['member_id'])->field('id,shop_code,mobile,level_id')->find();
                if( !$shop_code ){
                    break;
                }
                $shop_id = Db::name('shop')->where('code',$shop_code['shop_code'])->value('id'); //门店id
                //判断充值是否满赠送活动（模拟下单、模拟会员下单）
                $map = [];
                $map[] = ['shop_id','eq',$shop_id];
                $map[] = ['type','eq',2];
                $map[] = ['status','eq',1];
                $map[] = ['price','<=',$accumulative_total];
                $active = Db::name('online_activity')->where($map)->order('price desc')->find();//按金额降序排序满足的第一条
                // 启动事务
                Db::startTrans();
                try {
                    //送卡
                    if( $active ){
                        if( $active['activity_type'] == 1 && !empty($active['activity_type_id']) ){
                            //赠送服务卡
                            $worker = Db::name('shop_worker')
                                ->where('sid',$shop_id)
                                ->where('status',1)
                                ->where('post_id',1)
                                ->find();
                            $card_id = $active['activity_type_id']; //服务卡id
                            $cardMoney = Db::name('ticket_money')->where('card_id',$card_id)->where('level_id',1)
                                ->field('price')->find();       //查询最高价为原价
                            $data = db::name("ticket_card")->where("id",$card_id)->find();   //服务卡信息
                            //购买记录
                            $ticket =[
                                "shop_id"=>$shop_id,
                                "order_id"=>0,
                                "member_id"=>$v['member_id'],
                                "mobile"=>$shop_code['mobile'],
                                "ticket_id"=>$card_id,
                                "status"=>0,        //状态：未激活,表示未领取
                                "price" =>!empty($cardMoney['price'])?$cardMoney['price']:0,
                                "real_price"=>0,
                                "start_time"=>time(),    //未激活状态下：过期时间为一个月
                                "end_time"=>strtotime('+1 month'), //未激活状态下：过期时间为一个月
                                "create_time"=>time(),
                                "waiter"=>$worker['name'],
                                "waiter_id"=>$worker['id'],
                                "over_time"=>$data["day"] ==0?0:strtotime('+'.$data["day"].'day'),
                                "type" =>$data['type'],
                                "month"=>$data['month'],
                                "year" =>$data['year'],
                                "day"=>$data['use_day'],
                                "level_id"=>$shop_code['level_id'],
                                "give_type"=>2, //2赠送
                                "remarks"=>date('Y-m-d H:i:s').'充值满'.$active['price'].'元,商城活动赠送服务卡：'.$data['card_name'],
                                "is_online" =>1 //线上商城赠送的服务卡
                            ];
                            $cardId = Db::name("ticket_user_pay")->insertGetId($ticket);   //赠送服务卡
                            //ddxm_member_ticket赠送表
                            $arr = [];
                            $arr = [
                                'member_id' =>$v['member_id'],
                                'card_id' =>$cardId,
                                'status' =>0,
                                'create_time' =>time(),
                                'receive_expire_time' =>strtotime('+1 month'),
                                'receive_time' =>0,
                                'use_expire_time' =>0,
                                'ticket_id' =>$card_id,
                                'order_id' =>$v['id'],
                                'type' =>2,
                            ];
                            Db::name('member_ticket') ->insert($arr);
                        }
                    }
                    //改为已结算
                    $update = [];
                    $update = [
                        'status'    =>1,
                        'give_status'=> !empty($active)?1:0
                    ];
                    Db::name('grand_total')->where('id',$v['id'])->update($update);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
    }

    /***
     * 自动成团./直接添加所有团员消息
     */
    public function autoAssembleTask(){
        $set_assemble_success_time = Db::name('overtime_set') ->where('id',1)->value('set_assemble_success_time');
        $where = [];
        $where[] = ['a.status','eq',1];     //拼团中
//        $where[] = ['a.end_time','>=',time()];  //未结束
        //结束之前的设定时间
//        $set_assemble_success_time = $set_assemble_success_time*60 + time();
//        $where[] = ['a.end_time','<=',$set_assemble_success_time];  //未结束
        $where[] = ['b.auto','eq',1];   //自动成团
        $list = Db::name('assemble_list') ->alias('a')
            ->join('flash_sale b','a.assemble_id=b.id')
            ->where($where)
            ->field('a.*')
            ->select();
        if( count($list) > 0 ){
            foreach ( $list as $k=>$v ){
                if ( $v['end_time']-time() <= 30*60 )
                {
                    //做成团操作
                    $memberIds = Db::name('member')->where('is_fictitious',1)->limit($v['r_num'])->orderRand()->field('id')->select();  //选出虚拟用户
                    // 启动事务
                    Db::startTrans();
                    try {
                        //设置状态
                        $update_assemble_list = [];
                        $update_assemble_list = [
                            'r_num' =>0,
                            'status'   =>2,
                            'over_time' =>time()
                        ];
                        Db::name('assemble_list')->where('id',$v['id'])->update($update_assemble_list); //设置成团状态

                        //修改订单状态
                        $orders = Db::name('assemble_info') ->where('assemble_list_id',$v['id'])->column('order_id');
                        $orderMap = [];
                        $orderMap[] = ['id','in',implode(',',$orders)];
                        Db::name('order')->where($orderMap)->setField('assemble_status',1);  //拼团成功

                        //修改订单信息
                        $update_assemble_info = [];
                        $arr = [];
                        $arr = [
                            'assemble_list_id'  =>$v['id'],
                            'order_id'  =>0,        //订单id为0表示为虚拟的人成的团
                            'o_sn'      =>'',
                            'item_id'   =>0,
                            'item_name'=>'',
                            'real_price'=>0,
                            'commander'=>2,
                            'num'=>1,
                            'create_time'=>time(),
                            'status'=>1,
                            'member_id'=>1,
                            'attr_ids'=>'',
                            'attr_name'=>''
                        ];
                        foreach ( $memberIds as $mk=>$mv ){
                            $arr['member_id'] = $mv['id'];
                            array_push($update_assemble_info,$arr);
                        }
                        $assemble_info_add_ids = [];
                        foreach ( $update_assemble_info as $aik=>$alv ){
                            $id = Db::name('assemble_info') ->insertGetId($alv);
                            array_push($assemble_info_add_ids,$id);
                        }

                        $assemble_list_log = [];
                        $assemble_list_log = [
                            'assemble_id'   =>$v['assemble_id'],
                            'assemble_list_id'   =>$v['id'],
                            'assemble_info_ids'   =>implode(',',$assemble_info_add_ids),
                            'reality_num'   =>$v['num'] - $v['r_num'],
                            'fictitious_num'   =>$v['r_num'],
                            'all_num'   =>$v['num'],
                            'create_time'   =>time()
                        ];
                        Db::name('assemble_list_log') ->insert($assemble_list_log);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }
        }
    }

    /***
     * 分销自动结算
     */
    public function autoSettlementTask(){
        $time = Db::name('overtime_set')->where('id',1)->value('set_retai_order_time'); //确认收货结算后结算时间
        $timeWhere =  time()-($time*24*60*60);   //七天之后

        $where = [];
        $where[] = ['b.add_time','<=',$timeWhere];    //确认收货之后的订单
        $where[] = ['b.status','eq',2];   //已确认收货
        $where[] = ['a.status','eq',0];     //未结算
        $list = Db::name('order_retail')->alias('a')
            ->join('order_attr b','a.order_id=b.order_id')
            ->field('a.*')
            ->where($where)
            ->select();
        if( count($list) >0 ){
            foreach ( $list as $k=>$v ){
                // 启动事务
                Db::startTrans();
                try {
                    //添加会员的可提现金额
                    Db::name('member_money') ->where('member_id',$v['member_id'])->setInc('retail_money',$v['price']);
                    //更改状态：
                    Db::name('order_retail')->where('id',$v['id'])->update(['status'=>1,'cut_of_time'=>time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
            }
        }
    }

    /***
     *7天自动收货
     */
    public function autoTakeItemTask(){
        $time2 = (time()) - (30*24*60*60);
        $overtime_set = Db::name('overtime_set') ->find();
        $time = (time()) - ($overtime_set['set_takedelivery_time']*24*60*60);   //普通订单自动确认收货时间
        $time3 = (time()) - ($overtime_set['set_ca_takedelivery_time']*24*60*60);   //跨境购订单确认收货时间
        $where = [];
        $where[] = ['pay_status','eq',1];
        $where[] = ['order_status','eq',1]; //待收货
        $order_list = Db::name('order') ->where($where)->select();
        // dump($order_list);die;
        if( count($order_list) >0 ){
            foreach ( $order_list as $k=>$v ){
                if( empty($v['cross_border']) ){
                    //之前的订单，按照目前设计的做
                    if( !empty($v['sendtime']) ){
                        if( $v['sendtime'] > $time ) {
                            continue;  //在收货七天之内，返回
                        }
                    }else{
                        if( $v['paytime'] > $time2){
                            continue;  //在支付30之内，返回
                        }
                    }
                }else if( $v['cross_border'] == 1 ){
                    //跨境购
                    if( !empty($v['sendtime']) ){
                        if( $v['sendtime'] > $time3 ) {
                            continue;  //在收货七天之内，返回
                        }
                    }else{
                        if( $v['paytime'] > $time3){
                            continue;  //在支付30之内，返回
                        }
                    }
                }else if( $v['cross_border'] == 2 ){
                    // dump($v['sendtime']);
                    //   	dump($time);
                    //   	die;
                    //普通订单
                    if( !empty($v['sendtime']) ){
                        if( $v['sendtime'] > $time ) {
                            continue;  //在收货七天之内，返回
                        }
                    }else{
                        if( $v['paytime'] > $time2 ){
                            continue;  //在支付30之内，返回
                        }
                    }
                }

                //判断订单是否全部发货，如果没有则跳出
                $map = [];
                $map[] = ['order_id','eq',$v['id']];
                $map[] = ['refund_status','neq',2];     //未退款
                $map[] = ['deliver_status','eq',0]; //未发货
                $count = Db::name('order_goods') ->where($map)->select();
                if( count($count) > 0 ){
                    continue ;     //存在未发货订单，不能自动收货
                }
                //确认收货
                $attr = [
                    "order_id"=>$v['id'],
                    "status"  => 2,
                    "title"   => "自动确认收货",
                    "add_time"=>time(),
                ];

                //查询是否存在汪汪队商品
                $giveType = 0;  //是否赠送优惠券：0不赠送，1赠送
                if ( $v['coupon_id'] == 3 )
                {
                    $goods = Db::name('order_goods') ->where('order_id',$v['id'])->column('item_id');  //商品ID
                    $giveType = 0;  //是否赠送优惠券：0不赠送，1赠送
                    $ids = [4472,4473,4474,4475,4476,4605,4606,4607,4608,4609,4610,4611,4612];
                    foreach ( $goods as $k1=>$v1 ){
                        if ( in_array($v1,$ids) )
                        {
                            $giveType = 1;
                            break;
                        }
                    }
                }
                //事务
                Db::startTrans();
                try{
                    Db::name('order') ->where('id',$v['id']) ->setField('order_status',2);
                    Db::name("order_attr")->insert($attr);
                    //已下单时间为准3.6号14：00分，汪汪队系列确认收货后立返买2送1优惠券，优惠券ID;4
                    //赠送优惠券活动于2020/4/9日下线
//                    if ( $giveType == 1 )
//                    {
//                        $res = (new CouponReceiveModel()) ->giveCoupon(['member_id'=>$v['member_id']]);
//                    }
                    //提交事务
                    Db::commit();
                }catch (\Exception $e){
                    //回滚事务
                    Db::rollback();
                    dump($e ->getMessage());die;
                }
            }
        }
    }

    /***
     * 查询重复订单
     */
    public function autoRepeatTask(){
        $start_time = strtotime(date('Y-m-d').'08:50:00');
        $end_time = strtotime(date('Y-m-d').'20:00:00');
        if( time()>= $start_time && time()<=$end_time ){
            $sql = 'select sn from ddxm_order group by sn having count(sn) > 1';
            $list = Db::query($sql);
            if( count($list) >0 ){
                $user = ['oZULn02wnBIf73Iq26nFJIIf9wQE','oZULn07xR-xrND2GcD2CeNQ1jLXQ','oZULn04r6eGRy_nuTJTKoj8g7zS8'];
                //发送客户消息
                $sn = array_column($list,'sn');
                $sn1 = implode(',',$sn);
                foreach ( $user as $k=>$v ){
                    $content = '紧急事件：今日又有订单重复啦'.$sn1;
                    $post_data = '{"touser":"'.$v.'","msgtype":"text","text":{"content":"'.$content.'"}}';
                    (new WxPayModel())->sed_custom_message($post_data);     //给上级发消息
                }
            }
        }
    }

    /**
     * 查询 余额为 负数 值  超额
     */
    public function autoMoneyExcess(){

        $start_time = strtotime(date('Y-m-d').'08:50:00');
        $end_time = strtotime(date('Y-m-d').'20:00:00');
        if( time()>= $start_time && time()<=$end_time ) {

            set_time_limit(0);

            require_once APP_PATH . '/../vendor/predis/predis/autoload.php';
            $client = new Client();
            $key = 'autoMoneyExcess_id';

            $id = $client->get($key);
            if (empty($id)) {
                $id = 0;
            }
            $memberList = Db::name("member")->where('id', '>=', $id)->limit(50)->select();

            //查询数据库中 最后一个ID
            $memberLastId = Db::name("member")->limit(1)->order('id', 'desc')->value('id');

            $select_memberid = 0;
            foreach ($memberList as $value) {

                $memberId = $value['id'];
                $money = Db::name("member_money")->where("member_id", $memberId)->value("money");
                $xs = $this->getLimitedPrice1($memberId);
                $mon = $money - $xs;
                if ($mon < 0) {
                    $client->set($key, $memberId);
                    $client->EXPIRE($key, 60);

                    $user = ['oZULn02wnBIf73Iq26nFJIIf9wQE', 'oZULn07xR-xrND2GcD2CeNQ1jLXQ', 'oZULn04r6eGRy_nuTJTKoj8g7zS8'];
                    //发送客户消息
                    foreach ($user as $k => $v) {
                        $content = '紧急事件：ID为' . $memberId . '余额异常 ：' . $mon;
                        $post_data = '{"touser":"' . $v . '","msgtype":"text","text":{"content":"' . $content . '"}}';
                        (new WxPayModel())->sed_custom_message($post_data);     //给上级发消息
                    }
                    return $memberId . '余额异常 ：' . $mon;
                }
                if ($memberId >= $memberLastId) {//已经查询到最后一条ID了
                    $client->set($key, 0);
                } else {
                    $client->set($key, $memberId);
                    $client->EXPIRE($key, 60);
                }
                $select_memberid = $memberId;
            }

            return '一切正常' . $select_memberid;
        }
    }

    /***
     * 查询会员的限时余额
     */
    public function getLimitedPrice1($member_id){
        $map = [];
        $map[] = ['member_id','eq',$member_id];
        $map[] = ['status','in','0,1'];
//            $map[] = ['expire_time','>=',time()];
        $list = Db::name('member_money_expire')
            ->where($map)
            ->field('id,price,use_price,expire_time,status,expire_day')->select();
        $Utils = new UtilsModel();
        $info = []; //数据
        foreach ($list as $k=>$v){
            $list[$k]['limited'] = bcsub($v['price']-$v['use_price'],2);
            if( $v['use_price'] <$v['price'] ){
                $arr = [];
                $arr = [
                    'id'    =>$v['id'],
                    'price'    =>bcsub($v['price'],$v['use_price'],2),
                    'expire_time'    =>$v['expire_time'],
                    'status'    =>$v['status'],
                    'expire_day'    =>$v['expire_day'],
                ];
                array_push($info,$arr);
            }
        }
        foreach ($info as $k=>$v){
            if( $v['status'] == 1 ){
                $info[$k]['company'] = date('Y-m-d H:i:s',$v['expire_time']);
            }else{
                $info[$k]['company'] = '未激活';
            }
        }
        $allPrice = 0;
        foreach ($info as $k=>$v){
            $allPrice += $v['price'];
        }
        return $allPrice;
    }

    /**
     * 优惠券自动过期
     */
    public function autoCouponInvalid()
    {
        $where = [];
        $where[] = ['is_use','eq',1];
        $where[] = ['invalid_time','<=',time()];
        Db::name('coupon_receive') ->where($where)->setField('is_use',3);
    }
}
