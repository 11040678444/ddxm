<?php
namespace app\wxshop\controller;

use app\common\controller\FinancialFlow;
use app\mall_admin_market\model\exclusive\NewExclusive;
use app\mall_admin_market\model\exclusive\StPayLog;
use app\mall_admin_order\controller\OrderService;
use app\wxshop\model\coupon\CouponReceiveModel;
use app\wxshop\model\st_recharge\StRechargeFlow;
use think\Controller;
use think\Db;
use think\Query;
/**
商城,微信支付回调
 */
class Wxnotify extends Controller
{
    // 商品支付：微信 支付 回调 地址
    public function index()
    {
        $notify_data = file_get_contents("php://input");//获取由微信传来的数据
        if (!$notify_data) {
            $notify_data = $GLOBALS['HTTP_RAW_POST_DATA'] ?: '';//以防上面函数获取到的内容为空
        }
        if (!$notify_data) {
            exit(false);
        }
        $doc = new \DOMDocument();
        $doc->loadXML($notify_data);
        $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue; //自己传的订单编号
        $time_end = $doc->getElementsByTagName("time_end")->item(0)->nodeValue; //支付成功的时间20141030133525（yyyyMMddHHmmss）
        $appid = $doc->getElementsByTagName("appid")->item(0)->nodeValue;

//        $out_trade_no = 'WM2020040149515357419';
//        $time_end = '20200401210329';
        $order = Db('order')->where('sn', $out_trade_no)->find();
        if ($order == false) {
            //$msg = "订单查询失败";
            exit(false);
        }
        /***
         * 做订单支付成功操作
         */
        if( $order['pay_status'] == 1 || !empty($order['paytime'])  ){
//            exit(true);     //已经修改了状态
        }
        //使用优惠券
        if( !empty($order['coupon_id']) && !empty($order['c_receive_id']) ){
            $coupon = (new CouponReceiveModel()) ->where('id',$order['c_receive_id'])->find();
            if( $coupon['is_use'] != 1 ){
                exit(false);
            }
        }

        $item = Db::name('order_goods') ->where('order_id',$order['id'])->select(); //订单商品表
        //如果是新人专享订单
        if ( $order['order_distinguish'] == 6 )
        {
            $payStatus = ( new StPayLog() ) ->userPayLog(['member_id'=>$this->getUserId()]);
            if( $payStatus == 1 )
            {
                exit(false);
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
                exit(false);
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            $res = 1;
            //更改订单状态
            Db::name('order')->where('id',$order['id'])->update(['pay_status'=>1,'pay_way'=>1,'paytime'=>strtotime($time_end),'overtime'=>strtotime($time_end)]);
            //添加商品的销量
            $oneRetailPrice = 0;    //一级分销金额
            $twoRetailPrice = 0;    //二级分销金额
            $ownRetailPrice = 0;    //自购分销金额
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
                }
            }

            $memberInfo = Db::name('member')->where('id',$order['member_id'])->field('id,openid,mobile,wechat_nickname,nickname,retail')->find();
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
                $order_assemble_log = Db::name('order_assemble_log') ->where('order_id',$order['id'])->find();    //当时拼团时的金额
                Db::name('order_assemble_log') ->where('id',$order_assemble_log['id'])->setField('pay_way',1);
                if( $order['commander_type'] == 1 ){    //表示团长
                    //表示开团,拼团人数永远不可能是一个人所以不需要判断拼团是否成功
                    //结束时间
                    $set_assemble_fail_time = Db::name('overtime_set') ->where('id',1)->value('set_assemble_fail_time');
                    //$active['end_time']活动的结束时间
                    $map = [];
                    $map[] = ['id','eq',$order['event_id']];
                    $active = Db::name('flash_sale') ->where($map)->find();
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
//                    (new WxPayModel()) ->send_message($post_data);
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
                    $orders = Db::name('assemble_info') //方便平台成功或者失败的一起用
                        ->alias('a')
                        ->where('a.assemble_list_id',$order_assemble_log['assemble_list_id'])
                        ->join('member b','a.member_id=b.id')
                        ->field('a.member_id,a.o_sn,a.commander,a.real_price,a.order_id,b.openid,b.wechat_nickname')->select();
                    //判断拼团是否成功
                    $new_assemble_list_info = Db::name('assemble_list')->where('id',$order_assemble_log['assemble_list_id'])->find();
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
                        $order_ids = Db::name('assemble_info')
                            ->where('assemble_list_id',$order_assemble_log['assemble_list_id'])
                            ->column('order_id');
                        $orderWhere = [];
                        $orderWhere[] = ['id','in',implode(',',$order_ids)];
                        Db::name('order')->where($orderWhere)->setField('assemble_status',1);  //拼团成功
                        //发送模板消息，因为拼团成功，所以给每个团员都发送拼团成功消息
                        $menicknames = array_column($orders,'wechat_nickname'); //少了当前支付的人的姓名
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
//                            (new WxPayModel()) ->send_message($post_data);
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
//                        (new WxPayModel()) ->send_message($post_data);
                    }
                }
                //增加活动的已拼团数量 $flash_sale_attr
                $map = [];
                $map[] = ['flash_sale_id','eq',$order['event_id']];
                $map[] = ['item_id','eq',$order_assemble_log['item_id']];
                $map[] = ['specs_ids','eq',$order_assemble_log['attr_ids']];
                $flash_sale_attr = Db::name('flash_sale_attr')->where($map)->find();    //当前此活动的状态
                $flash_sale_attr_new_data = [];
//                if( $flash_sale_attr['stock'] != '-1' ){
//                    $flash_sale_attr_new_data['stock'] = $flash_sale_attr['stock'] - $order_assemble_log['buy_num'];
//                }
                if( $flash_sale_attr['residue_num'] != '-1' ){
                    $flash_sale_attr_new_data['residue_num'] = $flash_sale_attr['residue_num'] - $order_assemble_log['buy_num'];
                }
                $flash_sale_attr_new_data['already_num'] = $flash_sale_attr['already_num'] + $order_assemble_log['buy_num'];
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

