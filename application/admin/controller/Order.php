<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\order\OrderModel;
use app\admin\model\order\OrderRefundModel;
use app\admin\common\Logs;
use app\admin\controller\LogsController;
use app\admin\model\order\OrderTp5Model;
use app\common\model\PayModel;
use app\common\model\UtilsModel;
use app\index\model\Member\MemberMoneyModel;
use app\index\model\Member\MemberModel;
use app\index\model\Member\MemberRechargeLogModel;
use app\index\model\Member\MemberDetailsModel;
use app\index\model\Order\Order as IndexOrderModel;

use app\wxshop\wxpay\WxPayMicroPay;
use think\Db;

/**
 * 订单模块
 */
class Order extends Adminbase
{
    protected function initialize()
    {
        parent::initialize();
        /*  $this->AdminUser = new AdminUser;*/
    }
    /**
     * 门店商品订单列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $field = $this->request->get();
            $res = $field['field'];
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $Order = new OrderTp5Model();
            if(isset($res['search']) && !empty($res['search'])){
                $where[] = ['a.sn|m.mobile|og.subtitle',"like","%{$res['search']}%"];
            }
            //门店搜索
            if(isset($res['shop']) && !empty($res['shop'])){
                $where[] = ['a.shop_id',"=",$res['shop']]; 
            }
            //支付方式搜索
            if(isset($res['pay_way']) && !empty($res['pay_way'])){
                $where[] = ['a.pay_way',"=",$res['pay_way']];
            }
            //订单状态搜索
            if(isset($res['status']) && !empty($res['status'])){
                $where[] = ['a.order_status',"=",$res['status']];
            }
            //订单对账状态搜索
            if(isset($res['is_examine']) && $res['is_examine'] != '' ){
                $where[] = ['a.is_examine',"=",$res['is_examine']];
            }
            //开始时间 
            if(isset($res['start_time']) && !empty($res['start_time'])){
                $start_time = strtotime($res['start_time']);
                $where[] = ['a.add_time',">=",$start_time];
            }
            // 结束时间
            if(isset($res['end_time']) && !empty($res['end_time'])){
                $end_time = strtotime($res['end_time']) + 86399;
                $where[] = ['a.add_time',"<=",$end_time];
            }


            $where[] = ["type","in",'1,2,7'];
            $where[] = ["is_online","eq",0];
            $_list = $Order
                    ->alias("a")
                    ->where($where)
                    ->field("a.id,a.shop_id,a.sn,a.member_id,a.type,a.amount,a.overtime,a.pay_way,a.waiter,a.waiter_id,a.order_status,m.mobile,a.remarks,a.is_examine,a.refund_num")
                    ->join("ddxm_member m","a.member_id=m.id",'LEFT')
                    ->join("order_goods og","og.order_id = a.id",'left')
                    ->page($page,$limit)
                    ->order("overtime desc")
                    ->group('a.id')
                    ->select()
                    ->append(['message','item_list','price_list','num_list','waiter_list','cost_list']);
            $total = Db::name("order")->alias("a")->where($where)->join("ddxm_member m","a.member_id=m.id","left")->join("order_goods og","og.order_id = a.id",'left')->group('a.id')->count();
            $result = array("code" => 0, "count" => $total, "data" => $_list);
            return json($result);
        }
        $data['shop'] = db::name("shop")->field("id,name")->select();
        $data['worker'] = Db::name("shop_worker")->field("id,name")->where("status",1)->select();
        $this->assign("data",$data);
        return $this->fetch();
    }
    public function service(){
        if ($this->request->isAjax()) {
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            $Order = new OrderModel();
            $_list = Db::name("order")
                    ->alias("a")
                    ->where("type",2)
                    ->field("a.id,a.shop_id,a.sn,a.member_id,a.type,a.amount,a.overtime,a.pay_way,a.waiter,a.waiter_id,a.order_status,m.mobile")
                    ->join("ddxm_member m",' a.member_id = m.id','LEFT')
                    ->page($page,$limit)
                    ->order("overtime desc")
                    ->select();
            $_list = $Order->postOrderListDatas($_list);
            $total = Db::name("order")->where("type",2)->count();
            $result = array("code" => 0, "count" => $total, "data" => $_list);
            return json($result);
        }
        return $this->fetch();
    }
    //获取订单用户数据/门店服务人员数据
    public function user(){
        //判断是否提交数据
        if($this->request->isPost()){
            //获取提交数据
            $data = $this->request->post();
            // 实例化model
            $Order = new OrderModel();
            //根据传递的参数获取对应的数据
            $data =$Order->getUserName($data['title'],$data['id']);
            //返回json数据
            return json(['code'=>1,'msg'=>"获取成功",'data'=>$data]);
        }else{
            return json(['code'=>0,"msg"=>"暂无数据",'data'=>""]);
        }

    }
    // 获取订单明细表单
    public function goodslist(){
        $data = $this ->request ->param();
        if ( empty($data['order_id']) ) {
            return json(['code'=>'-3','msg'=>'请传入订单id','data'=>'']);
        }
        $item = [];
        $order_type = Db::name('order')->where('id',$data['order_id'])->value('type');
        if( $order_type == 1 ){
            $item1 = Db::name('order_goods')->where('order_id',$data['order_id'])->select();
            foreach ($item1 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                        'id'    =>$value['item_id'],
                        'subtitle' =>$value['subtitle'],
                        'num' =>$value['num'],
                        'oprice' =>$value['oprice'],
                        'price' =>$value['price'],
                        'real_price'=>$value['real_price'],
                        'real_total' =>$value['real_price']*$value['num'],
                        'refund'    =>$refund,
                        'modify_price'=>$value['modify_price'],
                        'is_service_goods'  =>0
                    );
                array_push($item,$arr);
            }
        }
        if( $order_type == 2 ){
            $item2 = Db::name('service_goods')->where('order_id',$data['order_id'])->select();
            foreach ($item2 as $key => $value) {
                
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                        'id'    =>$value['service_id'],
                        'subtitle' =>$value['service_name'],
                        'num' =>$value['num'],
                        'oprice' =>0,
                        'price' =>$value['price'],
                        'real_price'=>$value['real_price'],
                        'real_total' =>$value['real_price']*$value['num'],
                        'refund'    =>$refund,
                        'modify_price'=>$value['real_price']-$value['price']
                    );
                array_push($item,$arr);
            }
        }
        if( $order_type == 7 ){
            $item1 = Db::name('order_goods')->where('order_id',$data['order_id'])->select();
            $item2 = Db::name('service_goods')->where('order_id',$data['order_id'])->select();
            foreach ($item1 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                        'id'    =>$value['item_id'],
                        'subtitle' =>$value['subtitle'],
                        'num' =>$value['num'],
                        'oprice' =>$value['oprice'],
                        'price' =>$value['price'],
                        'real_price'=>$value['real_price'],
                        'real_total' =>$value['real_price']*$value['num'],
                        'refund'    =>$refund,
                        'modify_price'=>$value['modify_price'],
                        'is_service_goods'  =>0
                    );
                array_push($item,$arr);
            }

            foreach ($item2 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                        'id'    =>$value['service_id'],
                        'subtitle' =>$value['service_name'],
                        'num' =>$value['num'],
                        'oprice' =>0,
                        'price' =>$value['price'],
                        'real_price'=>$value['real_price'],
                        'real_total' =>$value['real_price']*$value['num'],
                        'refund'    =>$refund,
                        'modify_price'=>$value['real_price']-$value['price']
                    );
                array_push($item,$arr);
            }
        }
        $count = count($item);
        return json(['code'=>'0','msg'=>'查询成功','count'=>$count,'data'=>$item]);
    }
    //获取服务明细表单
    public function servicegoodslist(){
        $res = $this->request->get();
        $data = db::name("service_goods")->where("order_id",intval($res['id']))->select();
        $count = count($data);
        $order = new OrderModel();
        $data = $order->getServiceGoodsList($data);
        $result = array("code" => 0, "count" => $count, "data" => $data);
        return json($result);
    }
    public function refundlist(){
        $res = $this->request->get();
        $data = db::name("order_refund")->where("order_id",intval($res['id']))->select();
        $count = count($data);
        $order = new OrderModel();
        $data = $order->refundList($data);
        $result = array("code" => 0, "count" => $count, "data" => $data);
        return json($result);
    }
    public function refundgoods(){
        $res = $this->request->get();
        $data = db::name('order_refund_goods')->where("refund_id",$res['id'])->select();
        $count = count($data);
        $order = new OrderModel();
        $data = $order->refundGoodsList($data,$res['type']);
        $result = array("code" => 0, "count" => $count, "data" => $data);
        return json($result);
    }
    //门店订单详情
    public function details(){
        $res = $this->request->get();
        $data = Db::name("order")->field("id,shop_id,sn,member_id,type,old_amount,"
                . "amount,overtime,pay_way,waiter,waiter_id,order_status,is_online")
                ->where("id",intval($res['id']))->find();
        $order = new OrderModel();
        $data = $order->getOrderDetails($data);
        $this->assign('data',$data);
        return $this->fetch();
    }
    public function s_details(){
        $res = $this->request->get();
        $data = Db::name("order")->field("id,shop_id,sn,member_id,type,old_amount,"
                . "amount,overtime,pay_way,waiter,waiter_id,order_status,is_online")
                ->where("id",intval($res['id']))->find();
        $order = new OrderModel();
        $data = $order->getOrderDetails($data);
        $this->assign('data',$data);
        return $this->fetch();
    }
    //订单退货
    public function refund(){
        $res = $this->request->get();
        if(isset($res['order_id'])||$res['order_id']){
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data  = db::name("order_refund_goods")
                ->alias('a')
                ->where('a.refund_id',$res['order_id'])
                ->select();
            foreach($data as $key => $val){
                if($val['is_service_goods'] ==0){
                    $goods = db::name("order_goods")->where("id",$val['og_id'])->find();
                    if( !$goods ){
                        unset($data[$key]);
                    }

                    $data[$key]['number'] = $goods['num'];
                    $data[$key]["price"] = $goods['real_price'];
                    $data[$key]['refund'] = $goods['refund'];
                }else if($val['is_service_goods'] ==1){
                    $goods = db::name("service_goods")->where("id",$val['og_id'])->find();
                    if( !$goods ){
                        unset($data[$key]);
                    }
                    $data[$key]['number'] = $goods['num'];
                    $data[$key]["price"] = $goods['real_price'];
                    $data[$key]['refund'] = $goods['refund'];
                }
                $order_info = Db::name('order')->where('id',$goods['order_id'])->field('is_online')->find();
                if( !$order_info ){
                    unset($data[$key]);
                }
                if( $order_info['is_online'] == 1 ){
                    unset($data[$key]);
                }
            }
            $count = db::name("order_refund_goods")->where("refund_id",$res['order_id'])->count();
            /*$order = new OrderModel();
            $data = $order->getRefundDetails($data);*/
            $result = array("code" => 0, "count" =>$count, "data" => $data);
            return json($result);
        }else{
            $data = DB::name("order_refund")->where("id",intval($res['id']))
                ->withAttr("r_type",function($value,$data){
                    return $this->getPayWayAttr($data['r_type']);
                })
                ->withAttr("otype",function($value,$data){
                    return $this->refundWay($data['otype']);
                })
                ->withAttr("create_time",function($value,$data){
                    return date("Y-m-d H:i:s");
                })
                ->withAttr("status",function($value,$data){
                    if($data['r_status']==0){
                        return "待处理";
                    }else if($data['r_status']==1){
                        return "已完成";
                    }else if($data['r_status'] ==2){
                        return "已取消";
                    }else if($data['r_status'] ==3){
                        return "已拒绝";
                    }
                })
                ->find();
            $this->assign("data",$data);
            return $this->fetch();
        }
    }
    //订单退货列表 
    public function refund_list(){
        $res  = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d', 10);
        $data  = db::name("order_refund")
        ->where("order_id",intval($res['id']))
        ->withAttr("r_type",function($value,$data){
            return $this->getPayWayAttr($data['r_type']);
        })
        ->withAttr("otype",function($value,$data){
            return $this->refundWay($data['otype']);
        })
        ->withAttr("dealwith_time",function($value,$data){
            if($data['dealwith_time'] == 0){
                return '无';
            }
            return date("Y-m-d H:i:s",$data['dealwith_time']);
        })
        ->withAttr("r_status",function($value,$data){
            if($data['r_status']==0){
                return "待处理";
            }else if($data['r_status']==1){
                return "已完成";
            }else if($data['r_status'] ==2){
                return "已取消";
            }else if($data['r_status'] ==3){
                return "已拒绝";
            }
        })
        ->page($page,$limit)
        ->select();
        $count = Db::name("order_refund")->where("order_id",intval($res['id']))->count();
        return json(['code' => 0, 'count' =>$count, "data" => $data]);
    }
    public function refundWay($val){
        if(empty($val)){
            return "数据未知";
        }
        $status =[
            1=>'现金退款',
            2=>"余额退款",
            3=>"银行卡退款",
        ];
        return $status[$val];
    }
    public function getPayWayAttr($value){
        if (empty($value)) {
            return '未支付';
        }
        $status = [
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
            15  =>'框框宝',
            99 => '异常充值'
        ];
        return $status[$value];
    }
    //服务退货列表
    public function s_refund(){
        $res = $this->request->get();
        if(isset($res['order_id'])||$res['order_id']){
            $data  = db::name("service_goods")->where("order_id",$res['order_id'])->where("status",1)->field("id,order_id,sid,num,refund,real_price,service_id,service_name")->select();
            $count = count($data);
            $order = new OrderModel();
            $data = $order->getRefundDetails($data);
            $result = array("code" => 0, "count" =>$count, "data" => $data);
            return json($result);
        }else{
            $this->assign("data",$res);
            return $this->fetch();
        }
    }
    //确认退货
    public function orderrefund(){
        $res = $this->request->post();
        $type = $res['type'];
        $refund = Db::name("order_refund")->where("id",intval($res['id']))->find();
        if(!$refund){
            return json(['result'=>false,"msg"=>"订单不存在",'data'=>'']);
        }
        if($refund['status']!=0 && $refund['r_status'] != 0){
            return json(['result'=>false,"msg"=>"数据错误","data"=>""]);
        }
        $order = db::name("order")->where("id",$refund['order_id'])->find();
        $update_refund = [
            "r_status"=>1,
            "status"=>1,
            "update_time"=>time(),
            "dealwith_time"=>time(),
            "creator"=> Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            "creator_id"=>session("admin_user_auth")['uid'],
        ];

        $refund_goods_where = [];
        $refund_goods_where[] = ['refund_id','eq',$refund['id']];
        $refund_goods_where[] = ['s_id','neq','null'];
        $refund_goods = db::name("order_refund_goods")->where($refund_goods_where)->select();
        $db = Db::name("order_refund");
        $model=new Shareholder();
        $statistics_item = [
            "order_id"=>$refund['order_id'],
            "shop_id"=>$refund['shop_id'],
            "order_sn"=>$refund['o_sn'],
            /*"type"=>3,*/
            "data_type"=>2,
            "pay_way"=>$refund['otype']==1?5:($refund['otype']==2?3:4),
            "create_time"=>time(),
            "title"=>"商品退货",
        ];
        $db->startTrans();
        try{
            $service_price = 0;
            $item_price = 0;
            $s_price_item = 0;
            $gs_refund_cost_price = 0; //公司成本退款（股东数据）
            foreach($refund_goods as $key => $val){

                if($val["status"] != 0 || $val["status"] != null){
                    break;
                }
                //修改 订单退货详情里面的状态 为已处理
                $update_goods['status'] =1;
                db::name("order_refund_goods")->where("id",$val['id'])->update($update_goods);
                //判断 如果是商品
                if($val['is_service_goods'] ==0){
                    $order_goods_item = db::name("order_goods")->where("id",$val['og_id'])->find();
                    $s_price_item += ($order_goods_item["oprice"])*$val['r_num'];

                    //查询订单的商品明细
                    $order_goods = db::name("order_goods")->where("id",$val['og_id'])->find();
                    // 剩余可退货数量和 当前退货数量
                    if(($order_goods['num'] - $order_goods['refund'])<$val['r_num']){
                        //退货数量不足时
                        $db->rollback();
                        return json(['result'=>false,"msg"=>$val['r_subtitle'].":商品数量不足","data"=>""]);
                    }else{
                        //退货数量足够时
                        db::name("order_goods")->where("id",$val['og_id'])->setInc("refund",$val['r_num']);
                        $order_goods_cost = db::name("order_goods_cost")->where("order_goods_id",$val['og_id'])->find();
                        Db::name("shop_item")->where("shop_id",$order_goods_cost['shop_id'])->where("item_id",$order_goods_cost['item_id'])->setInc("stock",$val['r_num']);
                        Db::name("purchase_price")->where("id",$order_goods_cost['purchase_price_id'])->setInc("stock",$val['r_num']);

                        //判断是否存在公司成本，如果存在则累加退回成本(股东数据)
                        $is_gs_cost = Db::name("purchase_price")->where("id",$order_goods_cost['purchase_price_id'])->find();
                        if(bccomp($is_gs_cost['store_cose'],$order_goods_item['oprice'],2) != 0)
                        {
                            $gs_refund_cost_price+=bcmul(bcsub($is_gs_cost['store_cose'],$order_goods_item['oprice'],2),$val['r_num'],2);
                        }
                    }
                    $item_price += $val['r_num'] * $val['r_price'];
                }else if($val['is_service_goods'] ==1){
                    $service_goods = db::name("service_goods")->where("id",$val['og_id'])->find();
                    if(($service_goods['num'] - $service_goods['refund'])<$val['r_num']){
                        //退货数量不足时
                        $db->rollback();
                        return json(['result'=>false,"msg"=>$val['r_subtitle'].":商品数量不足","data"=>""]);
                    }else{
                        db::name("service_goods")->where("id",$val['og_id'])->setInc("refund",$val['r_num']);
                    }
                    $service_price += $val['r_num'] * $val["r_price"];
                }
            }
            if($order['is_outsourcing_goods'] ==1){
                $statistics_item['price'] = -$s_price_item;
                $statistics_item['type'] = 10;
                db::name('statistics_log')->insert($statistics_item);
            }else{
                $statistics_item['price'] = -$s_price_item;
                $statistics_item['type'] = 8;
                db::name('statistics_log')->insert($statistics_item);
            }
            $statistics_log = [
                "order_id"=>$refund['order_id'],
                "shop_id"=>$refund['shop_id'],
                "order_sn"=>$refund['o_sn'],
                /*"type"=>3,*/
                "data_type"=>2,
                "pay_way"=>$refund['otype']==1?5:($refund['otype']==2?3:4),
                "price"=>-$refund['r_amount'],
                "create_time"=>time(),
                "title"=>"商品退货",
            ];
            if($refund['r_type'] == 3 || $refund['r_type'] == 13){
                if($refund['otype'] ==2){
                    //余额购买 退款到余额   =>余额消耗
                    $order_mobile = db::name("order")->where("id",$refund['order_id'])->value("member_id");
                    if($order_mobile == 0 || $order_mobile ==null){
                        $db->rollback();
                        return json(['result'=>false,"msg"=>"会员不存在","data"=>""]);
                    }
                    $member = db::name("member_money")->where("member_id",$order_mobile)->find();
                    $member_money = $refund['r_amount'] + $member['money'];
                    db::name("member_money")->where("member_id",$order_mobile)->update(["money"=>$member_money]);
                    $member_details = [
                        'member_id'=>$member['member_id'],
                        "mobile"=>$member['mobile'],
                        "reason"=>"商品退货增加".$refund['r_amount'],
                        "addtime"=>time(),
                        "amount"=>$refund['r_amount'],
                        "type"=>2,
                        "order_id"=>$refund['order_id'],
                    ];
                    Db::name("member_details")->insert($member_details);
                    $statistics_log["type"] = 4;
                    db::name("statistics_log")->insert($statistics_log);
                }else{
                    //余额购买 退款到非余额 =>余额充值
                    $order_mobile = db::name("order")->where("id",$refund['order_id'])->find();
                    if($order_mobile["member_id"] == 0 || $order_mobile["member_id"] ==null){
                        $db->rollback();
                        return json(['result'=>false,"msg"=>"会员不存在","data"=>""]);
                    }
                    $member = db::name("member_money")->where("member_id",$order_mobile["member_id"])->find();
                    $data = [
                        "type"=>5,
                        "member_id"=>$order_mobile["member_id"],
                        "pay_way"=>$order_mobile['pay_way'],
//                        "waiter_id"=>session("admin_user_auth")['uid'],
                        "waiter_id"=>$order['waiter_id'],
                        "price"=>-($refund['r_amount']),
                        "title"=>"商品退单充值",
                        "remarks"=>"商品退单",
                    ];
                    if($service_price > 0){
                        $data['price'] = '-'.$service_price;
                        $data['title'] = "服务退单";
                        $res_model  = $model->refund_recharge($data);
                    }
                    if($item_price >0){
                        $data['price'] = '-'.$item_price;
                        $res_model  = $model->refund_recharge($data);
                    }
                    if(!$res_model){
                        $db->rollback();
                        return json(["result"=>false,'msg'=>"系统繁忙，请稍后再试","data"=>"res_model"]);
                    }
                    $statistics_log["type"] = 4;
                    db::name("statistics_log")->insert($statistics_log);

                }
            }else{
                //非余额购买  退款到余额   =>余额充值
                if($refund['otype'] ==2){
                    /*$statistics_log = 1; */
                    $order_mobile = db::name("order")->where("id",$refund['order_id'])->find();
                    if($order_mobile["member_id"] == 0 || $order_mobile["member_id"] ==null){
                        $db->rollback();
                        return json(['result'=>false,"msg"=>"会员不存在","data"=>""]);
                    }
                    $member = db::name("member_money")->where("member_id",$order_mobile["member_id"])->find();
                    $data = [
                        "type"=>5,
                        "member_id"=>$member['member_id'],
                        "pay_way"=>$order_mobile['pay_way'],
                        "waiter_id"=>session("admin_user_auth")['uid'],
                        "price"=>$refund['r_amount'],
                        "title"=>"商品退单充值",
                        "remarks"=>"商品退单",
                    ];
                    if($service_price > 0){
                        $data['price'] = $service_price;
                        $data['title'] = "服务退单";
                        $res_model  = $model->refund_recharge($data);
                    }
                    if($item_price >0){
                        $data['price'] = $service_price;
                        $res_model  = $model->refund_recharge($data);
                    }
                    if(!$res_model){
                        $db->rollback();
                        return json(["result"=>false,'msg'=>"系统繁忙，请稍后再试","data"=>"res_model"]);
                    }
                    $statistics_log["type"] = 3;
                    db::name("statistics_log")->insert($statistics_log);
                    $statistics_log["type"] = 5;
                    db::name("statistics_log")->insert($statistics_log);
                }else{
                    //非余额购买  退款到非余额 =>消费消耗
                    $statistics_log["type"] = 3;
                    db::name("statistics_log")->insert($statistics_log);
                    $statistics_log["type"] = 5;
                    db::name("statistics_log")->insert($statistics_log);
                }
            }

            //如果是限时余额退款
            if($refund['r_type'] == 13)
            {
                $expire_id = db::name('member_expire_log')->where(['order_id'=>$refund['order_id']])->value('money_expire_id');

                if (empty($expire_id))
                {
                    return json(["result"=>false,'msg'=>"限时余额退款错误-1","data"=>"res_model"]);
                }

                //回退限时余额已使用额度
                $result = db::name('member_money_expire')->where(['id'=>$expire_id])->setDec('use_price',$refund['r_amount']);

                //增加普通余额字额度
                if(!empty($result))
                {
                    //$res = db::name('member_money')->where(['member_id'=>$order['member_id']])->setInc('money',$refund['r_amount']);

                    if(!empty($result))
                    {
                        $expire_log_data = [
                            'member_id'=>$order['member_id'],
                            'order_id'=>$order['id'],
                            'price'=>-$refund['r_amount'],
                            'money_expire_id'=>$expire_id,
                            'order_sn'=>$order['sn'],
                            'create_time'=>time(),
                            'reason'=>'退款：门店退款'
                        ];
                        $result = db::name('member_expire_log')->insert($expire_log_data);

                        if(empty($result))
                        {
                            return json(["result"=>false,'msg'=>"限时余额退款错误-3","data"=>"res_model"]);
                        }
                    }
                }else{
                    return json(["result"=>false,'msg'=>"限时余额退款错误-2","data"=>"res_model"]);
                }
            }

            //判断是否存在公司成本，如果存在则退一个负成本
            if(!empty($gs_refund_cost_price))
            {
                $gs_refund_cost['order_id'] = $order['id'];
                $gs_refund_cost['shop_id'] = 1;
                $gs_refund_cost['order_sn'] = $order['sn'];
                $gs_refund_cost['data_type']= 2;
                $gs_refund_cost['type']= 8;
                $gs_refund_cost['price']= -$gs_refund_cost_price;
                $gs_refund_cost['create_time']= $statistics_item['create_time'];
                $gs_refund_cost['title']= '商品退货';
                $gs_refund_cost['pay_way']= $statistics_item['pay_way'];

                $result = db::name('statistics_log')->insert($gs_refund_cost);

                if(empty($result))
                {
                    db::rollback();
                    return json(["result"=>false,'msg'=>"公司成本退回错误","data"=>""]);
                }
            }

            $order_goods_num = db::name("order_goods")->where("order_id",$refund['order_id'])->sum("num");
            $order_goods_refund =db::name("order_goods")->where("order_id",$refund['order_id'])->sum("refund");
            $service_goods_num = db::name("service_goods")->where("order_id",$refund['order_id'])->sum("num");
            $service_goods_refund =db::name("service_goods")->where("order_id",$refund['order_id'])->sum("refund");
            $goods_num = $order_goods_num +$service_goods_num;
            $goods_refund = $order_goods_refund +$service_goods_refund;
            if($goods_refund >0){
                if($goods_refund <$goods_num){
                    $update_order['order_status'] = -3;
                }else if($goods_refund == $goods_num){
                    $update_order['order_status'] = -6;
                }
                db::name("order")->where("id",$refund['order_id'])->update($update_order);
            }
            Db::name("order_refund")->where("id",intval($res['id']))->update($update_refund);

            $db->commit();
            return json(["result"=>true,"msg"=>"操作成功","data"=>""]);
        }catch(\Exception $e){
            $error = $e->getMessage();
            $db->rollback();
            return json(["result"=>false,"msg"=>"系统错误","data"=>$error]);

        }
    }
    public function orderrefund_close(){
        $res = $this->request->post();
        $type = $res['type'];
        $refund = Db::name("order_refund")->where("id",intval($res['id']))->find();
        if(!$refund){
            return json(['result'=>false,"msg"=>"订单不存在",'data'=>'']);
        }
        if($refund['status']!=0 && $refund['r_status'] != 0){
            return json(['result'=>false,"msg"=>"数据错误","data"=>""]);
        }
        $update_refund = [
            "r_status"=>3,
            "status"=>1,
            "update_time"=>time(),
            "dealwith_time"=>time(),
            "creator"=> Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            "creator_id"=>session("admin_user_auth")['uid'],
        ];
        $refund_goods = db::name("order_refund_goods")->where("refund_id",$refund['id'])->select();
        $db = Db::name("order_refund");
        $db->startTrans();
        try{
             foreach($refund_goods as $key => $val){
                if($val["status"] != 0 || $val["status"] != null){
                    break;
                }
                //修改 订单退货详情里面的状态 为已拒绝
                $update_goods['status'] =2;
                db::name("order_refund_goods")->where("id",$val['id'])->update($update_goods);
            }
            Db::name("order_refund")->where("id",intval($res['id']))->update($update_refund);
            $db->commit();
            return json(["result"=>true,"msg"=>"操作成功","data"=>""]);
        }catch(\Exception $e){
            $error = $e->getMessage();
            $db->rollback();
            return json(["result"=>false,"msg"=>"系统错误","data"=>$error]);

        }
    }
    // 服务退货
    public function servicerefund(){
        if($this->request->isPost()){
            $data = $this->request->post("");
            $order = new OrderRefundModel();
            $result = $order->editServiceRefund($data);
            return json($result);
        }
    }
    //门店服务卡订单
    public function ticket_list(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $data['shop'] = db::name("shop")->where("delete_id",0)->field("name,id")->select();
            $data['worker'] = db::name("shop_worker")->where("delete_id",0)->field("name,id")->select();
            $this->assign("data",$data);
            return $this->fetch();
        }else{
            $field = $res['field'];
            $where = [];
            if( isset($field['shop']) && !empty($field['shop']) ){
                $where[] = ['o.shop_id',"=",intval($field['shop'])]; 
            }
            if(isset($field['worker']) && !empty($field['worker'])){
                $where[] = ['a.waiter_id',"=",intval($field['worker'])]; 
            }
            if(isset($field['pay_way']) && !empty($field['pay_way'])){
                $where[] = ['o.pay_way',"=",intval($field['pay_way'])]; 
            }
            if(isset($field['status']) && !empty($field['status'])){
                $where[] = ['o.order_status',"=",intval($field['status'])]; 
            }
            //订单对账状态搜索
            if(isset($field['is_examine'])  && $field['is_examine'] != ''  ){
                $where[] = ['o.is_examine',"=",$field['is_examine']];
            }
            //开始时间 
            if(isset($field['start_time']) && !empty($field['start_time'])){
                $start_time = strtotime($field['start_time']);
                $where[] = ['o.overtime',">=",$start_time];
            }
            // 结束时间
            if(isset($field['end_time']) && !empty($field['end_time'])){
                $end_time = strtotime($field['end_time']) + 86399;
                $where[] = ['o.overtime',"<=",$end_time];
            }
            if(isset($field['search']) && !empty($field['search'])){

                $where[] = ['o.sn|a.mobile',"like","%{$field['search']}%"]; 
               /* $where[] = ['',"like","%{$res['search']}%"]*/
            }
            $where[] = ['o.type',"eq",5];
            $where[] = ['a.create_time',">",1571295714];        //只查询上线之后的订单(新系统防止重复)
            $order = new OrderModel();
            $data = $order->ticket_list($where,$res);
            foreach ($data as $k=>$v){
                $service = self::ticket_service(['id'=>$v['ticket_id']])['data'];
                $arr2 = array_column($service, 'service_name');
                $arr2 = implode(',',$arr2);
                $data[$k]['services'] = $arr2;
            }
            $count = Db::name("ticket_user_pay")->alias("a")->where($where)->join("ddxm_order o","a.order_id = o.id")->count();
            $result = array("code" => 0, "count" =>$count, "data" => $data);
            return json($result);  
        }
    }

    public function ticket_service($res){
        $data = Db::name("ticket_service")
            ->where("card_id",intval($res['id']))
            ->field("day,month,year,id,service_name,num")
            ->withAttr("day",function($value,$data){
                if($data['day']==0){
                    return "无限制";
                }
                return $data['day'];
            })
            ->withAttr("month",function($value,$data){
                if($data['month']==0){
                    return "无限制";
                }
                return $data['month'];
            })
            ->withAttr("year",function($value,$data){
                if($data['year']==0){
                    return "无限制";
                }
                return $data['year'];
            })
            ->withAttr("num",function($value,$data){
                if($data['num']==0){
                    return "无限制";
                }
                return $data['num'];
            })
            ->select();

        $total = Db::name("ticket_service")->where("card_id",intval($res['id']))->count();
        if($data){
            return ["code"=>0,"count"=>$total,'data'=>$data];
        }else{
            return ["code"=>0,"count"=>0,"data"=>[]];
        }
    }

    //门店服务卡详情
    public function ticket_details(){
        $res = $this->request->get();
        $order = new OrderModel();
        $data = $order->ticket_details($res);
        $this->assign("data",$data);
        return $this->fetch();
    }
    //门店服务卡订单明细列表
    public function ticketgoodslist(){        
        $res = $this->request->get();
        $order = new OrderModel();
        $data = $order->ticketgoodslist($res);
        $count = Db::name("ticket_use")->where("ticket_id",intval($res['id']))->count();
        $result = ['code'=>0,'count'=>$count,"data"=>$data];
        return json($result);
    }
    //服务卡退单
    public function ticketrefund(){
        $order = new OrderRefundModel();
        if($this->request->isPost()){
            $res = $this->request->post();
            $data = $order ->orderticketrefund($res);
            return json($data);
        }else{
            $res = $this->request->get();
            $data = $order->ticketrefund($res);
            $this->assign("data",$data);
            return $this->fetch();
        }
        
    }
    /**
     * 添加管理员
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.insert');
            if (true !== $result) {
                return $this->error($result);
            }
            if ($this->AdminUser->createManager($data)) {
                $this->success("添加管理员成功！", url('admin/manager/index'));
            } else {
                $error = $this->AdminUser->getError();
                $this->error($error ? $error : '添加失败！');
            }

        } else {
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

    /**
     * 管理员编辑
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.update');
            if (true !== $result) {
                return $this->error($result);
            }
            if ($this->AdminUser->editManager($data)) {
                $this->success("修改成功！");
            } else {
                $this->error($this->User->getError() ?: '修改失败！');
            }
        } else {
            $Item = new ItemModel();
            $id = $this->request->param('id/d');
            $data = $Item->dbmysql("item")->where(array("id" => $id))->find();
            if (empty($data)) {
                $this->error('该信息不存在！');
            }
            $this->assign("data", $data);
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

    /**
     * 管理员删除
     */
    public function del()
    {
        $id = $this->request->param('id/d');
        if ($this->AdminUser->deleteManager($id)) {
            $this->success("删除成功！");
        } else {
            $this->error($this->AdminUser->getError() ?: '删除失败！');
        }
    }

    //充值订单
    public function recharge_list(){
        if ($this->request->isAjax()) {
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request->param();
            $where = [];
            if( !empty($data['name']) ){
                $name = $data['name'];
                $where[] = ['m.mobile|a.sn','like',"%$name%"];
            }
            if( !empty($data['shop_id']) ){
                $where[] = ['a.shop_id','=',$data['shop_id']];
            }
            if( !empty($data['waiter_id']) ){
                $where[] = ['a.waiter_id','=',$data['waiter_id']];
            }
            if( !empty($data['pay_way']) ){
                $where[] = ['a.pay_way','=',$data['pay_way']];
            }
            if( !empty($data['time']) ){
                $time = strtotime($data['time']);
                $end_time = strtotime($data['end_time']);
                $where[] = ['add_time','between',$time.','.$end_time];
            }
            //订单对账状态搜索
            if(isset($data['is_examine'])  && $data['is_examine'] != ''  ){
                $where[] = ['a.is_examine',"=",$data['is_examine']];
            }

            $where[] = ['a.type','eq',3];
            $where[] = ['a.isdel&l.is_delete','eq',0];
            // dump($where);
            $Order = new OrderModel();
            $_list = Db::name("order")
                    ->alias("a")
                    ->where($where)
                    ->field("a.id,a.shop_id,a.sn,a.member_id,a.type,m.nickname,a.amount,a.overtime,a.pay_way,a.waiter,a.waiter_id,a.order_status,m.mobile,l.remarks,a.is_examine")
                    ->join("ddxm_member m",' a.member_id = m.id','LEFT')
                    ->join("ddxm_member_recharge_log l","a.id=l.order_id",'left')
                    ->page($page,$limit)
                    ->order("overtime desc")
                    ->select();
            $_list = $Order->postOrderListDatas($_list);
            $total = Db::name("order")
                    ->alias("a")
                    ->where($where)
                    ->field("a.id,a.shop_id,a.sn,a.member_id,a.type,a.amount,a.overtime,a.pay_way,a.waiter,a.waiter_id,a.order_status,m.mobile,l.remarks")
                    ->join("ddxm_member m",' a.member_id = m.id','LEFT')
                    ->join("ddxm_member_recharge_log l","a.id=l.order_id",'left')
                    ->page($page,$limit)
                    ->order("overtime desc")
                    ->count();
            $result = array("code" => 0, "count" => $total, "data" => $_list);
            return json($result);
        }
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);


        return $this->fetch();
    }

    //获取服务人员
    public function waiter_select(){
        $sid = $this ->request ->param('shop_id');
        $list = Db::name('shop_worker')->where('sid',$sid)->field('id,name')->select();
        return json(['code'=>1,'data'=>$list]);
    }

    //充值订单详情
    public function order_info(){
        $res = $this->request->get();
        $data = Db::name("order")->field("id,shop_id,sn,member_id,type,old_amount,"
                . "amount,overtime,pay_way,waiter,waiter_id,order_status,is_online")
                ->where("id",intval($res['id']))->find();

        $order = new OrderModel();
        $data = $order->getOrderDetails($data);

        $this->assign('data',$data);

        $member = Db::name('member')->where('id',$data['member_id'])->field('mobile,nickname,level_id')->find();
        $member['level_name'] = Db::name('member_level')->where('id',$member['level_id'])->value('level_name');
        $member['money'] = Db::name('member_money')->where('member_id',$member['member_id'])->value('money');
        $this ->assign('member',$member);
        return $this->fetch();
    }

    //充值订单充值
    public function recharge(){
        session('codes', mt_rand(1000, 9999));
        return $this->fetch();
    }

    //充值操作
    public function doPost(){
        $data = $this ->request->post();
        if($data['codes'] != session('codes')){
            return json(['code'=>100,'msg'=>'正在处理您的请求请稍后~']);
        }else{
            session('codes', mt_rand(1000, 9999));
        }
        if( empty($data['price']) || empty($data['pay_way']) || empty($data['remarks']) || empty($data['mobile']) ){
            return json(['code'=>0,'msg'=>'请输入必填内容']);
        }
        if( $data['type'] == 2 ){
            if ( ($data['year'] == 0) && ($data['month'] == 0) && ($data['day'] == 0) ) {
                return json(['code'=>0,'msg'=>'过期时间必须大于等于1天']);
            }
        }
        if( $data['type'] == 2 && ( $data['pay_way'] == 1 || $data['pay_way'] == 2 ) ){
            if( empty($data['code']) ){
                return json(['code'=>0,'msg'=>'请扫秒条形码']);
            }
        }
        //计算过期时间,数据库已最小单位  天数计算
        $expireDay = $data['year']*365 + $data['month']*30 + $data['day'];
        $Member = new MemberModel();
        $IndexOrder = new IndexOrderModel();
        $MemberMoney = new MemberMoneyModel();
        $MemberRechargeLog = new MemberRechargeLogModel();
        $MemberDetails = new MemberDetailsModel();

        $shop_id = $Member ->getMessage(['mobile'=>$data['mobile']],'shop_id');
        $member_id = $Member ->getMessage(['mobile'=>$data['mobile']],'id');

        $tt = $Member ->where('mobile',$data['mobile'])->find();
        if( !$tt ){
           return json(['code'=>0,'msg'=>'会员不存在']); 
        }
        //判断余额是否不足
        if( $data['type'] == 1 ){
            //反充值，扣普通余额
            $userMoney = $MemberMoney ->where('mobile',$data['mobile'])->value('money');
            if( $userMoney <$data['price'] ){
                return json(['code'=>0,'msg'=>'余额不足']);
            }
            //查询限时余额的总和，判断普通余额是否足够
            $expireMoneyWhere = [];
            $expireMoneyWhere[] = ['member_id','eq',$member_id];
            $expireMoneyWhere[] = ['status','eq',1];
            $expireMoneyWhere[] = ['expire_time','>=',time()];
            $expireList = Db::name('member_money_expire')->where($expireMoneyWhere)->field('id,price,use_price')->select();
            $expireMoney = 0;       //总的限时余额
            foreach ( $expireList as $k=>$v ){
                $expireMoney += $v['price'] - $v['use_price'];
            }
            //判断普通余额是否足够
            if ( ($userMoney - $expireMoney) < $data['price'] ) {
                return json(['code'=>0,'msg'=>'普通余额不足,其中总余额:￥'.$userMoney.',限时余额:￥'.$expireMoney]);
            }
        }
        $level_standard = Db::name('level_price')
                ->alias('a')
                ->join('member_level b','a.level_id=b.id')
                ->where(['a.shop_id'=>$shop_id])
                ->order('b.sort desc')
                ->field('a.level_id,a.price,b.sort')
                ->select();
        $amount = $MemberDetails ->where(['mobile'=>$data['mobile'],'type'=>1])->sum('amount');  //累积充值
        if( $data['type'] == 1 ){
            $new_amount = $amount - $data['price'];     //降级
        }else{
            $new_amount = $amount + $data['price'];     //升级
        }
        $levelWhere = [];
        $levelWhere[] = ['shop_id','=',$shop_id];
        $levelWhere[] = ['price','<=',$new_amount];
        $level = Db::name('level_price')->where($levelWhere)
            ->order('price desc')->find();
        if( $level ){
            $new_level = $level['level_id'];
        }else{
            $new_level = 1;
        }
        $order_sn = 'XM'.time().$shop_id;
        // 生成订单表信息
        if( $data['type'] == 2 ){
            $amount_price = $data['price'];
        }else{
            $amount_price = '-'.$data['price'];
        }
        $order = array(
            'shop_id'   =>$shop_id,
            'member_id' =>$member_id,
            'sn'        =>$order_sn,
            'type'      =>3,
            'amount'    =>$amount_price,
            'number'    =>1,
            'pay_status'=>1,
            'pay_way'   =>$data['pay_way'],
            'paytime'   =>time(),
            'overtime'  =>time(),
            'dealwithtime'=>time(),
            'order_status'=>'-6',      //已完成
            'add_time'  =>time(),
            'is_online' =>0,
            'is_admin'  =>1,
            'order_type'=>1,
            'old_amount'=>'-'.$data['price'],
            'waiter'    =>session('admin_user_auth')['username'],       //操作人员名字
            'waiter_id' =>session('admin_user_auth')['uid']        //操作人员id
        );
        //生成会员表明细数据、member_recharge_log
        $rechargeLog = array(
            'member_id'     =>$member_id,
            'shop_id'       =>$shop_id,
            'price'         =>$amount_price,
            'pay_way'       =>$data['pay_way'],
            // 'is_only_service'=>$data['is_only_service'],        //是否只限制服务使用：1只能服务使用,0都可使用(暂时无用)
            'remarks'       =>$data['remarks'],
            'create_time'   =>time(),
            'type'          =>2
        );

        //生成股东数据统计表数据ddxm_statistics_log
        $statisticsLog = array(
            'shop_id'       =>$shop_id,
            'order_sn'      =>$order_sn,
            'type'          =>1,
            'data_type' =>$data['type']==2?1:2,
            'pay_way'       =>$data['pay_way'],
            'price'         =>$amount_price,
            'create_time'   =>time(),
            'title' =>$data['type']==2?'充值限时余额':'反充值'
        );
        // 启动事务
        Db::startTrans();
        try {
            $orderId = Db::name('order') ->insertGetId($order);    //添加订单表订单
            $rechargeLog['order_id'] = $orderId;
            $MemberRechargeLog ->insert($rechargeLog);  //添加充值记录
            $MemberMoney ->where('member_id',$member_id)->setInc('money',$amount_price);   //增加余额
            $Member ->where('mobile',$data['mobile'])->update(['level_id'=>$new_level]);         //新的会员等级
            // 生成累积充值记录
            $MemberDetailsData = array(
                    'member_id'     =>$member_id,
                    'mobile'        =>$data['mobile'],
                    'remarks'       =>$data['remarks'],
                    'reason'        =>'充值'.$amount_price.'元',
                    'addtime'       =>time(),
                    'amount'        =>$amount_price,
                    'type'          =>1,
                    'order_id'      =>$orderId
                );
            $MemberDetails ->insert($MemberDetailsData);

            $statisticsLog['order_id'] = $orderId;
            Db::name('statistics_log')  ->insert($statisticsLog);

            if( $data['type'] == 2 && ( $data['pay_way']==1 || $data['pay_way'] == 2 ) ){
                //微信支付宝支付
                if( $data['pay_way'] == 1 ){
                    //微信支付
                    $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
                    // 支付授权码
                    $input = new WxPayMicroPay();
                    $input->SetAuth_code($data['code']);
                    $input->SetBody($shopName);
                    $input->SetTotal_fee($amount_price*100);//订单金额  订单单位 分
                    $input->SetOut_trade_no($order_sn);
                    $PayModel = new PayModel();
                    $resPay = $PayModel ->pay($input);
                    if( $resPay == false ){
                        throw new \Exception("结账失败,微信扣款失败！");
                    }
                }else if($data['pay_way'] == 2){
                    //支付宝支付
                    $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
                    $PayModel = new PayModel();
                    $resPay = $PayModel ->AliCodePay($data['code'],$order_sn,$shopName,$amount_price);
                    if( $resPay['code'] != 200 ){
                        $resPay['msg'] = '结账失败,支付宝扣款失败！！';
                        throw new \Exception("结账失败,支付宝扣款失败！！");
                    }
                }
            }
            //p判断是是否为限时余额
            if( $data['type'] == 2 ){
                $expire = array(
                    'member_id' =>$member_id,
                    'price'     =>$data['price'],
                    'use_price' =>0.00,
                    'create_time'=>time(),
                    'expire_time'=>0,
                    'status'=>0,
                    'expire_day'=>$expireDay,
                    'order_id'=>$orderId,
                );
                Db::name('member_money_expire')->insert($expire);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>'500','msg'=>$e->getMessage(),'data'=>'']);
        }
        return json(['code'=>'1','msg'=>'充值成功','data'=>'']);
    }

    //查询会员昵称
    public function findMember(){
        $mobile = $this ->request ->param('mobile');
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $mobile);
        if (!$ruleResult) {
            $result['code'] = 0;
            $result['msg'] = '手机号格式错误';
            return json($result);
        }
        $nickName = Db::name('member')->alias('a')
            ->where(['a.mobile'=>$mobile])
            ->join('member_money b','a.id=b.member_id')
            ->field('a.id,a.nickname,b.money')
            ->find();
        if( !$nickName ){
            return json(['code'=>0,'msg'=>'会员不存在']);
        }
        $guoqi = self::getLimitedPrice1(['member_id'=>$nickName['id']]);
        $nickName['money'] = bcsub($nickName['money'],$guoqi,2);
        $nickName['guoqi'] = $guoqi;
        return json(['code'=>1,'msg'=>'查询成功','data'=>$nickName]);
    }

    public function getLimitedPrice1($data){
        $map = [];
        $map[] = ['member_id','eq',$data['member_id']];
        $map[] = ['status','in','0,1'];
//            $map[] = ['expire_time','>=',time()];
        $list = Db::name('member_money_expire') ->where($map)->field('id,price,use_price,expire_time,status,expire_day')->select();
        $Utils = new UtilsModel();
        $info = []; //数据
        foreach ($list as $k=>$v){
            $list[$k]['limited'] = $v['price']-$v['use_price'];
            if( $v['use_price'] <$v['price'] ){
                $arr = [];
                $arr = [
                    'id'    =>$v['id'],
                    'price'    =>$v['price']-$v['use_price'],
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
            $allPrice = bcadd($allPrice,$v['price'],2);
        }
        return $allPrice;
    }

    //批量对账
    public function edit_status(){
        $data = $this ->request ->param();
        if( count($data)<=0 ){
            return json(['code'=>0,'msg'=>'请先选择']);
        }
        $ids = implode(',',$data['ids']);
        $where[] = ['id','in',$ids];
        $result = Db::name('order')->where($where)->update(['is_examine'=>1]);
        if( $result ){
            return json(['code'=>1,'msg'=>'对账成功']);
        }else{
            return json(['code'=>0,'msg'=>'对账失败']);
        }
    }

    //对账
    public function duizhang(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = Db::name('order')->where('id',$data['id'])->setField('is_examine',1);
        if( $result ){
            $this ->success('对账成功');
        }else{
            $this ->error('对账失败');
        }
    }
    //对账
    public function quxiao(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = Db::name('order')->where('id',$data['id'])->setField('is_examine',0);
        if( $result ){
            $this ->success('操作成功');
        }else{
            $this ->error('操作失败');
        }
    }
    public function ticket_list_details(){
        $res = $this->request->get();
        $data = db::name("order_refund")->where("order_id",intval($res['id']))
        ->find();
        $data['ticket'] = db::name("ticket_user_pay")
        ->where("order_id",intval($res['id']))
        ->withAttr("start",function($value,$data){
            if($data['status'] ==0){
                return "未激活";
            }
            if($data['start_time'] ==0){
                return "未激活";
            }
            return date("Y-m-d",$data['start_time']);
        })
        ->withAttr("end",function($value,$data){
            if($data['status'] ==0){
                return "未激活";
            }
            return date("Y-m-d",$data['end_time']);
        })
        ->withAttr("create_time",function($value,$data){
            /*if($data['status'] ==0){
                return "未激活";
            }*/
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        
        ->find();
        if($data['type'] !=1){
            $data['ticket']['months'] = $this->month_numbers(date("Y-m-d",$data['ticket']['start_time']),date("Y-m-d",time()));
        }
        if($data['type'] ==1){
            $data['type_text'] = "次卡";
        }else if($data['type']==2){
            $data['type_text'] = "月卡";
        }else if($data['type']==4){
            $data['type_text'] = "年卡";
        }
        if($data){
            $this->assign("data",$data);
            return $this->fetch(); 
        }else{
            return "暂无数据";
        }
    }
    public function month_numbers($start_m,$end_m){ //日期格式为2018-8-28
        $date1 = explode('-',$start_m);
        $date2 = explode('-',$end_m);

        if($date1[1]<$date2[1]){ //判断月份大小，进行相应加或减
            $month_number= abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
        }else{
            $month_number= abs($date1[0] - $date2[0]) * 12 - abs($date1[1] - $date2[1]);
        }
        return $month_number+1;
    }
    public function ticket_consume_details(){

        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $data['shop'] = DB::name("shop")->where("status",1)->where("delete_id",0)->field("id,name")->select();
            $data['waiter'] = db::name("shop_worker")->where("status",1)->field("id,name")->select();
            $data['service'] = db::name("service")->where("status",1)->field("id,sname as name")->select();
            $this->assign("data",$data);
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $field = $res['field'];
            $where = [];
            if(isset($field['shop']) && !empty($field['shop'])){
                $where[] = ['a.shop_id',"=",intval($field['shop'])];
            }
            if(isset($field['waiter']) && !empty($field['waiter'])){
                $where[] = ['a.waiter_id',"=",intval($field['waiter'])];
            }
            if(isset($field['service']) && !empty($field['service'])){
                $where[] = ['a.service_id',"=",intval($field['service'])];
            }
            if(isset($field['status']) && !empty($field['status'])){
                $num = $field['status'] -1;
                $where[] = ['a.state',"=",$num];
            }
            $data = db::name("ticket_consumption")
            ->alias("a")
            ->where($where)
            ->field("a.id,a.member_id,a.shop_id,a.service_name,a.service_id,a.waiter_id,a.waiter,a.price,a.state,m.nickname,s.name as shop_name,a.time,a.num")
            ->withAttr("time",function($value,$data){
                return date("Y-m-d H:i:s",$data['time']);
            })
            ->join("member m","a.member_id = m.id")
            ->join("shop s","a.shop_id= s.id")
            ->order("time","desc")
            ->page($page,$limit)
            ->select();
            $total = db::name("ticket_consumption")->alias("a")->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $data);
            return json($result);
        }
    }
    public function consume_shop(){
        $res = $this->request->post();
        $id = intval($res['id']);
        $where = [];
        if($id !== 0){
            $where[]  = ['sid',"=",$id];
        }      
        $data = db::name("shop_worker")->where($where)->field("id,name")->select();
        if($data){
            return json(['result'=>true,"msg"=>"获取成功","data"=>$data]);
        }else{
            return json(['result'=>false,"msg"=>"暂无服务人员","data"=>""]);
        }
    }
    public function consume_edit_status(){
        $res = $this->request->post();
        $ids = $res['ids'];
        foreach($ids as $key =>$val){
            $res = db::name("ticket_consumption")->where("id",intval($val))->update(['state'=>1]);
        }
        if($res){
            return json(['result'=>true,"msg"=>"批量对账成功","data"=>""]);
        }else{
            return json(['result'=>false,"msg"=>"系统错误，请稍后重试","data"=>""]); 
        }
    }
}
