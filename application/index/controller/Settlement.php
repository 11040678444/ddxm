<?php
/*
	结算控制器
*/
namespace app\index\controller;
use app\common\model\PayModel;
use app\index\model\ItemModel;
use app\index\model\WorkerModel;
use app\index\model\ServiceModel;
use app\index\model\Member\MemberModel;
use app\index\model\Member\MemberMoneyModel;
use app\index\model\ShopItem\ShopItemModel;
use app\index\model\ShopItem\PurchasePriceModel;

//第二期添加的
use app\index\model\Shop\ShopWorkerModel;
use app\index\model\item\ItemModel as TP5ItemModel;
use app\index\model\item\PurchasePriceModel as Tp5PurchasePriceModel;
use app\index\model\item\ServiceModel as Tp5ServiceModel;

use app\wxshop\wxpay\WxPayMicroPay;
use think\Request;
use think\Db;
class Settlement extends Base
{

    /**
    结账
    member ：会员id
    waiter ： 服务人员id
    goods ：普遍商品数组
    service_goods：服务商品组
    pay_way:支付方式
     */
    public function index(){
        $data = $this ->request ->post();
        $shop_id = $this->getUserInfo()['shop_id'];		//门店id
        if( empty($data['goods']) && empty($data['service_goods']) ){
            return json(['code'=>'-3','msg'=>'请选择商品','data'=>'']);
        }
        if( empty($data['pay_way']) ){
            return json(['code'=>'-3','msg'=>'请选择支付方式','data'=>'']);
        }
        if( ($data['pay_way'] == 1 || $data['pay_way'] == 2) && empty($data['auth_code']) ){
            return json(['code'=>'-3','msg'=>'微信支付宝支付请出示付款码','data'=>'']);
        }

        if( (!empty($data['goods']) && !empty($data['service_goods'])) && $data['pay_way']== 3 ){
            return json(['code'=>'-9','msg'=>'当存在服务商品与普通商品时,禁止使用余额支付','data'=>'']);
        }
        $oldOrderId = Db::name('order')->where(['shop_id'=>$shop_id])->order('add_time desc')->find();
        if( $oldOrderId && (time()-$oldOrderId['add_time'])<5 ){
            return json(['code'=>301,'msg'=>'操作过于频繁']);
        }

        if( !empty($data['member']) ){
            if( $data['pay_way'] == 8 ){
                return json(['code'=>'-9','msg'=>'会员禁止使用门店自用方式','data'=>'']);
            }
            //查询会员等级id
            $Member = new MemberModel();
            $memberWhere['id'] = $data['member'];
            $memberInfo = Db::name('member') ->where($memberWhere)->field('id,level_id,mobile')->find();
            if(!$memberInfo){
                return json(['code'=>'-3','msg'=>'会员错误','data'=>'']);
            }
        }
        if( empty($data['member']) || empty($memberInfo) ){
            $memberInfo['level_id'] = 1;
            $data['member'] = 0;
        }
        if( empty($data['goods']) ){
            return json(['code'=>'-3','msg'=>'请选择商品','data'=>'']);
        }
        $items = $data['goods'];	//全部商品
        $goods = [];	//普通商品
        $service_goods = [];	//服务商品
        foreach ($items as $key => $value) {
            if( !isset($value['is_service_goods']) ){
                return json(['code'=>'-3','msg'=>'请传入参数is_service_goods','data'=>'']);
            }
            if ( $value['is_edit'] == 1 && !isset($value['edit_price']) ) {
                return json(['code'=>'-3','msg'=>'当前有改价商品但未传入修改金额','data'=>'']);
            }
            if( !isset($value['num']) ){
                return json(['code'=>'-3','msg'=>'请选择数量','data'=>'']);
            }
            if( !isset($value['id']) ){
                return json(['code'=>'-3','msg'=>'请传入商品id','data'=>'']);
            }

            if ( $value['is_service_goods'] == 1 ) {
                if ( empty($value['waiter_id']) ) {
                    return json(['code'=>'-3','msg'=>'缺少服务项目服务人员id','data'=>'']);
                }
                array_push($service_goods, $value);
            }else if( $value['is_service_goods'] == 0 ){
                //表示为商品，则必须传入商品服务人员
                if ( empty($data['waiter']) ) {
                    return json(['code'=>'-3','msg'=>'缺少商品服务人员id','data'=>'']);
                }
                array_push($goods, $value);
            }
        }

        $s_order_outsourcing = 0;	//是否包含外包服务
        $order_outsourcing = 0;	//是否包含外包商品
        if( count($goods)>0 ){
            //查询商品服务人员的名称
            $Worker = new ShopWorkerModel();
            $workerInfo = $Worker ->where(['id'=>$data['waiter']])->find();
            //获取商品列表
            $itemIds = array_column($goods, 'id');
            $itemIds = implode(',',$itemIds);
            $Item = new TP5ItemModel();
            $item_list = $Item ->getGoods($itemIds,$shop_id);	//获取商品(库存，原价，最低改价，库存表id)列表

            foreach ($item_list as $key => $value) {
                foreach ($goods as $k => $v) {
                    if( $value['id'] == $v['id'] ){
                        if ( $value['stock'] < $v['num'] ) {	//库存不足
                            return json(['code'=>'-4','msg'=>$value['title'].'商品库存有变动，请重新下单！','data'=>'']);
                        }
                        if( $v['is_edit'] == 1 && ($v['edit_price']<$value['minimum_selling_price']) ){
                            return json(['code'=>'-4','msg'=>$value['title'].'商品价格不能低于最低价！','data'=>'']);
                        }
                        $goods[$k]['old_price'] = $value['price'];		//商品原价
                        $goods[$k]['category_id'] = $value['type'];		//商品分类id
                        $goods[$k]['type_id'] = $value['type_id'];			//商品1级分类的id
                        $goods[$k]['title'] = $value['title'];
                    }
                }
            }
            //计算商品的平均成本
            $Purchase= new Tp5PurchasePriceModel();
            $cost = $Purchase ->itemCostPrice($goods,$shop_id);

            $goods = $cost['data'];
            $shop_item_array = [];	//shop_item需要减的库存的数据
            foreach ($item_list as $key => $value) {		//组装shop_item需要减的库存的数据
                foreach ($goods as $k => $v) {
                    if( $value['id'] == $v['id'] ){
                        $new_array = array(
                            'id'	=>$value['shop_item_id'],
                            'stock'	=>$value['stock']-$v['num']
                        );
                        array_push($shop_item_array, $new_array);
                    }
                }
            }
            foreach ($goods as $key => $value) {
                //计算每个商品的单价,与每个商品的总价,修改的金额
                if( $value['is_edit'] == 1 ){	//改过价格
                    $goods[$key]['item_all_price'] = $value['edit_price']*$value['num'];		//单商品的总价
                    $goods[$key]['danjia'] = $value['edit_price'];								//单商品的实际支付单价，方便后面order_price统计
                }else{
                    $goods[$key]['item_all_price'] = $value['old_price']*$value['num'];
                    $goods[$key]['danjia'] = $value['old_price'];
                    $goods[$key]['edit_price'] = 0;
                }
                //计算每个商品的原价的总价
                $goods[$key]['item_old_all_price'] = $value['old_price']*$value['num'];
                //判断是否为外包商品
                $title = $value['title'];
                $title_s = mb_substr($title,0,2,'utf-8');
                if( $title_s === '外包' ){
                    $goods[$key]['is_outsourcing_goods'] = 1;		//1外包商品，0不是
                }else{
                    $goods[$key]['is_outsourcing_goods'] = 0;
                }
            }

            foreach ($goods as $key => $value) {
                if( $value['is_outsourcing_goods'] == 1 ){
                    $goods[$key]['cost_price'] = $value['danjia'];
                    $goods[$key]['all_cost_price'] = $value['danjia']*$value['num'];
                }
            }

            foreach ($goods as $key => $value) {
                if( $value['is_outsourcing_goods'] == 1 ){
                    $order_outsourcing = 1;
                    break;
                }
            }

            $allNum = 0;	//商品的总量
            $allPrice = 0;	//商品的总金额
            $old_all_price = 0;	//商品的总原价
            $all_cost_price = 0;	//普通商品的总成本
            $all_md_cost_price = 0;	//普通商品的门店总成本
            $all_gs_cost_price = 0;	//普通商品的公司总成本
            $all_cost_price_waibao = 0;    //外包商品的总成本
            foreach ($goods as $key => $value) {
                $allNum += $value['num'];
                $allPrice += $value['item_all_price'];
                $old_all_price += $value['item_old_all_price'];
                if( $value['is_outsourcing_goods'] == 1 ){
                    $all_cost_price_waibao += $value['all_cost_price'];
                }else{
                    $all_cost_price += $value['all_cost_price'];
                    $all_md_cost_price = bcadd($all_md_cost_price,$value['md_cost_price'],2);
                    $all_gs_cost_price = bcadd($all_gs_cost_price,$value['gs_cost_price'],2);
                }
            }
            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15 ){	//门店赠送总金额为0
                $allPrice = 0;
            }
        }
        if( count($service_goods)>0 ){
            //获取服务人员的名称
            $Worker = new ShopWorkerModel();
            foreach ($service_goods as $key => $value) {
                $service_goods[$key]['workid'] = $Worker ->where(['id'=>$value['waiter_id']])->value('workid');
                $service_goods[$key]['name'] = $Worker ->where(['id'=>$value['waiter_id']])->value('name');
            }

            $service_itemIds = array_column($service_goods, 'id');
            $Service = new Tp5ServiceModel();
            $service_goods_list = $Service ->getList($service_itemIds,$shop_id,$memberInfo['level_id']);
            if( count($service_goods_list)  == 0 ){
                return json(['code'=>'500','msg'=>'内部出错','data'=>'']);
            }

            foreach ($service_goods_list as $key => $value) {
                foreach ($service_goods as $k => $v) {
                    if ( $value['id'] == $v['id'] ) {
                        $service_goods[$k]['cost_price'] = $value['cost_price'];		//商品的价格
                        $service_goods[$k]['title'] = $value['sname'];		//商品名称
                    }
                }
            }

            foreach ($service_goods as $key => $value) {
                if( $value['is_edit'] == 1 ){	//改过价
                    $service_goods[$key]['item_all_price'] = $value['num']*$value['edit_price'];
                    $service_goods[$key]['danjia'] = $value['edit_price'];			//实际成交价，方便数据库统计
                }else{
                    $service_goods[$key]['item_all_price'] = $value['num']*$value['cost_price'];
                    $service_goods[$key]['danjia'] = $value['cost_price'];			//实际成交价，方便数据库统计
                    $service_goods[$key]['edit_price'] = 0;
                }

                //计算每个商品的原价的总价
                $service_goods[$key]['item_old_all_price'] =  $value['cost_price']*$value['num'];

                //判断是否为外包商品
                $title = $value['title'];
                $title_s = mb_substr($title,0,2,'utf-8');
                if( $title_s === '外包' ){
                    $service_goods[$key]['is_outsourcing_goods'] = 1;		//1外包商品，0不是
                }else{
                    $service_goods[$key]['is_outsourcing_goods'] = 0;
                }
            }

            foreach ($service_goods as $key => $value) {
                if( $value['is_outsourcing_goods'] == 1 ){
                    $s_order_outsourcing = 1;
                    break;
                }
            }

            $s_allNum = 0;	//总量
            $s_allPrice = 0;	//总金额
            $s_old_all_price = 0;	//总原价
            foreach ($service_goods as $key => $value) {
                $s_allNum += $value['num'];
                $s_allPrice += $value['item_all_price'];
                $s_old_all_price += $value['item_old_all_price'];
            }
            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15 ){	//门店赠送总金额为0
                $s_allPrice = 0;
            }
        }

        //生成订单
        if( count($goods)>0 && count($service_goods)>0 ){
            $type = 7;
            $order_triage = 2;
            $order_sn = 'FI'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$shop_id;
            $all_total_price = $s_allPrice + $allPrice ;	//总价
            $all_total_num = $s_allNum + $allNum ;	//总数量
            $all_total_old_price = $s_old_all_price +$old_all_price;//总原价
            $all_total_cost_price = $all_cost_price+$all_cost_price_waibao;	//总成本
        }else if( count($goods)<=0 && count($service_goods)>0 ){
            $order_sn = 'GD'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$shop_id;
            $type = 2;
            $order_triage = 1;
            $all_total_price = $s_allPrice;	//总价
            $all_total_old_price = $s_old_all_price;//总原价
            $all_total_num = $s_allNum;	//总数量
            $all_total_cost_price = 0;//总成本
        }else if( count($goods)>0 && count($service_goods)<=0 ){
            $order_sn = 'FG'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$shop_id;
            $type = 1;
            $order_triage = 0;
            $all_total_price = $allPrice ;	//总价
            $all_total_num =$allNum ;	//总数量
            $all_total_old_price = $old_all_price;//总原价
            $all_total_cost_price = $all_cost_price+$all_cost_price_waibao;//总成本
        }

        if( empty($workerInfo) ){
            $workerInfo['name'] = '';
            $workerInfo['id'] = 0;
        }

        if( $order_outsourcing == 1 || $s_order_outsourcing == 1 ){
            $is_outsourcing_goods = 1;	//包含外包
        }else if( $order_outsourcing == 0 && $s_order_outsourcing == 0 ){
            $is_outsourcing_goods = 0;	//不包含外包
        }

        // $order	//订单数据表
        if( !empty($data['remarks']) ){
            $remarks = $data['remarks'];
        }else{
            $remarks = '';
        }
        $order = array(
            'shop_id'	=>$shop_id,			//商铺id
            'member_id'	=>$data['member'],	//会员id
            'sn'		=>$order_sn,	//订单编号
            'type'		=>$type,				//订单类型 1:商品购买
            'number'	=>$all_total_num,			//总数量
            'amount'	=>$all_total_price,		//总金额
            'pay_status'=>'1',				//支付状态
            'pay_way'	=>$data['pay_way'],	//支付方式
            'add_time'	=>time(),			//创建时间
            'dealwithtime'=>time(),			//门店处理时间
            'is_online'	=>'0',				//是否线上商城支付 默认为1，收银台等为0
            'waiter'	=>$workerInfo['name'],//服务人员名称
            'waiter_id'	=>$workerInfo['id'],	//服务人员id
            'order_type'=>'1',				//订单种类 1普通订单2预定订单
            'old_amount'=>$all_total_old_price,	//订单原价 商品原价+服务又会价
            'order_triage'=>$order_triage,	//0为商品订单1为服务订单2为商品、服务订单
            'order_status'	=>2,
            'is_outsourcing_goods'	=>$is_outsourcing_goods,
            'overtime'	=>time(),
            'user_id'	=>$this->getUserInfo()['id'],
            'remarks'   =>$remarks
        );
        //判断如果是余额支付余额是否不足
        if( !empty($data['member']) && ($data['pay_way'] == 3 || $data['pay_way'] == 13) ){
            $MemberMoney = new MemberMoneyModel();
            $me = $MemberMoney ->where('member_id',$data['member'])->value('money');
            if( !$me ){
                return json(['code'=>'-200','msg'=>'会员余额不足']);  //总余额不足
            }
            if( $me < $all_total_price ){
                return json(['code'=>'-200','msg'=>'会员余额不足']);  //总余额不足
            }
            //查询限时余额
            $expireMoneyWhere = [];
            $expireMoneyWhere[] = ['member_id','eq',$data['member']];
            $expireMoneyWhere[] = ['status','neq',2];
            $expireList = Db::name('member_money_expire')->where($expireMoneyWhere)->field('id,price,use_price,status,expire_time')->select();

            $expireMoney = 0;   //激活限时总余额
            $expireMoney1 = 0;   //未激活限时总余额
            foreach ( $expireList as $k=>$v ){
                if( $v['status'] == 1 && (time() <=$v['expire_time']) ){
                    //已激活未过期
                    $expireMoney += $v['price'] - $v['use_price'];
                }
                if( $v['status'] == 0 ){
                    //未激活
                    $expireMoney1 += $v['price'] - $v['use_price'];
                }
            }
            $all_expireMoney = $expireMoney + $expireMoney1;		//总的限时余额（包括未激活和未使用的限时余额）
            if( $data['pay_way'] == 3 && (($me-$all_expireMoney)<$all_total_price) ){
                //判断普通余额是否不足
                return json(['code'=>'-200','msg'=>'普通余额不足,当前普通余额:￥'.($me-$all_expireMoney).',限时余额可用:￥'.$expireMoney]);  //总余额不足
            }
            if( $data['pay_way'] == 13 && ($expireMoney<$all_total_price) ){
                //判断限时余额是否不足
                return json(['code'=>'-200','msg'=>'限时余额不足,当前普通余额:￥'.($me-$all_expireMoney).',限时余额可用:￥'.$expireMoney]);  //总余额不足
            }
        }

        // 启动事务
        Db::startTrans();
        try {
            //处理并发防止网络差多次操作
            $file = fopen('settlement_loke.txt','w+');
            if(flock($file,LOCK_EX|LOCK_NB))
            {
                $res = Db::name('order')->insertGetId($order);	//添加订单
                if ( $res )
                {
                    $orderId = $res;
                }
                //添加副表
                if ( $res )
                {
                    //添加商品副表
                    if( count($goods)>0 ){
                        $order_goods = [];
                        foreach ($goods as $key => $value) {
                            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15  ){
                                $real_price = 0;    //成交单价
                                $modify_price = $value['old_price'];    //修改金额
                            }else{
                                $real_price = $value['danjia'];
                                $modify_price = abs($value['danjia']-$value['old_price']);
                            }
                            $newGoods = array(
                                'order_id'		=>$orderId,		//订单编号
                                'type_id'		=>$value['type_id'],	//一级分类id
                                'category_id'	=>$value['category_id'],	//分类id
                                'subtitle'		=>$value['title'],		//商品标题
                                'item_id'		=>$value['id'],			//商品id
                                'num'			=>$value['num'],		//数量
                                'price'			=>$value['old_price'],		//原单价
                                'oprice'		=>$value['cost_price'],		//成本价
                                'modify_price'	=>$modify_price,//改价,修改的金额
                                'real_price'	=>$real_price,	//实际支付金额
                                'status'		=>'1',
                                'is_outsourcing_goods'	=>$value['is_outsourcing_goods'],
                                'all_oprice'	=> $value['md_cost_price'],  //门店产生的总成本
                                'gs_cost_price'	=> $value['gs_cost_price'],  //公司产生的总成本
                            );
                            // array_push($order_goods, $newGoods);
                            if ( $res )
                            {
                                $res = Db::name('order_goods')->insertGetId($newGoods);    //添加订单商品表
                                $orderGoodsId = $res;
                            }

                            $costPrices = $cost['costPrices'];
                            foreach ($costPrices as $k => $v) {
                                if( $v['item_id'] == $value['id'] ){
                                    $arr = array(
                                        'order_id'  =>$orderId,
                                        'order_goods_id'=>$orderGoodsId,
                                        'item_id'   =>$v['item_id'],
                                        'shop_id'   =>$shop_id,
                                        'num'       =>$v['num'],
                                        'purchase_price_id'=>$v['stockIds']
                                    );
                                    if ( $res )
                                    {
                                        $res = Db::name('order_goods_cost')->insert($arr);     //添加消耗商品成本表
                                    }
                                }
                            }

                        }
                        if( !empty($data['member']) && ($data['pay_way'] == 3 || $data['pay_way'] == 13) ){
                            //余额，添加累积消费记录
                            $detailsDate = array(
                                'member_id'		=>$data['member'],
                                'mobile'		=>$memberInfo['mobile'],
                                'reason'		=>'购买商品',
                                'addtime'		=>time(),
                                'amount'		=>$allPrice,
                                'type'			=>3,
                                'order_id'		=>$orderId
                            );
                            if ( $res )
                            {
                                $res = Db::name('member_details')->insert($detailsDate);	//添加累积消费
                            }

                            if ( $res )
                            {
                                $res = $MemberMoney ->where('member_id',$data['member'])->setDec('money',$allPrice);	//扣余额
                            }

                            if( $data['pay_way'] == 13 ){
                                //扣除限时余额
                                $expireList = self::expireList($data['member'],$allPrice);
                                if( !$expireList ){
                                    return json(['code'=>'100','msg'=>'显示余额不足']);
                                }
                                foreach ( $expireList as $k1=>$v1 ){
                                    //更改限时余额表
                                    if ( $res )
                                    {
                                        $res = Db::name('member_money_expire') ->where('id',$v1['id'])->setField('use_price',$v1['use_price']);
                                    }

                                    //添加限时余额使用记录表
                                    $arr = [];
                                    $arr = [
                                        'member_id' =>$data['member'],
                                        'order_id' =>$orderId,
                                        'price' =>$v1['consume_price'],
                                        'money_expire_id' =>$v1['id'],
                                        'order_sn' =>$order_sn,
                                        'create_time' =>time(),
                                        'reason' =>'购买商品',
                                    ];
                                    if ( $res )
                                    {
                                        $res = Db::name('member_expire_log') ->insert($arr);
                                    }
                                }
                            }
                        }
                        $ShopItem = new ShopItemModel();
                        //更新门店商品库存表的最新库存
                        foreach ($shop_item_array as $key => $value) {
                            if ( $res )
                            {
                                $res = Db::name('shop_item')->where('id',$value['id'])->setField('stock',$value['stock']);
                            }
                        }

                        //更新ddxm_purchase_price的消耗情况
                        $costPrices = $cost['costPrices'];
                        foreach ($costPrices as $key => $value) {
                            if ( $res )
                            {
                                $res = $Purchase ->where('id',$value['stockIds'])->setDec('stock',$value['num']);
                            }
                        }
                    }
                }

                if ( $res )
                {
                    //添加服务商品副表
                    if( count($service_goods)>0 ){
                        $s_order_goods = [];
                        foreach ($service_goods as $key => $value) {
                            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15  ){
                                $real_price = 0;    //成交单价
                            }else{
                                $real_price = $value['danjia'];
                            }
                            $s_newGoods = array(
                                'order_id'		=>$orderId,		//订单编号
                                'member_id'		=>$data['member'],	//会员id
                                'sid'	=>$shop_id,
                                'sn'		=>$order_sn,
                                'paytime'		=>time(),
                                'yytime'		=>time(),
                                'status'		=>1,
                                'workid'		=>$value['workid'],
                                'workerid'	=>$value['waiter_id'],
                                'name'	=>$value['name'],
                                'addtime'		=>time(),
                                'locktime'		=>time(),
                                'num'		=>$value['num'],
                                'price'		=>$value['cost_price'],
                                'real_price'		=>$real_price,
                                'service_id'		=>$value['id'],
                                'service_name'		=>$value['title'],
                                'state'		=>'1',
                                'is_outsourcing_goods'	=>$value['is_outsourcing_goods']

                            );
                            array_push($s_order_goods, $s_newGoods);
                        }
                        if ( $res )
                        {
                            $res = Db::name('service_goods')->insertAll($s_order_goods);
                        }
                        if( !empty($data['member']) && ($data['pay_way'] == 3 || $data['pay_way'] == 13) ){
                            //余额，添加累积消费记录
                            $detailsDate = array(
                                'member_id'		=>$data['member'],
                                'mobile'		=>$memberInfo['mobile'],
                                'reason'		=>'购买服务项目',
                                'addtime'		=>time(),
                                'amount'		=>$s_allPrice,
                                'type'			=>4,
                                'order_id'		=>$orderId
                            );
                            if ( $res )
                            {
                                $res = Db::name('member_details')->insert($detailsDate);
                            }
                            if ( $res )
                            {
                                $res = $MemberMoney ->where('member_id',$data['member'])->setDec('money',$s_allPrice);	//扣余额
                            }
                            if( $data['pay_way'] == 13 ){
                                //扣除限时余额
                                $expireList = self::expireList($data['member'],$s_allPrice);
                                if( !$expireList ){
                                    return json(['code'=>'100','msg'=>'限时余额不足']);
                                }
                                foreach ( $expireList as $k1=>$v1 ){
                                    if ( $res )
                                    {
                                        $res = Db::name('member_money_expire') ->where('id',$v1['id'])->setField('use_price',$v1['use_price']);
                                    }
                                    $arr = [];
                                    $arr = [
                                        'member_id' =>$data['member'],
                                        'order_id' =>$orderId,
                                        'price' =>$v1['consume_price'],
                                        'money_expire_id' =>$v1['id'],
                                        'order_sn' =>$order_sn,
                                        'create_time' =>time(),
                                        'reason' =>'购买服务',
                                    ];
                                    if ( $res )
                                    {
                                        $res = Db::name('member_expire_log') ->insert($arr);
                                    }
                                }
                            }
                        }
                    }
                }

                if ( $res )
                {
                    // 统计类型:1:余额充值,2:购卡,3:消耗收款,4:余额消耗,5消费消耗,6商品外包分润,7推拿外包分润,8商品成本,9营业费用,10外包商品成本
                    //股东数据
                    if ( count($goods)>0 ) {
                        $statisticsLog = [];    //外包商品数据order_outsourcing
                        if( $data['pay_way'] == 3 || $data['pay_way'] == 13 ){
                            $arry = array(
                                'order_id'      =>$orderId,
                                'shop_id'       =>$shop_id,
                                'order_sn'      =>$order_sn,
                                'data_type'     =>1,
                                'type'          =>4,
                                'pay_way'       =>$data['pay_way'],
                                'price'         =>$allPrice,
                                'create_time'   =>time(),
                                'title'         =>'购买商品'
                            );
                            array_push($statisticsLog, $arry);  //余额消耗

                            //股东成本数据
                            if( $order_outsourcing == 1 ){
                                //包含外包商品,查看是否只有外包商品一件 商品
                                if( count($goods) == 1 ){
                                    // 只有外包商品
                                    $arry = array(
                                        'order_id'      =>$orderId,
                                        'shop_id'       =>$shop_id,
                                        'order_sn'      =>$order_sn,
                                        'data_type'     =>1,
                                        'type'          =>10,
                                        'pay_way'       =>$data['pay_way'],
                                        'price'         =>$all_cost_price_waibao,
                                        'create_time'   =>time(),
                                        'title'         =>'购买外包商品'
                                    );
                                    array_push($statisticsLog, $arry);     //10外包商品成本
                                }else{
                                    //有外包和普通商品
                                    $arry = array(
                                        'order_id'      =>$orderId,
                                        'shop_id'       =>$shop_id,
                                        'order_sn'      =>$order_sn,
                                        'data_type'     =>1,
                                        'type'          =>10,
                                        'pay_way'       =>$data['pay_way'],
                                        'price'         =>$all_cost_price_waibao,
                                        'create_time'   =>time(),
                                        'title'         =>'购买外包商品'
                                    );
                                    array_push($statisticsLog, $arry);  //10外包商品成本
                                    $arry = array(
                                        'order_id'      =>$orderId,
                                        'shop_id'       =>$shop_id,
                                        'order_sn'      =>$order_sn,
                                        'data_type'     =>1,
                                        'type'          =>8,
                                        'pay_way'       =>$data['pay_way'],
                                        'price'         =>$all_md_cost_price,
                                        'create_time'   =>time(),
                                        'title'         =>'购买商品'
                                    );
                                    array_push($statisticsLog, $arry);      //8门店商品成本

                                    if ( $all_gs_cost_price > 0 )
                                    {
                                        $arry['shop_id'] = 1;
                                        $arry['price'] = $all_gs_cost_price;
                                        array_push($statisticsLog, $arry);      //8公司商品成本
                                    }
                                }
                            }else{
                                //不含外包商品
                                $arry = array(
                                    'order_id'      =>$orderId,
                                    'shop_id'       =>$shop_id,
                                    'order_sn'      =>$order_sn,
                                    'data_type'     =>1,
                                    'type'          =>8,
                                    'pay_way'       =>$data['pay_way'],
                                    'price'         =>$all_md_cost_price,
                                    'create_time'   =>time(),
                                    'title'         =>'购买商品'
                                );
                                array_push($statisticsLog, $arry);  //门店成本

                                if ( $all_gs_cost_price > 0 )
                                {
                                    $arry['shop_id'] = 1;
                                    $arry['price'] = $all_gs_cost_price;
                                    array_push($statisticsLog, $arry);      //8公司商品成本
                                }

                            }
                        }else{
                            $arry = array(
                                'order_id'      =>$orderId,
                                'shop_id'       =>$shop_id,
                                'order_sn'      =>$order_sn,
                                'data_type'     =>1,
                                'type'          =>3,
                                'pay_way'       =>$data['pay_way'],
                                'price'         =>$allPrice,
                                'create_time'   =>time(),
                                'title'         =>'购买商品'
                            );
                            array_push($statisticsLog, $arry);  //消耗收款
                            $arry = array(
                                'order_id'      =>$orderId,
                                'shop_id'       =>$shop_id,
                                'order_sn'      =>$order_sn,
                                'data_type'     =>1,
                                'type'          =>5,
                                'pay_way'       =>$data['pay_way'],
                                'price'         =>$allPrice,
                                'create_time'   =>time(),
                                'title'         =>'购买商品'
                            );
                            array_push($statisticsLog, $arry);    //5消费消耗

                            //添加成本
                            if( $order_outsourcing == 1 ){
                                //包含外包商品,查看是否只有外包商品一件 商品
                                if( count($goods) == 1 ){
                                    // 只有外包商品
                                    $arry = array(
                                        'order_id'      =>$orderId,
                                        'shop_id'       =>$shop_id,
                                        'order_sn'      =>$order_sn,
                                        'data_type'     =>1,
                                        'type'          =>10,
                                        'pay_way'       =>$data['pay_way'],
                                        'price'         =>$all_cost_price_waibao,
                                        'create_time'   =>time(),
                                        'title'         =>'购买外包商品'
                                    );
                                    array_push($statisticsLog, $arry);     //10外包商品成本
                                }else{
                                    //有外包和普通商品
                                    $arry = array(
                                        'order_id'      =>$orderId,
                                        'shop_id'       =>$shop_id,
                                        'order_sn'      =>$order_sn,
                                        'data_type'     =>1,
                                        'type'          =>10,
                                        'pay_way'       =>$data['pay_way'],
                                        'price'         =>$all_cost_price_waibao,
                                        'create_time'   =>time(),
                                        'title'         =>'购买外包商品'
                                    );
                                    array_push($statisticsLog, $arry);  //10外包商品成本
                                    $arry = array(
                                        'order_id'      =>$orderId,
                                        'shop_id'       =>$shop_id,
                                        'order_sn'      =>$order_sn,
                                        'data_type'     =>1,
                                        'type'          =>8,
                                        'pay_way'       =>$data['pay_way'],
                                        'price'         =>$all_md_cost_price,
                                        'create_time'   =>time(),
                                        'title'         =>'购买商品'
                                    );
                                    array_push($statisticsLog, $arry);      //8商品成本

                                    if ( $all_gs_cost_price > 0 )
                                    {
                                        $arry['shop_id'] = 1;
                                        $arry['price'] = $all_gs_cost_price;
                                        array_push($statisticsLog, $arry);      //8公司商品成本
                                    }
                                }
                            }else{
                                //不含外包商品
                                $arry = array(
                                    'order_id'      =>$orderId,
                                    'shop_id'       =>$shop_id,
                                    'order_sn'      =>$order_sn,
                                    'data_type'     =>1,
                                    'type'          =>8,
                                    'pay_way'       =>$data['pay_way'],
                                    'price'         =>$all_md_cost_price,
                                    'create_time'   =>time(),
                                    'title'         =>'购买商品'
                                );
                                array_push($statisticsLog, $arry);  //8商品成本

                                if ( $all_gs_cost_price > 0 )
                                {
                                    $arry['shop_id'] = 1;
                                    $arry['price'] = $all_gs_cost_price;
                                    array_push($statisticsLog, $arry);      //8公司商品成本
                                }
                            }
                        }
                        if ( $res )
                        {
                            $res = Db::name('statistics_log')	->insertAll($statisticsLog);	//添加股东统计数据表数据
                        }
                    }
                    if( count($service_goods)>0 ){
                        // 生成股东数据
                        if( $data['pay_way'] == 3 || $data['pay_way'] == 13 ){
                            //余额购买服务项目只有一个 余额消耗
                            $statisticsLog = array(
                                '0'	=>array(
                                    'order_id'		=>$orderId,
                                    'shop_id'		=>$shop_id,
                                    'order_sn'		=>$order_sn,
                                    'data_type'		=>1,
                                    'type'			=>4,
                                    'pay_way'		=>$data['pay_way'],
                                    'price'			=>$s_allPrice,
                                    'create_time'	=>time(),
                                    'title'			=>'购买服务'
                                )
                            );
                        }else{
                            $statisticsLog = array(
                                '0'	=>array(
                                    'order_id'		=>$orderId,
                                    'shop_id'		=>$shop_id,
                                    'order_sn'		=>$order_sn,
                                    'data_type'		=>1,
                                    'type'			=>3,
                                    'pay_way'		=>$data['pay_way'],
                                    'price'			=>$s_allPrice,
                                    'create_time'	=>time(),
                                    'title'			=>'购买服务'
                                ),
                                '1'	=>array(
                                    'order_id'		=>$orderId,
                                    'shop_id'		=>$shop_id,
                                    'order_sn'		=>$order_sn,
                                    'data_type'		=>1,
                                    'type'			=>5,
                                    'pay_way'		=>$data['pay_way'],
                                    'price'			=>$s_allPrice,
                                    'create_time'	=>time(),
                                    'title'			=>'购买服务'
                                ),
                            );
                        }
                        if ( $res )
                        {
                            $res = Db::name('statistics_log')->insertAll($statisticsLog);	//添加股东统计数据表数据
                        }
                    }
                }

                if ( $res )
                {
                    //微信,支付宝支付
                    if( $data['pay_way'] == 1 ){
                        //微信支付
                        $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
                        $auth_code = $data['auth_code'];
                        $input = new WxPayMicroPay();
                        $input->SetAuth_code($auth_code);
                        $input->SetBody($shopName);
                        $input->SetTotal_fee($order['amount']*100);//订单金额  订单单位 分
                        // $input->SetTotal_fee(1);//订单金额  订单单位 分
                        $input->SetOut_trade_no($order['sn']);
                        $PayModel = new PayModel();
                        $res = $PayModel ->pay($input);
                    }else if($data['pay_way'] == 2){
                        //支付宝支付
                        $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
                        $PayModel = new PayModel();
                        $resPay = $PayModel ->AliCodePay($data['auth_code'],$order['sn'],$shopName,$order['amount']);
                        if( $resPay['code'] != 200 ){
                            $res = false;
                        }else{
                            $res = true;
                        }
                    }
                }
                if ( $res )
                {
                    // 提交事务
                    Db::commit();
                    //释放并发进程
                    flock($file,LOCK_UN);
                }else{
                    return json(['code'=>100,'系统繁忙，请稍后再试']);
                }
            }else{
                return json(['code'=>100,'系统繁忙，请稍后再试']);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //关闭并发进程
            fclose($file);
            return json(['code'=>'500','msg'=>$e->getMessage(),'data'=>$e]);
        }
        return json(['code'=>'200','msg'=>'结算成功','data'=>'']);
    }
    public function index1(){
        $data = $this ->request ->post();
        $shop_id = $this->getUserInfo()['shop_id'];		//门店id
        if( empty($data['goods']) && empty($data['service_goods']) ){
            return json(['code'=>'-3','msg'=>'请选择商品','data'=>'']);
        }
        if( empty($data['pay_way']) ){
            return json(['code'=>'-3','msg'=>'请选择支付方式','data'=>'']);
        }
        if( ($data['pay_way'] == 1 || $data['pay_way'] == 2) && empty($data['auth_code']) ){
            return json(['code'=>'-3','msg'=>'微信支付宝支付请出示付款码','data'=>'']);
        }

        if( (!empty($data['goods']) && !empty($data['service_goods'])) && $data['pay_way']== 3 ){
            return json(['code'=>'-9','msg'=>'当存在服务商品与普通商品时,禁止使用余额支付','data'=>'']);
        }
        $oldOrderId = Db::name('order')->where(['shop_id'=>$shop_id])->order('add_time desc')->find();
        if( $oldOrderId && (time()-$oldOrderId['add_time'])<5 ){
            return json(['code'=>301,'msg'=>'操作过于频繁']);
        }

        if( !empty($data['member']) ){
            if( $data['pay_way'] == 8 ){
                return json(['code'=>'-9','msg'=>'会员禁止使用门店自用方式','data'=>'']);
            }
            //查询会员等级id
            $Member = new MemberModel();
            $memberWhere['id'] = $data['member'];
            $memberInfo = Db::name('member') ->where($memberWhere)->field('id,level_id,mobile,nickname')->find();
            if(!$memberInfo){
                return json(['code'=>'-3','msg'=>'会员错误','data'=>'']);
            }
        }
        if( empty($data['member']) || empty($memberInfo) ){
            $memberInfo['level_id'] = 1;
            $data['member'] = 0;
        }
        if( empty($data['goods']) ){
            return json(['code'=>'-3','msg'=>'请选择商品','data'=>'']);
        }
        $items = $data['goods'];	//全部商品
        $goods = [];	//普通商品
        $service_goods = [];	//服务商品
        foreach ($items as $key => $value) {
            if( !isset($value['is_service_goods']) ){
                return json(['code'=>'-3','msg'=>'请传入参数is_service_goods','data'=>'']);
            }
            if ( $value['is_edit'] == 1 && !isset($value['edit_price']) ) {
                return json(['code'=>'-3','msg'=>'当前有改价商品但未传入修改金额','data'=>'']);
            }
            if( !isset($value['num']) ){
                return json(['code'=>'-3','msg'=>'请选择数量','data'=>'']);
            }
            if( !isset($value['id']) ){
                return json(['code'=>'-3','msg'=>'请传入商品id','data'=>'']);
            }

            if ( $value['is_service_goods'] == 1 ) {
                if ( empty($value['waiter_id']) ) {
                    return json(['code'=>'-3','msg'=>'缺少服务项目服务人员id','data'=>'']);
                }
                array_push($service_goods, $value);
            }else if( $value['is_service_goods'] == 0 ){
                //表示为商品，则必须传入商品服务人员
                if ( empty($data['waiter']) ) {
                    return json(['code'=>'-3','msg'=>'缺少商品服务人员id','data'=>'']);
                }
                array_push($goods, $value);
            }
        }

        $s_order_outsourcing = 0;	//是否包含外包服务
        $order_outsourcing = 0;	//是否包含外包商品

        if( count($goods)>0 ){
            //查询商品服务人员的名称
            $Worker = new ShopWorkerModel();
            $workerInfo = $Worker ->where(['id'=>$data['waiter']])->find();
            //获取商品列表
//            $itemIds = array_column($goods, 'id');
//            $itemIds = implode(',',$itemIds);
//            $Item = new TP5ItemModel();
//            $item_list = $Item ->getGoods($itemIds,$shop_id);	//获取商品(库存，原价，最低改价，库存表id)列表
//            foreach ($item_list as $key => $value) {
//                foreach ($goods as $k => $v) {
//                    if( $value['id'] == $v['id'] ){
//                        if ( $value['stock'] < $v['num'] ) {	//库存不足
//                            return json(['code'=>'-4','msg'=>$value['title'].'商品库存有变动，请重新下单！','data'=>'']);
//                        }
//                        if( $v['is_edit'] == 1 && ($v['edit_price']<$value['minimum_selling_price']) ){
//                            return json(['code'=>'-4','msg'=>$value['title'].'商品价格不能低于最低价！','data'=>'']);
//                        }
//                        $goods[$k]['old_price'] = $value['price'];		//商品原价
//                        $goods[$k]['category_id'] = $value['type'];		//商品分类id
//                        $goods[$k]['type_id'] = $value['type_id'];			//商品1级分类的id
//                        $goods[$k]['title'] = $value['title'];
//                    }
//                }
//            }
//            //计算商品的平均成本
//            $Purchase= new Tp5PurchasePriceModel();
//            $cost = $Purchase ->itemCostPrice($goods,$shop_id);
//            $goods = $cost['data'];
//            $shop_item_array = [];	//shop_item需要减的库存的数据
//            foreach ($item_list as $key => $value) {		//组装shop_item需要减的库存的数据
//                foreach ($goods as $k => $v) {
//                    if( $value['id'] == $v['id'] ){
//                        $new_array = array(
//                            'id'	=>$value['shop_item_id'],
//                            'stock'	=>$value['stock']-$v['num']
//                        );
//                        array_push($shop_item_array, $new_array);
//                    }
//                }
//            }
//            foreach ($goods as $key => $value) {
//                //计算每个商品的单价,与每个商品的总价,修改的金额
//                if( $value['is_edit'] == 1 ){	//改过价格
//                    $goods[$key]['item_all_price'] = $value['edit_price']*$value['num'];		//单商品的总价
//                    $goods[$key]['danjia'] = $value['edit_price'];								//单商品的实际支付单价，方便后面order_price统计
//                }else{
//                    $goods[$key]['item_all_price'] = $value['old_price']*$value['num'];
//                    $goods[$key]['danjia'] = $value['old_price'];
//                    $goods[$key]['edit_price'] = 0;
//                }
//                //计算每个商品的原价的总价
//                $goods[$key]['item_old_all_price'] = $value['old_price']*$value['num'];
//                //判断是否为外包商品
//                $title = $value['title'];
//                $title_s = mb_substr($title,0,2,'utf-8');
//                if( $title_s === '外包' ){
//                    $goods[$key]['is_outsourcing_goods'] = 1;		//1外包商品，0不是
//                }else{
//                    $goods[$key]['is_outsourcing_goods'] = 0;
//                }
//            }
//
//            foreach ($goods as $key => $value) {
//                if( $value['is_outsourcing_goods'] == 1 ){
//                    $goods[$key]['cost_price'] = $value['danjia'];
//                    $goods[$key]['all_cost_price'] = $value['danjia']*$value['num'];
//                }
//            }
//
//            foreach ($goods as $key => $value) {
//                if( $value['is_outsourcing_goods'] == 1 ){
//                    $order_outsourcing = 1;
//                    break;
//                }
//            }


            //二期
            foreach ( $goods as $k=>$v )
            {
                $post_data = [];
                $post_data['warehouse_id'] = $shop_id;
                $post_data['g_type'] = 2;
                $post_data['goods_id'] = $v['id'];
                if ( !empty($v['key']) )
                {
                    $post_data['key'] = $v['key'];
                }

                $url = config('erp_url').'api/warehouse_goods/getList';
                $result = sendPost($url,$post_data);
                $result = json_decode($result,true);
                if ( $result['code'] !== 200 || $result['data']['total'] != 1 )
                {
                    return_error('商品出错');
                }
                $goods_info = $result['data']['data'][0];
                if ( bccomp($goods_info['w_actual_stock'],$v['num']) == -1 )
                {
                    return_error('库存不足');
                }
                $goods[$k]['title'] = $goods_info['g_title'];

                //计算每个商品的单价,与每个商品的总价,修改的金额
                if( $v['is_edit'] == 1 ){	//改过价格
                    $goods[$k]['item_all_price'] = bcmul($v['edit_price'],$v['num'],2);		//单商品的总价
                    $goods[$k]['danjia'] = $v['edit_price'];	//单商品的实际支付单价，方便后面order_price统计
                }else{
                    $goods[$k]['item_all_price'] = bcmul($goods_info['price'],$v['num'],2);
                    $goods[$k]['danjia'] = $goods_info['price'];
                    $goods[$k]['edit_price'] = 0;
                }
                $goods[$k]['old_price'] = $goods_info['price'];
                $goods[$k]['item_old_all_price'] = bcmul($goods_info['price'],$v['num'],2); //商品原价的总金额

                //判断外包
                $title = $goods_info['g_title'];
                $title_s = mb_substr($title,0,2,'utf-8');
                if( $title_s === '外包' ){
                    $goods[$k]['is_outsourcing_goods'] = 1;		//1外包商品，0不是
                    $goods[$k]['cost_price'] = $goods_info['price'];		//1外包商品的成本就是卖价
                    $goods[$k]['all_cost_price'] = bcmul($goods_info['price'],$v['num'],2);		//1外包商品的成本就是卖价
                }else{
                    $goods[$k]['is_outsourcing_goods'] = 0;
                }
            }

            //方便后面
            $order_outsourcing = 0;
            foreach ($goods as $key => $value) {
                if( $value['is_outsourcing_goods'] == 1 ){
                    $order_outsourcing = 1;
                    break;
                }
            }

            $allNum = 0;	//商品的总量
            $allPrice = 0;	//商品的总金额
            $old_all_price = 0;	//商品的总原价
            $all_cost_price_waibao = 0;    //外包商品的总成本
            foreach ($goods as $key => $value) {
                $allNum = bcadd($allNum,$value['num']);
                $allPrice = bcadd($allPrice,$value['item_all_price'],2);
                $old_all_price = bcadd($old_all_price,$value['item_old_all_price'],2);
                if( $value['is_outsourcing_goods'] == 1 ){
                    $all_cost_price_waibao = bcadd($all_cost_price_waibao,$value['all_cost_price'],2);
                }
            }
            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15 ){	//门店赠送总金额为0
                $allPrice = 0;
            }
        }

        if( count($service_goods)>0 ){
            //获取服务人员的名称
            $Worker = new ShopWorkerModel();
            foreach ($service_goods as $key => $value) {
                $service_goods[$key]['workid'] = $Worker ->where(['id'=>$value['waiter_id']])->value('workid');
                $service_goods[$key]['name'] = $Worker ->where(['id'=>$value['waiter_id']])->value('name');
            }

            $service_itemIds = array_column($service_goods, 'id');
            $Service = new Tp5ServiceModel();
            $service_goods_list = $Service ->getList($service_itemIds,$shop_id,$memberInfo['level_id']);
            if( count($service_goods_list)  == 0 ){
                return json(['code'=>'500','msg'=>'内部出错','data'=>'']);
            }

            foreach ($service_goods_list as $key => $value) {
                foreach ($service_goods as $k => $v) {
                    if ( $value['id'] == $v['id'] ) {
                        $service_goods[$k]['cost_price'] = $value['cost_price'];		//商品的价格
                        $service_goods[$k]['title'] = $value['sname'];		//商品名称
                    }
                }
            }

            foreach ($service_goods as $key => $value) {
                if( $value['is_edit'] == 1 ){	//改过价
                    $service_goods[$key]['item_all_price'] = $value['num']*$value['edit_price'];
                    $service_goods[$key]['danjia'] = $value['edit_price'];			//实际成交价，方便数据库统计
                }else{
                    $service_goods[$key]['item_all_price'] = $value['num']*$value['cost_price'];
                    $service_goods[$key]['danjia'] = $value['cost_price'];			//实际成交价，方便数据库统计
                    $service_goods[$key]['edit_price'] = 0;
                }

                //计算每个商品的原价的总价
                $service_goods[$key]['item_old_all_price'] =  $value['cost_price']*$value['num'];

                //判断是否为外包商品
                $title = $value['title'];
                $title_s = mb_substr($title,0,2,'utf-8');
                if( $title_s === '外包' ){
                    $service_goods[$key]['is_outsourcing_goods'] = 1;		//1外包商品，0不是
                }else{
                    $service_goods[$key]['is_outsourcing_goods'] = 0;
                }
            }

            foreach ($service_goods as $key => $value) {
                if( $value['is_outsourcing_goods'] == 1 ){
                    $s_order_outsourcing = 1;
                    break;
                }
            }

            $s_allNum = 0;	//总量
            $s_allPrice = 0;	//总金额
            $s_old_all_price = 0;	//总原价
            foreach ($service_goods as $key => $value) {
                $s_allNum += $value['num'];
                $s_allPrice += $value['item_all_price'];
                $s_old_all_price += $value['item_old_all_price'];
            }
            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15 ){	//门店赠送总金额为0
                $s_allPrice = 0;
            }
        }

        //生成订单
        if( count($goods)>0 && count($service_goods)>0 ){
            $type = 7;
            $order_triage = 2;
            $order_sn = 'FI'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$shop_id;
            $all_total_price = $s_allPrice + $allPrice ;	//总价
            $all_total_num = $s_allNum + $allNum ;	//总数量
            $all_total_old_price = $s_old_all_price +$old_all_price;//总原价
            $all_total_cost_price = $all_cost_price_waibao;	//总成本
        }else if( count($goods)<=0 && count($service_goods)>0 ){
            $order_sn = 'GD'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$shop_id;
            $type = 2;
            $order_triage = 1;
            $all_total_price = $s_allPrice;	//总价
            $all_total_old_price = $s_old_all_price;//总原价
            $all_total_num = $s_allNum;	//总数量
            $all_total_cost_price = 0;//总成本
        }else if( count($goods)>0 && count($service_goods)<=0 ){
            $order_sn = 'FG'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$shop_id;
            $type = 1;
            $order_triage = 0;
            $all_total_price = $allPrice ;	//总价
            $all_total_num =$allNum ;	//总数量
            $all_total_old_price = $old_all_price;//总原价
            $all_total_cost_price = $all_cost_price_waibao;//总成本
        }

        if( empty($workerInfo) ){
            $workerInfo['name'] = '';
            $workerInfo['id'] = 0;
        }

        if( $order_outsourcing == 1 || $s_order_outsourcing == 1 ){
            $is_outsourcing_goods = 1;	//包含外包
        }else if( $order_outsourcing == 0 && $s_order_outsourcing == 0 ){
            $is_outsourcing_goods = 0;	//不包含外包
        }

        // $order	//订单数据表
        if( !empty($data['remarks']) ){
            $remarks = $data['remarks'];
        }else{
            $remarks = '';
        }
        $order = array(
            'shop_id'	=>$shop_id,			//商铺id
            'member_id'	=>$data['member'],	//会员id
            'sn'		=>$order_sn,	//订单编号
            'type'		=>$type,				//订单类型 1:商品购买
            'number'	=>$all_total_num,			//总数量
            'amount'	=>$all_total_price,		//总金额
            'pay_status'=>'1',				//支付状态
            'pay_way'	=>$data['pay_way'],	//支付方式
            'add_time'	=>time(),			//创建时间
            'paytime'	=>time(),			//创建时间
            'dealwithtime'=>time(),			//门店处理时间
            'is_online'	=>'0',				//是否线上商城支付 默认为1，收银台等为0
            'waiter'	=>$workerInfo['name'],//服务人员名称
            'waiter_id'	=>$workerInfo['id'],	//服务人员id
            'order_type'=>'1',				//订单种类 1普通订单2预定订单
            'old_amount'=>$all_total_old_price,	//订单原价 商品原价+服务又会价
            'order_triage'=>$order_triage,	//0为商品订单1为服务订单2为商品、服务订单
            'order_status'	=>2,
            'is_outsourcing_goods'	=>$is_outsourcing_goods,
            'overtime'	=>time(),
            'user_id'	=>$this->getUserInfo()['id'],
            'remarks'   =>$remarks
        );
        //判断如果是余额支付余额是否不足
        if( !empty($data['member']) && ($data['pay_way'] == 3 || $data['pay_way'] == 13) ){
            $MemberMoney = new MemberMoneyModel();
            $me = $MemberMoney ->where('member_id',$data['member'])->value('money');
            if( !$me ){
                return json(['code'=>'-200','msg'=>'会员余额不足']);  //总余额不足
            }
            if( $me < $all_total_price ){
                return json(['code'=>'-200','msg'=>'会员余额不足']);  //总余额不足
            }
            //查询限时余额
            $expireMoneyWhere = [];
            $expireMoneyWhere[] = ['member_id','eq',$data['member']];
            $expireMoneyWhere[] = ['status','neq',2];
            $expireList = Db::name('member_money_expire')->where($expireMoneyWhere)->field('id,price,use_price,status,expire_time')->select();

            $expireMoney = 0;   //激活限时总余额
            $expireMoney1 = 0;   //未激活限时总余额
            foreach ( $expireList as $k=>$v ){
                if( $v['status'] == 1 && (time() <=$v['expire_time']) ){
                    //已激活未过期
                    $expireMoney += $v['price'] - $v['use_price'];
                }
                if( $v['status'] == 0 ){
                    //未激活
                    $expireMoney1 += $v['price'] - $v['use_price'];
                }
            }
            $all_expireMoney = $expireMoney + $expireMoney1;		//总的限时余额（包括未激活和未使用的限时余额）
            if( $data['pay_way'] == 3 && (($me-$all_expireMoney)<$all_total_price) ){
                //判断普通余额是否不足
                return json(['code'=>'-200','msg'=>'普通余额不足,当前普通余额:￥'.($me-$all_expireMoney).',限时余额可用:￥'.$expireMoney]);  //总余额不足
            }
            if( $data['pay_way'] == 13 && ($expireMoney<$all_total_price) ){
                //判断限时余额是否不足
                return json(['code'=>'-200','msg'=>'限时余额不足,当前普通余额:￥'.($me-$all_expireMoney).',限时余额可用:￥'.$expireMoney]);  //总余额不足
            }
        }

        //微信,支付宝支付
        if( $data['pay_way'] == 1 ){
            //微信支付
            $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
            try {
                // 支付授权码
                $auth_code = $data['auth_code'];
                $input = new WxPayMicroPay();
                $input->SetAuth_code($auth_code);
                $input->SetBody($shopName);
                $input->SetTotal_fee($order['amount']*100);//订单金额  订单单位 分
                // $input->SetTotal_fee(1);//订单金额  订单单位 分
                $input->SetOut_trade_no($order['sn']);
                $PayModel = new PayModel();
                $resPay = $PayModel ->pay($input);
                if( $resPay == false ){
                    return json(['code'=>100,'微信支付失败']);
                }
            } catch(Exception $e) {
                return json(['code'=>100,'微信支付失败']);
            }
        }else if($data['pay_way'] == 2){
            //支付宝支付
            $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
            $PayModel = new PayModel();
            $resPay = $PayModel ->AliCodePay($data['auth_code'],$order['sn'],$shopName,$order['amount']);
            if( $resPay['code'] != 200 ){
                $resPay['msg'] = '结账失败,支付宝扣款失败！！';
                return json($resPay);
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            //处理并发防止网络差多次操作
            $file = fopen('settlement_loke.txt','w+');
            if(flock($file,LOCK_EX|LOCK_NB))
            {
                $res = Db::name('order')->insertGetId($order);	//添加订单
                $orderId = $res;
                $erp_send_goods = [];   //调用erp发货系统的发货数据
                //添加副表
                if ( $res )
                {
                    if( count($goods)>0 ){
                        $order_goods = [];
                        foreach ($goods as $key => $value) {
                            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15  ){
                                $real_price = 0;    //成交单价
                                $modify_price = $value['old_price'];    //修改金额
                            }else{
                                $real_price = $value['danjia'];
                                $modify_price = abs($value['danjia']-$value['old_price']);
                            }
                            $newGoods = array(
                                'order_id'		=>$orderId,		//订单编号
                                'type_id'		=>$value['type_id'],	//一级分类id
                                'category_id'	=>$value['category_id'],	//分类id
                                'subtitle'		=>$value['title'],		//商品标题
                                'item_id'		=>$value['id'],			//商品id
                                'num'			=>$value['num'],		//数量
                                'price'			=>$value['old_price'],		//原单价
                                'oprice'		=>$value['cost_price'],		//成本价
                                'modify_price'	=>$modify_price,//改价,修改的金额
                                'real_price'	=>$real_price,	//实际支付金额
                                'status'		=>'1',
                                'is_outsourcing_goods'	=>$value['is_outsourcing_goods'],
                                'all_oprice'	=>$value['all_cost_price'],
                                'attr_ids'    =>!empty($value['key']) ? $value['key'] :'',
                                'attr_name'    =>!empty($value['attr_name']) ? $value['key'] :'',
                            );
                            // array_push($order_goods, $newGoods);
                            $orderGoodsId = Db::name('order_goods')->insertGetId($newGoods);    //添加订单商品表
                            $arr = [];
                            $arr = [
                                'join_id'   =>$orderId,
                                'og_id'   =>$orderGoodsId,
                                'desc'   =>!empty($data['member']) ? $memberInfo['nickname'] : '门店散客用户',
                                'item_id'   =>$value['id'],
                                'attr_ids'   =>!empty($value['key']) ? $value['key'] :'',
                                'num'   =>$value['num'],
                                'shop_id'   =>$shop_id,
                                'o_pay_type'   =>$data['pay_way'],
                                'order_warehouse_id'   =>$shop_id,
                                'o_sn'   =>$order_sn,
                            ];
                            array_push($erp_send_goods,$arr);
                        }
//                        dump($erp_send_goods);die;    //调用erp系统，因为回滚问题,所以放在最后调用
                        if( !empty($data['member']) && ($data['pay_way'] == 3 || $data['pay_way'] == 13) ){
                            //余额，添加累积消费记录
                            $detailsDate = array(
                                'member_id'		=>$data['member'],
                                'mobile'		=>$memberInfo['mobile'],
                                'reason'		=>'购买商品',
                                'addtime'		=>time(),
                                'amount'		=>$allPrice,
                                'type'			=>3,
                                'order_id'		=>$orderId
                            );
                            Db::name('member_details')->insert($detailsDate);	//添加累积消费
                            $MemberMoney ->where('member_id',$data['member'])->setDec('money',$allPrice);	//扣余额
                            if( $data['pay_way'] == 13 ){
                                //扣除限时余额
                                $expireList = self::expireList($data['member'],$allPrice);
                                if( !$expireList ){
                                    return json(['code'=>'100','msg'=>'显示余额不足']);
                                }
                                foreach ( $expireList as $k1=>$v1 ){
                                    //更改限时余额表
                                    Db::name('member_money_expire') ->where('id',$v1['id'])->setField('use_price',$v1['use_price']);
                                    //添加限时余额使用记录表
                                    $arr = [];
                                    $arr = [
                                        'member_id' =>$data['member'],
                                        'order_id' =>$orderId,
                                        'price' =>$v1['consume_price'],
                                        'money_expire_id' =>$v1['id'],
                                        'order_sn' =>$order_sn,
                                        'create_time' =>time(),
                                        'reason' =>'购买商品',
                                    ];
                                    Db::name('member_expire_log') ->insert($arr);
                                }
                            }
                        }


                    }
                }

                if ( $res )
                {
                    if( count($service_goods)>0 ){
                        $s_order_goods = [];
                        foreach ($service_goods as $key => $value) {
                            if( $data['pay_way'] == 7 || $data['pay_way'] == 8 || $data['pay_way'] == 15  ){
                                $real_price = 0;    //成交单价
                            }else{
                                $real_price = $value['danjia'];
                            }
                            $s_newGoods = array(
                                'order_id'		=>$orderId,		//订单编号
                                'member_id'		=>$data['member'],	//会员id
                                'sid'	=>$shop_id,
                                'sn'		=>$order_sn,
                                'paytime'		=>time(),
                                'yytime'		=>time(),
                                'status'		=>1,
                                'workid'		=>$value['workid'],
                                'workerid'	=>$value['waiter_id'],
                                'name'	=>$value['name'],
                                'addtime'		=>time(),
                                'locktime'		=>time(),
                                'num'		=>$value['num'],
                                'price'		=>$value['cost_price'],
                                'real_price'		=>$real_price,
                                'service_id'		=>$value['id'],
                                'service_name'		=>$value['title'],
                                'state'		=>'1',
                                'is_outsourcing_goods'	=>$value['is_outsourcing_goods']

                            );
                            array_push($s_order_goods, $s_newGoods);
                        }
                        Db::name('service_goods')->insertAll($s_order_goods);
                        if( !empty($data['member']) && ($data['pay_way'] == 3 || $data['pay_way'] == 13) ){
                            //余额，添加累积消费记录
                            $detailsDate = array(
                                'member_id'		=>$data['member'],
                                'mobile'		=>$memberInfo['mobile'],
                                'reason'		=>'购买服务项目',
                                'addtime'		=>time(),
                                'amount'		=>$s_allPrice,
                                'type'			=>4,
                                'order_id'		=>$orderId
                            );
                            Db::name('member_details')->insert($detailsDate);
                            $MemberMoney ->where('member_id',$data['member'])->setDec('money',$s_allPrice);	//扣余额

                            if( $data['pay_way'] == 13 ){
                                //扣除限时余额
                                $expireList = self::expireList($data['member'],$s_allPrice);
                                if( !$expireList ){
                                    return json(['code'=>'100','msg'=>'限时余额不足']);
                                }
                                foreach ( $expireList as $k1=>$v1 ){
                                    Db::name('member_money_expire') ->where('id',$v1['id'])->setField('use_price',$v1['use_price']);
                                    $arr = [];
                                    $arr = [
                                        'member_id' =>$data['member'],
                                        'order_id' =>$orderId,
                                        'price' =>$v1['consume_price'],
                                        'money_expire_id' =>$v1['id'],
                                        'order_sn' =>$order_sn,
                                        'create_time' =>time(),
                                        'reason' =>'购买服务',
                                    ];
                                    Db::name('member_expire_log') ->insert($arr);
                                }
                            }
                        }
                    }
                }

                if ( $res )
                {
                    // 统计类型:1:余额充值,2:购卡,3:消耗收款,4:余额消耗,5消费消耗,6商品外包分润,7推拿外包分润,8商品成本,9营业费用,10外包商品成本
                    $arry = [];
                    $arry = array(
                        'order_id'      =>$orderId,
                        'shop_id'       =>$shop_id,
                        'order_sn'      =>$order_sn,
                        'data_type'     =>1,
                        'pay_way'       =>$data['pay_way'],
                        'price'         =>$all_total_price,
                        'create_time'   =>time(),
                        'title'         =>'购买商品'
                    );
                    $statisticsLog = [];
                    //下单数据
                    if ( $data['pay_way'] == 3 || $data['pay_way'] == 13 )
                    {
                        $arry['type'] = 4;
                        array_push($statisticsLog,$arry);
                    }else{
                        $arry['type'] = 3;
                        array_push($statisticsLog,$arry);
                        $arry['type'] = 5;
                        array_push($statisticsLog,$arry);
                    }

                    if ( $all_total_cost_price > 0 )
                    {
                        $arry['type'] = 8;
                        array_push($statisticsLog,$arry);
                    }
                    $res = Db::name('statistics_log')	->insertAll($statisticsLog);	//添加股东统计数据表数据
                }

                if ( count($erp_send_goods) > 0 )
                {
                    $url = config('erp_url').'api/send_goods/sendGoods';
                    $post_send_data = ['items'=>$erp_send_goods,'order_type'=>2];

                    $result = sendPost1($url,[],$post_send_data);
                    $res = $result['code'] == 200 ? 1 :0 ;
                }

                if ( $res )
                {
                    Db::commit();
                    //释放并发进程
                    flock($file,LOCK_UN);
                }else{
                    return json(['code'=>100,'msg'=>'系统繁忙，请稍后再试']);
                }

            }else{
                return json(['code'=>100,'msg'=>'系统繁忙，请稍后再试']);
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //关闭并发进程
            fclose($file);
            return json(['code'=>'500','msg'=>$e->getMessage(),'data'=>$e]);
        }
        return json(['code'=>'200','msg'=>'结算成功','data'=>'']);
    }

    /***
     * @param $memberId
     * @param $allPrice
     * @return array|bool,其中根据数组的id修改use_price值,consume_price每条记录使用的金额
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function expireList( $memberId , $allPrice ){
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
}
