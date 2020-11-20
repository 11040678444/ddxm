<?php
/**
	门店订单系统

*/
namespace app\index\controller;

use app\index\model\Order\Order as OrderModel;
use app\index\model\Service\Service as ServiceModel;
use app\index\model\Order\OrderRefund as RefundModel;
use app\index\model\Order\OrderGoodsModel;

use app\admin\model\order\OrderTp5Model;
use app\index\model\Member\MoneyExpireLogModel;

/*use app\index\model\MemberModel;
use app\index\model\WorkerModel;
use app\index\model\ServiceModel;*/

use app\wxshop\model\member\MemberExpireLogModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;

class Order extends Base
{

/*
    门店商品订单列表
    shop_code  门店id
	type       订单类型 1 门店订单  2 门店订单 3:充值购卡 4:收银台收款 5:购买卷卡（服务劵，服务卡...） 6:兑换券 默认为1
 */

    //门店订单ticket_list
    public function order_list(){
    	$shop_id = $this->getUserInfo()['shop_id'];
    	if(empty($shop_id)){
    		return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
    	}
		$Order = new OrderTp5Model();
		$res = $this ->request ->param();
		$data["limit"] = $this->request->param('limit/d', 10);
	    $data['page'] = $this->request->param('page/d',0);
        $where = [];
        if(isset($res['search']) && !empty($res['search'])){
            $where[] = ['a.sn|m.mobile',"like","%".$res['search']."%"];
        }
        //支付方式搜索
        if(isset($res['pay_way']) && !empty($res['pay_way'])){
            $where[] = ['a.pay_way',"=",$res['pay_way']];
        }
        //订单状态搜索
        if(isset($res['status']) && !empty($res['status'])){
            $where[] = ['a.order_status',"=",$res['status']];
        }
        //订单状态搜索
        if(isset($res['waiter_id']) && !empty($res['waiter_id'])){
            $where[] = ['a.waiter_id',"=",$res['waiter_id']];
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
        if( $res['type'] == 1 || $res['type'] == 2 ){
            $where[] = ["type","in",'1,2,7'];
        }else if( $res['type'] == 3 ){
            $where[] = ["type","in",'3'];
        } 
        if( isset($res['goods_type']) && !empty($res['goods_type']) ){
            if( $res['goods_type'] == 1 ){
                $where[] = ["type","=",'1'];
                $where[] = ["is_outsourcing_goods","=",'0'];
            }else if( $res['goods_type'] == 2 ){
                $where[] = ["type","=",'2'];
                $where[] = ["is_outsourcing_goods","=",'0'];
            }else if( $res['goods_type'] == 3 ){
                $where[] = ["type","=",'1'];
                $where[] = ["is_outsourcing_goods","=",'1'];
            }else if( $res['goods_type'] == 4 ){
                $where[] = ["type","=",'2'];
                $where[] = ["is_outsourcing_goods","=",'1'];
            }
        }
        $where[] = ['a.shop_id',"=",$shop_id];
        $_list = $Order
                ->alias("a")
                ->field("a.id,m.nickname,a.is_online,a.shop_id,a.sn,a.member_id,a.type,a.amount,a.overtime,a.pay_way,a.waiter,a.waiter_id,a.order_status,m.mobile,m.nickname,a.order_status as status")
                ->join("ddxm_member m","a.member_id=m.id",'LEFT')
                ->page($data['page'],$data['limit'])
                ->order("overtime desc")
                ->where($where)
                ->select()
                ->append(['message','item_list','price_list','num_list','waiter_list','cost_list']);
        $total = $Order->alias("a")->join("ddxm_member m","a.member_id=m.id","left")->where($where)->count();
	    return json(['code'=>'200','count'=>$total,'msg'=>'查询成功','data'=>$_list]);
    }

    //限时余额过期列表
    public function expireList(){
        $data = $this ->request ->param();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',0);
        $where = [];
        if( !empty($data['member']) ){
            $where[] = ['b.nickname|b.wechat_nickname','like','%'.$data['member'].'%'];
        }
        if( !empty($data['mobile']) ){
            $where[] = ['b.mobile','like','%'.$data['member'].'%'];
        }
        if( !empty($data['sn']) ){
            $where[] = ['a.sn','like',$data['sn']];
        }
        if( !empty($data['start_time']) ){
            $where[] = ['a.craete_time','>=',strtotime($data['start_time'].' 00:00:00')];
        }
        if( !empty($data['end_time']) ){
            $where[] = ['a.craete_time','>=',strtotime($data['end_time'].' 23:59:59')];
        }
        $shop_id = $this->getUserInfo()['shop_id'];
        $where[] = ['a.shop_id','eq',$shop_id];
        $list = (new MoneyExpireLogModel())
            ->alias('a')
            ->join('member b','a.member_id=b.id')
            ->where($where)
            ->field('a.money_expire_id as id,b.mobile,b.nickname,b.wechat_nickname,a.craete_time,a.sn,a.price,a.pay_way,a.remarks,a.member_id')
            ->page($page,$limit)
            ->order('a.id desc')
            ->select();
        $count = (new MoneyExpireLogModel())
            ->alias('a')
            ->join('member b','a.member_id=b.id')
            ->where($where)
            ->count();
        return json(['code'=>'200','msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    /**
     * 获取限时余额详情
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function expireInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择限时余额']);
        }
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $expireMoney = Db::name('member_money_expire') ->where('id',$data['id'])->find();
        if( !$expireMoney ){
            return json(['code'=>100,'msg'=>'服务发生错误','data'=>'id错误,未找到此用户的此id下的限时余额']);
        }
        $arr =[];
        $arr = [
            'money' =>$expireMoney['price'],
            'title' =>'余额充值',
            'order_id' =>$expireMoney['order_id'],
            'create_time' =>date('m-d H:i:s',$expireMoney['create_time'])
        ];
        //查询余额记录
        $list = (new MemberExpireLogModel())
            ->where('money_expire_id',$data['id'])
            ->field('price as money,reason as title,order_id,create_time')
            ->page($page)->select()->toArray();
        foreach ( $list as $k=>$v ){
            if( $v['money'] < 0 ){
                $list[$k]['title'] .= '(返款)';
            }
        }
        $count = (new MemberExpireLogModel())
                ->where('money_expire_id',$data['id'])
                ->count()+1;
        array_unshift($list,$arr);

        $expireInfo = [
            'money' =>$expireMoney['price'] - $expireMoney['use_price'],
            'status' =>$expireMoney['status'],
            'expire_time' =>$expireMoney['expire_time'] != 0?date('Y-m-d H:i:s',$expireMoney['expire_time']):''
        ];
        $res = [];
        $res = [
            'expireInfo'    =>$expireInfo,
            'list'          =>$list
        ];
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$res]);
    }