            //加入股东数据3、5
            $statisticsData = [];   //股东数据
            $arr = [];
            $arr = [
                'order_id'  =>$order['id'],
                'shop_id'  =>$order['shop_id'],
                'order_sn'  =>$order['sn'],
                'type'  =>3,
                'data_type'  =>1,
                'pay_way'  =>1,
                'price'  =>$order['amount']
            ];
            array_push($statisticsData ,$arr);
            $arr['type'] = 5;
            array_push($statisticsData ,$arr);
            $res = controller('Base') ->addToStatistics($statisticsData);      //加入股东数据
            if( $res['code'] != 200 ){
                throw new \Exception('股东数据加入错误');     //php抛出异常
            }
            //添加累计消费表
            $grandTotalWhere = [];
            $grandTotalWhere[] = ['status','eq',0];
            $grandTotalWhere[] = ['member_id','eq',$order['member_id']];
            $grandTotalWhere[] = ['end_time','>=',time()];
            $or = Db::name('grand_total') ->where($grandTotalWhere)->find();
            if( !$or ){
                $time_end = strtotime($time_end);
                $grand_total_data = [];
                $grand_total_data = [
                    'start_order_id'    =>$order['id'],
                    'end_order_id'    =>0,
                    'start_time'    =>$time_end,
                    'end_time'    =>strtotime("+1month",$time_end),
                    'status'    =>0,
                    'member_id'    =>$order['member_id'],
                ];
                Db::name('grand_total') ->insert($grand_total_data);
            }

            //订单中间表数据
//            $OrderService = new OrderService();
//            $order_service_data = [];       //中间表数据
//            $order_service_data = [
//                'order_id'  =>$order['id'],
//                'os_type'  =>1,
//                'shop_id'  =>0,
//                'goods_id' =>array_column($item,'id')
//            ];
//            $OrderServiceRes = $OrderService ->addService(1,$order_service_data);
//            if( !$OrderServiceRes ){
//                throw new \Exception('内部错误');
//            }

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
                        //获取线上消费余额
                        $online_money = Db::name('member_money')->where(['member_id'=>$order['member_id']])->value('online_money');