    public function waiter(){
    	$shop_id = $this->getUserInfo()['shop_id'];
    	if(empty($shop_id)){
    		return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
    	}
		$order = new OrderModel();
		$data = $this ->request ->param();
		$result = $order->getwaiter($data['id']);
		if($result){
			return json(['code'=>'200','msg'=>'查询成功','data'=>$result]);
		}else{
			return json(['code'=>'500','msg'=>'服务器内部错误，请稍后重试','data'=>""]);
		}
    }
    public function order_details(){
    	$shop_id = $this->getUserInfo()['shop_id'];
    	if(empty($shop_id)){
    		return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
    	}
    	$order = new OrderModel();
		$data = $this ->request ->param();
        $result = $order->orderDetails($data);
		if($result){
            return json(['code'=>'200','msg'=>'查询成功','data'=>$result]);
		}else{
            return json(['code'=>'500','msg'=>'服务器内部错误，请稍后重试','data'=>""]);
        }
    }
    //
    public function order_refund(){
        $shop_id = $this->getUserInfo()['shop_id'];
    	if(empty($shop_id)){
    		return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
    	}
    	$order = new RefundModel();
		$data = $this ->request ->param();
        $result = $order->order_refund($data,$shop_id);
        if(count($result) == 0 ){
             return json(['code'=>'200','msg'=>'暂无数据','data'=>""]);
        }else if($result){
            return json(['code'=>'200','msg'=>'查询成功','data'=>$result]);
        }else{
            return json(['code'=>'500','msg'=>'服务器内部错误，请稍后重试','data'=>""]);
        }
    }
    //商品退单
    public function refund(){
    	$shop_id = $this->getUserInfo()['shop_id'];
    	if(empty($shop_id)){
    		return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
    	}
        $res = $this ->request ->post();
        $orderrefund = Db::name('order')->where("id",intval($res['order_id']))->where("shop_id",intval($shop_id))->find();
        if(!$orderrefund){
            return json(['code'=>402,'msg'=>'订单不存在或不能查看','data'=>""]);
        }
        if($orderrefund['order_status'] === -6){
            return json(['code'=>402,'msg'=>"服务器内部错误，该订单已退完无法继续退单",'data'=>""]);
        }
        $item = [];
        $order  = db::name("order_goods")->where("order_id",$res['order_id'])->where("status",1)->field("id,order_id,subtitle,num,refund,real_price,item_id")->select();
        $service = Db::name('service_goods')->where('order_id',$res['order_id'])->where("status",1)->field("id,order_id,service_name as subtitle,num,refund,real_price,service_id as item_id")->select();
        if($order){
            foreach ($order as $key => $value) {
                $arr = array(
                    'id'    =>$value['item_id'],
                    'order_id' =>$value['order_id'],
                    'subtitle' =>$value['subtitle'],
                    'num' =>$value['num'],
                    'refund'=>$value['refund'],
                    'real_price' =>$value['real_price'],
                    'item_id'    =>$value['item_id'],
                    'is_service_goods'  =>0,
                    "s_id"=>$value['id'],
                    'total' => $value['num'] *$value['real_price'],
                    'refund_num' => $value['num'] - $value['refund'],
                    'refund_old_num' => $value['num'] - $value['refund'],
                    'refund_price' => $value['real_price'],
                );
            array_push($item,$arr);
            }
        }
        if($service){
            foreach ($service as $key => $value) {
                $arr = array(
                    'id'    =>$value['item_id'],
                    'order_id' =>$value['order_id'],
                    'subtitle' =>$value['subtitle'],
                    'num' =>$value['num'],
                    'refund'=>$value['refund'],
                    'real_price' =>$value['real_price'],
                    'item_id'    =>$value['item_id'],
                    'is_service_goods'  =>1,
                    "s_id"=>$value['id'],
                    'total' => $value['num'] *$value['real_price'],
                    'refund_num' => $value['num'] - $value['refund'],
                    'refund_old_num' => $value['num'] - $value['refund'],
                    'refund_price' => $value['real_price'],
                    );
                array_push($item,$arr);
            }
        }
        $count = count($item);
        if($count !== 0 ){
            return json(['code'=>'200','msg'=>'查询成功',"count" =>$count, "data" => $item]);
        }else{
            return json(['code'=>'500','msg'=>'服务器内部错误，请稍后重试','data'=>""]);
        }
    }
     //订单商品明细
    public function order_goods_list(){
    	$data = $this ->request ->param();
    	if ( empty($data['order_id']) ) {
    		return json(['code'=>'-3','msg'=>'请传入订单id','data'=>'']);
    	}

        $item = [];
        $OrderGoods = new OrderGoodsModel();
        $order_type = Db::name('order')->where('id',$data['order_id'])->value('type');
        if( $order_type == 1 ){
            $item1 = $OrderGoods->where('order_id',$data['order_id'])->select();
            foreach ($item1 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                        'id'    =>$value['item_id'],
                        'title' =>$value['subtitle'],
                        'num' =>$value['num'],
                        'cost_price' =>$value['all_oprice'],
                        'price' =>$value['real_price'],
                        'all_price' =>$value['real_price']*$value['num'],
                        'status'    =>$value['status'],
                        'is_service_goods'  =>0,
                        'refund'    =>$refund
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
                        'title' =>$value['service_name'],
                        'num' =>$value['num'],
                        'cost_price' =>0,
                        'price' =>$value['real_price'],
                        'all_price' =>$value['real_price']*$value['num'],
                        'status'    =>$value['status'],
                        'is_service_goods'  =>1,
                        'refund'    =>$refund
                    );
                array_push($item,$arr);
            }
        }
        if( $order_type == 7 ){
            $item1 = $OrderGoods->where('order_id',$data['order_id'])->select();
            $item2 = Db::name('service_goods')->where('order_id',$data['order_id'])->select();
            foreach ($item1 as $key => $value) {
                if( empty($value['refund']) ){
                    $refund = 0;
                }else{
                    $refund = $value['refund'];
                }
                $arr = array(
                        'id'    =>$value['item_id'],
                        'title' =>$value['subtitle'],
                        'num' =>$value['num'],
                        'cost_price' =>$value['all_oprice'],
                        'price' =>$value['real_price'],
                        'all_price' =>$value['real_price']*$value['num'],
                        'status'    =>$value['status'],
                        'is_service_goods'  =>0,
                        'refund'    =>$refund
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
                        'title' =>$value['service_name'],
                        'num' =>$value['num'],
                        'cost_price' =>0,
                        'price' =>$value['real_price'],
                        'all_price' =>$value['real_price']*$value['num'],
                        'status'    =>$value['status'],
                        'is_service_goods'  =>1,
                        'refund'    =>$refund
                    );
                array_push($item,$arr);
            }
        }
        $count = count($item);
    	return json(['code'=>'200','msg'=>'查询成功','count'=>$count,'data'=>$item]);
    }
    public function refund_order(){
        $res  = $this->request->post();
        $user = $this->getUserInfo();
        $data = $res['data'];
        if(empty($res['order_id'])){
            return json(['code'=>'-3','msg'=>'请传入订单id','data'=>'']);
        }
        $order = Db::name("order")->where("id",intval($res['order_id']))->where("shop_id",$user['shop_id'])->find();
        if( $order['pay_way'] == 12 && $res['type'] !== 'card' ){
            return json(['code'=>"-3","msg"=>"超级汇买订单只能使用银行卡退货/退款","data"=>""]);
        }
        if(!$order){
            return json(['code'=>'-3','msg'=>'订单不存在','data'=>'']);
        }
        if($order['order_status'] ==-6){
            return json(['code'=>'-3','msg'=>'订单无法退单','data'=>'']);
        }
        if(!isset($res['type']) || empty($res['type'])){
            return json(['code'=>"-3","msg"=>"支付方式","data"=>""]);
        }
        if($order['member_id'] ==0 && $res['type'] ==="balance"){
            return json(['code'=>"-3","msg"=>"不是会员，退款方式错误","data"=>""]);
        }
        if($order['pay_way'] == 13 && $res['type'] !="balance")
        {
            return json(['code'=>"-3","msg"=>"限时余额够买订单只能使用余额退货/退款","data"=>""]);
        }
        $refund =[
            "order_id"=>$order['id'],
            "o_sn" => $order['sn'],
            "r_sn" => "OR".time().$order['shop_id'],//退款单号设定规则  O=>order + R=>refund + 时间戳 + 门店id;
            "r_status"=>0,
            "reason" =>!isset($res['reason'])?"":$res['reason'],
            "remarks" =>!isset($res['remarks'])?"":$res['remarks'],
            "type"=>1,
            "dealwith_time"=>0,
            "shop_id"=>$user['shop_id'],
            "worker"=>$user['name'],
            "worker_id"=>$user['id'],
            "otype"=>$res['type']=="cash"?1:($res['type'] =="balance"?2:3),
            "r_type"=>$order['pay_way'],
            "status"=>0,
            "create_time"=>time(),
        ];
        $order_db  = db::name("order_refund");
        $money = 0;
        $number = 0;
        $order_db->startTrans();
        try{
            $id = $order_db->insertGetId($refund);
            $new_array = [];
            foreach($data as $key => $val){
                if($val['is_service_goods'] =="0"){
                    $refund_goods = Db::name('order_goods')->where("id",intval($val['refund_id']))->find();
                    if($val['refund_num'] >($refund_goods['num'] - $refund_goods['refund'])){
                        $order_db->rollback();
                        return json(['code'=>"-3","msg"=>$refund_goods['subtitle'].":退货数量不足","data"=>""]);
                    }
                    if($val['refund_price']>$refund_goods['real_price']){
                        $order_db->rollback();
                        return json(['code'=>"-3","msg"=>$refund_goods['subtitle'].":退货金额错误","data"=>""]);
                    }
                    $order_refund_goods = [
                        "refund_id"=>$id,
                        "og_id"=>$refund_goods['id'],
                        "s_id"=>$refund_goods['item_id'],
                        "r_subtitle"=>$refund_goods['subtitle'],
                        "r_num"=>$val['refund_num'],
                        "r_price"=>$val['refund_price'],
                        'is_service_goods' =>$val['is_service_goods'],
                        /*"r_attr_pic"=>
                        "r_attr_name"=>*/
                    ];
                }else if($val['is_service_goods'] =="1"){
                    $refund_goods = Db::name('service_goods')->where("id",$val['refund_id'])->find();
                    if($val['refund_num'] >($refund_goods['num'] - $refund_goods['refund'])){
                        $order_db->rollback();
                        return json(['code'=>"-3","msg"=>$refund_goods['subtitle'].":退货数量不足","data"=>""]);
                    }
                    if($val['refund_price']>$refund_goods['real_price']){
                        $order_db->rollback();
                        return json(['code'=>"-3","msg"=>$refund_goods['subtitle'].":退货金额错误","data"=>""]);
                    }
                    $order_refund_goods = [
                        "refund_id"=>$id,
                        "og_id"=>$refund_goods['id'],
                        "s_id"=>$refund_goods['service_id'],
                        "r_subtitle"=>$refund_goods['service_name'],
                        "r_num"=>$val['refund_num'],
                        "r_price"=>$val['refund_price'],
                        'is_service_goods' =>$val['is_service_goods'],
                        /*"r_attr_pic"=>
                        "r_attr_name"=>*/
                    ];
                }
                array_push($new_array,$order_refund_goods);
                $r_money = $val['refund_num'] * $val['refund_price'];
                $money += number_format($r_money,2,".","");
                $number +=  $val['refund_num'];
            }
//             dump($new_array);die;
            $tt = db::name("order_refund_goods")->insertAll($new_array);
            db::name("order_refund")->where("id",$id)->update(['r_number'=>$number,"r_amount"=>$money]);
            DB::name("order")->where("id",$order['id'])->setInc("refund_num");
            $order_db->commit();
            return json(['code'=>"200","msg"=>"申请成功","data"=>""]);
        }catch(\Exception $e){
            $error = $e->getMessage();
            $order_db->rollback();
            return json(['code'=>"500","msg"=>"服务器内部错误","data"=>$error]);
        }
    }
    //服务卡退单
    public function ticketrefund(){        
        $res = $this->request->post();
        $user_id = $this->getUserInfo()['shop_id'];
        $res['shop_id'] = $user_id;
        $order = new RefundModel();
        $data = $order->ticketrefund($res);   
        return $data;    
    }
    // 服务卡--退单接口
    public function ticket_refund(){
        $res = $this->request->post();
        $user_id = $this->getUserInfo();
        $res['user'] = $user_id;
        $order = new RefundModel();
        $data = $order ->orderticketrefund($res);
        return $data;
    }
    public function ticket_list(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        if(empty($shop_id)){
            return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
        }
        $limit= $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',0);
        $where = [];
        if(isset($res['waiter_id']) && !empty($res['waiter_id'])){
            $where[] = ['o.waiter_id',"=",intval($res['waiter_id'])];
        }
        if(isset($res['pay_way']) && !empty($res['pay_way'])){
            $where[] = ['o.pay_way',"=",intval($res['pay_way'])];
        }
        if(isset($res['status']) && !empty($res['status'])){
            if(intval($res['status']) ==0){
                $where[] = ["a.status","neq",4];
            }else if(intval($res['status'])==1){
                $where[] = ["a.status","eq",4];
            }
        }
        if(isset($res['search']) && !empty($res['search'])){
            $where[] = ['o.sn|a.mobile',"like","%{$res['search']}%"];
        }
        if(isset($res['start_time']) && !empty($res['start_time'])){
            $start_time = strtotime($res['start_time']);
            $where[] = ["o.overtime",">=",$start_time];
        }
        // 结束时间
        if(isset($res['end_time']) && !empty($res['end_time'])){
            $end_time = strtotime($res['end_time']) + 86399;
            $where[] = ['o.overtime',"<=",$end_time];
        }
        //按钮
        if(isset($res['time']) && !empty($res['time'])){
            //今天
            if($res['time'] == "today"){                
                $start_time=mktime(0,0,0,date('m'),date('d'),date('Y'));
                $end_time=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
                $where[] = ["o.overtime",">=",$start_time];
                $where[] = ['o.overtime',"<=",$end_time];
            // 昨天
            }else if($res['time'] == "yesterday"){                
                $start_time=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
                $end_time=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
                $where[] = ["o.overtime",">=",$start_time];
                $where[] = ['o.overtime',"<=",$end_time];
            //本周
            }else if($res['time'] =="week"){
                $start_time=mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
                $end_time=mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));
                $where[] = ["o.overtime",">=",$start_time];
                $where[] = ['o.overtime',"<=",$end_time];
            }
        }
        $where[] =['a.shop_id',"=",$shop_id];
        $where[] =['o.isdel','=',0];
        $data = db::name("ticket_user_pay")
            ->alias("a")
            ->where($where)
            ->field("a.id,o.sn,a.mobile,a.member_id,a.real_price,o.pay_way,o.overtime,o.waiter,o.waiter_id,a.status,o.refund_num,a.order_id")
            ->withAttr("refund",function($value,$data){
                if($data['refund_num']==0){
                    if($data['status'] ==0 || $data['status'] ==1){
                        return "退卡";
                    }
                }
                return "";
            })
            ->withAttr("o_status",function($value,$data){
                if($data['status']==4){
                    return "已退卡";
                }
                return "正常";
            })
            ->withAttr("overtime",function($value,$data){
                if($data['overtime']){
                    return date("Y-m-d H:i:s",$data['overtime']);
                }
                return "数据异常";
            })
            ->withAttr("pay_way",function($value,$data){
                return $this->getPayWayAttr($data['pay_way']);
            })
            ->join("order o ","a.order_id = o.id")
            ->order("o.overtime","desc")
            ->page($page,$limit)
            ->select();
        $count = db::name("ticket_user_pay")
            ->alias("a")
            ->where($where)
            ->join("order o ","a.order_id = o.id")
            ->count();
        if($data){
            return json(['code'=>'200','msg'=>'查询成功','count'=>$count,'data'=>$data]);
        }else{
            return json(['code'=>'200','msg'=>'暂无数据','count'=>$count,'data'=>$data]);
        }
    }
    public function getPayWayAttr($value)
    {
        if (empty($value)) {
            return '未支付';
        }
        $status = [
            1 => '微信支付',
            2 => '支付宝支付',
            3 => '余额支付',
            4 => '银行卡支付',
            5 => '现金支付',
            6 => '美团支付',
            7 => '赠送',
            8 => '门店自用',
            9 => '兑换',
            10 => '包月服务',
            11 => '定制疗程',
            12 => '超级汇买',
            13 => '限时余额',
            99 => '异常充值'
        ];
        return $status[$value];
    }
    public function ticket_details(){
        $res = $this->request->post();
        $id = intval($res['id']);
        if($id){
            return json(['code'=>'500',"msg"=>"请输入订单","data"=>""]);
        }

    }
    public function ticket_list_details(){
        $res = $this->request->post();
        $data = db::name("order_refund")->where("order_id",intval($res['id']))
        ->find();
        if(!$data){
            return json(['code'=>"-3","msg"=>"退单不存在","data"=>""]);
        }
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
            return json(['code'=>"200","msg"=>"获取成功","data"=>$data]);
        }else{
            return json(['code'=>"500","msg"=>"系统错误","data"=>'']);
        }
    }
}