                        $flow_data[]= [
                            'member_id'=>$order['member_id'],
                            'flow_code'=>'DDXM'.date('Ymd').rand('11111111','99999999'),//流水编号（格式：DDXM2020031288888888）
                            'order_code'=>$out_trade_no,
                            'flow_type'=>1,
                            'change_money'=>$value == 3 ? $order['amount'] : array_sum(array_column($flow,'discount_price')),
                            'pre_change_money'=>$value == 3 ? $online_money : $remain_price,
                            'after_change_money'=>$value == 3 ? bcsub($online_money,$order['amount'],2) : bcsub($remain_price,array_sum(array_column($flow,'discount_price')),2),
                            'pay_type'=>1,
                            'money_type'=>$value//1普通余额、2限时余额、3线上余额、4抵扣余额（充值送活动）
                        ];
                    }

                    $res = (new FinancialFlow())->addFlow($flow_data);

                    $res = $res['status'] == 200 ? 1 : 0;
                }
            }

            if ($res && ($order['wuyi_ok'] == 1) )
            {
                $time = time();
                //赠送优惠券
                if ( ($time>=1587571200) || ($time<=1588780800) )
                {
                    $res = (new CouponReceiveModel())->giveCoupon2($order['wuyi_item_amount'],$order['member_id']);
                }
            }

            //添加erp系统的待代购单
            if ( $res )
            {
                $result = Controller('Base')->getItemStore($order['id']);
                $result = json_decode($result,true);
                $res = $result['code']==200? 1:0;
            }
            //动态修改Redis商品库存
            $k = $item[0]['item_id'].'_'.$item[0]['attr_ids'];
            if(redisObj()->exists($k))
            {
                redisObj()->rpop($k);
            }
            // 提交事务
            if($res)
            {
                Db::commit();
            }else{
                Db::rollback();
                exit(false);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            exit(false);
        }
        exit(true);
    }

    //充值支付回调
    public function cz_notify(){
        $notify_data = file_get_contents("php://input");//获取由微信传来的数据
        if (!$notify_data) {
            $notify_data = $GLOBALS['HTTP_RAW_POST_DATA'] ?: '';//以防上面函数获取到的内容为空
        }
        if (!$notify_data) {
            exit(false);
        }
        $doc = new \DOMDocument();
        $doc->loadXML($notify_data);
        $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue; //自己传的订单编号
        $time_end = $doc->getElementsByTagName("time_end")->item(0)->nodeValue; //支付成功的时间20141030133525（yyyyMMddHHmmss）
        $appid = $doc->getElementsByTagName("appid")->item(0)->nodeValue;
        //$out_trade_no = 'CZ15846726397239902608766';
//        $time_end = '20191207162555';
        $order = Db('order')->where('sn', $out_trade_no)->find();
        if ($order == false) {
            //$msg = "订单查询失败";
            exit(false);
        }
        if( $order['pay_status'] == 1 || !empty($order['paytime'])  ){
            exit(true);     //已经修改了状态
        }

        //获取回调附带参数
        $param = request()->param();
        switch ($param['type'])
        {
            case 0:
                $title = '小程序充值';
            break;
            case 8:
                $title = '线上商城充值送限时抵扣余额活动';
            break;
            default:
                $title = '小程序充值';
        }

        // 启动事务
        Db::startTrans();
        try {
            $updata_order = [];
            $updata_order = [
                'pay_status'    =>1,
                'pay_way'    =>1,
                'paytime'    =>strtotime($time_end),    //转成时间戳
                'overtime'  =>time(),
                'order_status'  =>2,
            ];
            //修改订单状态
            $res = Db::name('order')->where('id',$order['id'])->update($updata_order);

            //获取线上消费余额
            $online_money = Db::name('member_money')->where(['member_id'=>$order['member_id']])->value('online_money');

            //更改会员线上余额问题
            if($res)
            {
                $res = Db::name('member_money')->where('member_id',$order['member_id'])->setInc('online_money',$order['amount']);
            }else{
                exit(false);
            }

            //添加充值日志
            if($res)
            {
                //会员信息
                $member = db::name('member')->where("id",$order['member_id'])->find();
                $recharge_log = [
                    'member_id' =>$order['member_id'],
                    'shop_id'   =>$order['shop_id'],
                    'order_id'  =>$order['id'],
                    'price'     =>$order['amount'],
                    'pay_way'     =>1,
                    'create_time'     =>time(),
                    'remarks'     =>$title,
                    'title'     =>'余额充值',
                    'type'     =>3
                ];

                $res = Db::name('member_recharge_log') ->lock(true)->insert($recharge_log);
            }

            //会员余额明细表
            if($res)
            {
                $member_details = [
                    'member_id' =>$order['member_id'],
                    'mobile' =>$member['mobile'],
                    'remarks' =>$title,
                    'reason' =>$title,
                    'addtime' =>time(),
                    'amount' =>$order['amount'],
                    'type' =>1,
                    'order_id' =>$order['id']
                ];

                $res = Db::name('member_details')->lock(true)->insert($member_details);    //会员余额明细表
            }

            //添加股东数据
            if($res)
            {
                $statisticsLog = array(
                    'shop_id'		=>$order['shop_id'],
                    'order_id'		=>$order['id'],
                    'order_sn'		=>$order['sn'],
                    'type'			=>1,
                    'data_type'	=>1,
                    'pay_way'		=>3,
                    'price'			=>$order['amount'],
                    'title'			=>$title,
                    'create_time'	=>time()
                );
                $res = Db::name('statistics_log')	->insert($statisticsLog);
            }

            //计算等级
            if($res)
            {
                $amount = Db::name('member_details')
                    ->where('member_id',$order['member_id'])->where('type',1)
                    ->sum('amount');    //累计充值
                $levelWhere = [];
                $levelWhere[] = ['shop_id','=',$order['shop_id']];
                $levelWhere[] = ['price','<=',$amount];
                $level = Db::name('level_price')->where($levelWhere)
                    ->order('price desc')->find();
                if( $level ){
                    if( $level['level_id'] > $member['level_id'] ){
                        //更改门店等级
                        $res = Db::name('member')->where('id',$member['id'])->setField('level_id',$level['level_id']);
                    }
                }
            }

            /*
             * 线上商城充值送限时抵扣余额活动
             */
            if($param['type'] == 8)
            {
                //活动规则配置
                $config = config('Recharge');

                //获取当前充值对应赠送抵扣金额
                $give_amount = array_search(intval($order['amount']),$config);

                //如果存在活动配置，则新增折扣记录
                if($give_amount)
                {
                    //组装ddxm_st_recharge表数据
                    $recharge_data = [
                        'member_id'=>$order['member_id'],
                        'enter_price'=>$give_amount,//当时充值赠送的折扣金额
                        'remain_price'=>$give_amount,//可使用余额
                        'order_id'=>$order['id'],//订单id
                        'create_time'=>time(),
                        'expires_time'=>strtotime(date('Y-m-d H:i:s',strtotime('+6month')))//过期时间（6月后过期）
                    ];

                    //获取充值送余额
                    $flow_where[] = ['member_id','eq',$order['member_id']];
                    $flow_where[] = ['expires_time','egt',time()];
                    $flow_where[] = ['remain_price','gt',0];
                    $remain_price = Db::name('st_recharge')->where($flow_where)->sum('remain_price');

                    $res = Db::name('st_recharge')->insert($recharge_data);

                    //添加流水
                    if($res)
                    {
                        //拼接流水数据：[3,4]参考flow_type备注
                        foreach ([3,4] as $key=>$value)
                        {
                            $flow_data[]= [
                                'member_id'=>$order['member_id'],
                                'flow_code'=>'DDXM'.date('Ymd').rand('11111111','99999999'),//流水编号（格式：DDXM2020031288888888）
                                'order_code'=>$out_trade_no,
                                'flow_type'=>$value,//类型：1线上商品消费、2门店消费、3线上充值、4赠送充值、5门店充值
                                'change_money'=>$value == 3 ? $order['amount'] : $give_amount,
                                'pre_change_money'=>$value == 3 ? $online_money : $remain_price,
                                'after_change_money'=>$value == 3 ? bcadd($online_money,$order['amount']) : bcadd($remain_price,$give_amount),
                                'pay_type'=>1,
                                'money_type'=>$value
                            ];
                        }

                        $res = (new FinancialFlow())->addFlow($flow_data);

                        $res = $res['status'] == 200 ? 1 : 0;
                    }
                }else{
                    return false;
                }
            }

            /**
             * 正常充值
             * $param['type'] 默认为0，主要兼容之前的充值
             */
            if(!$param['type'])
            {
                //判断充值是否满赠送活动（模拟下单、模拟会员下单）
                $map = [];
                $map[] = ['shop_id','eq',$order['shop_id']];
                $map[] = ['type','eq',1];
                $map[] = ['status','eq',1];
                $map[] = ['price','<=',$order['amount']];
                $active = Db::name('online_activity')->where($map)->order('price desc')->find();//按金额降序排序满足的第一条
                if( $active ){
                    if( $active['activity_type'] == 1 && !empty($active['activity_type_id']) ){
                        //赠送服务卡
                        $worker = Db::name('shop_worker')
                            ->where('sid',$order['shop_id'])
                            ->where('status',1)
                            ->where('post_id',1)
                            ->find();
                        $card_id = $active['activity_type_id']; //服务卡id
                        $cardMoney = Db::name('ticket_money')->where('card_id',$card_id)->where('level_id',1)
                            ->field('price')->find();       //查询最高价为原价
                        $data = db::name("ticket_card")->where("id",$card_id)->find();   //服务卡信息
                        // 生产订单
//                $order = [
//                    "shop_id" =>$order['shop_id'],
//                    "member_id" =>$order['member_id'],
//                    "sn"=>'XM'.time().$order['shop_id'],
//                    "type"=>5,
//                    "pay_sn"=>"",
//                    "ticket_id"=>$card_id,
//                    "number" =>1,
//                    "discount"=>$cardMoney['price'],
//                    "amount"=>0,
//                    "pay_status"=>1,
//                    "send_way"=>1,
//                    "pay_way"=>1,
//                    "paytime"=>time(),
//                    "sendtime"=>time(),
//                    "overtime"=>time(),
//                    "dealwithtime"=>time(),
//                    "order_status"=>2,
//                    "add_time"=>time(),
//                    "is_online"=>0,
//                    "waiter"=>$worker['name'],
//                    "waiter_id"=>$worker['id'],
//                    "order_type"=>1,
//                    "old_amount"=>$cardMoney['price'],
//                    "order_triage"=>1,
//                    "remarks"=>date('Y-m-d H:i:s').'充值'.$order['amount'].'元赠送服务卡',
//                ];
//                $newOrderId = Db::name('order')->insertGetId($order);
                        //购买记录
                        $ticket =[
                            "shop_id"=>$order['shop_id'],
                            "order_id"=>$order['id'],
                            "member_id"=>$order['member_id'],
                            "mobile"=>$member['mobile'],
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
                            "level_id"=>$member['level_id'],
                            "give_type"=>2, //2赠送
                            "remarks"=>date('Y-m-d H:i:s').'充值满'.$active['price'].'元,商城活动赠送服务卡：'.$data['card_name'],
                            "is_online" =>1 //线上商城赠送的服务卡
                        ];
                        $cardId = Db::name("ticket_user_pay")->insertGetId($ticket);   //赠送服务卡
                        //ddxm_member_ticket赠送表
                        if($cardId)
                        {
                            $arr = [];
                            $arr = [
                                'member_id' =>$order['member_id'],
                                'card_id' =>$cardId,
                                'status' =>0,
                                'create_time' =>time(),
                                'receive_expire_time' =>strtotime('+1 month'),
                                'receive_time' =>0,
                                'use_expire_time' =>0,
                                'ticket_id' =>$card_id,
                                'order_id' =>$order['id'],
                            ];
                            $res = Db::name('member_ticket') ->insert($arr);
                        }else{
                            exit(false);
                        }

                    }
                }
            }
            // 提交事务
            if($res)
            {
                Db::commit();
            }else{
                Db::rollback();
                exit(false);
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            exit(false);
        }
        exit(true);
    }

    // 微信 退单 回调 地址
    public function refundindex()
    {
        $notify_data = file_get_contents("php://input");//获取由微信传来的数据
        if (!$notify_data) {
            $notify_data = $GLOBALS['HTTP_RAW_POST_DATA'] ?: '';//以防上面函数获取到的内容为空
        }
        if (!$notify_data) {
            exit(false);
        }

        exit(true);
        return;
//        $doc = new \DOMDocument();
//        $doc->loadXML($notify_data);
//        $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue; //自己传的订单编号
//        $time_end = $doc->getElementsByTagName("time_end")->item(0)->nodeValue; //支付成功的时间20141030133525（yyyyMMddHHmmss）
//        $appid = $doc->getElementsByTagName("appid")->item(0)->nodeValue;
//
//        $order = Db('order')->where('sn', $out_trade_no)->find();
//        if ($order == false) {
//            //$msg = "订单查询失败";
//            exit(false);
//        }
//        /***
//         * 做订单支付成功操作
//         */
//        if( $order['pay_status'] == 1 || !empty($order['paytime'])  ){
//            exit(true);     //已经修改了状态
//        }
//        $item = Db::name('order_goods') ->where('order_id',$order['id'])->select(); //订单商品表
//        // 启动事务
//        Db::startTrans();
//        try {
//            //更改订单状态
//            Db::name('order')->where('id',$order['id'])->update(['pay_status'=>1,'pay_way'=>1,'paytime'=>time()]);
//            //添加商品的销量
//            foreach ($item as $k=>$v){
//                $map = [];
//                $map[] = ['gid','eq',$v['item_id']];
//                $map[] = ['key','eq',$v['attr_ids']];
//                $specGoods = Db::name('specs_goods_price') ->where($map) ->find();
//                if( $specGoods['store'] != '-1' ){
//                    Db::name('specs_goods_price') ->where($map)->setDec('store',$v['num']);
//                }
//                Db::name('specs_goods_price') ->where($map)->setInc('reality_sales',$v['num']);
//                //增加商品表的销量
//                Db::name('item') ->where('id',$v['item_id'])->setInc('reality_sales',$v['num']);
//            }
//            //根据订单判断是否为拼团的订单，需要去更改拼团表订单
//            if( $order['order_distinguish'] == 1 ){
//                //拼团订单:修改info表订单状态,修改assemble_attr 对应版本的剩余可拼团的数量
//                Db::name('assemble_info') ->where(['order_id'=>$order['id'],'o_sn'=>$order['sn']])->setField('status',1);
//                $assembleAttr = Db::name('assemble_attr')->where(['assemble_id'=>$order['event_id'],'update'=>$order['assemble_update']])->select();
//                if( $assembleAttr['all_stock'] != 0 ){
//                    Db::name('assemble_attr')->where(['assemble_id'=>$order['event_id'],'update'=>$order['assemble_update']])->setDec('remaining_stock',$order['number']);
//                }
//            }
//            if( $order['order_distinguish'] == 2 ){
//                //秒杀，更改秒杀表已抢购的数量（包含开始抢购的数量）
//                Db::name('seckill')->where('id',$order['event_id'])->setInc('already_num',$order['number']);
//            }
//            // 提交事务
//            Db::commit();
//        } catch (\Exception $e) {
//            // 回滚事务
//            Db::rollback();
//            exit(false);
//        }
//        exit(true);
    }
}