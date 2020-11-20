<?php
/**

服务卡控制器
 */
namespace app\index\controller;
use app\common\model\UtilsModel;
use app\index\model\Ticket\TicketModel;
use app\index\model\log;
use app\index\model\Adminlog;
use Predis\Client;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
class Ticket extends Base{
    //服务卡列表
    public function ticket_list(){
        //根据传递的XX-token获取门店id  $shop_id
        $shop_id = $this->getUserInfo()['shop_id'];
        $res = $this->request->post();
        $where = [];
        $where[] = ['a.status','=',1];
        //默认为次卡  2为月卡  4为年卡
        if(isset($res['search']) && !empty($res['search'])){
            $where[] = ['a.card_name','like', "%{$res['search']}%"];
        }
        if(isset($res['type']) && !empty($res['type'])){
            $where[] =['a.type',"=", $res['type']];
        }else{
            $where[] = ['a.type','=',1];
        }
        if(!isset($res['member_id']) || empty($res['member_id'])){
            return json(['code'=>"-3","msg"=>"请选择会员","data"=>""]);
        }
        $level_id  = Db::name("member")->where("id",intval($res['member_id']))->value("level_id");
        $where[] = ["t.level_id","=",!empty($level_id)?$level_id:1];
        $where[] = ['s.shop_id','=',intval($shop_id)];
        $where[] = ['a.display',"=",0];
        $where[] = ['a.show',"=",1];
        $where[] = ['a.status',"=",1];
        $where[] = ['a.del',"=",0];
        /*$where[] = ['start_time',"=",0];
        $where[] = ['end_time',"=",0];*/
        $whereOr1 = $where;
        $whereOr1[] =   ['start_time',"=",0];
        $whereOr1[] = ['end_time',"=",0];
        $whereor = $where;
        $whereor[] =['start_time',"<=",time()];
        $whereor[] =['end_time',">=",time()];
        $ticket = new TicketModel();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        try{
            $result = $ticket->ticket_list($shop_id)->whereOr([$whereOr1,$whereor])
                ->withAttr("service",function($value,$data){
                    return Db::name("ticket_service")->where("card_id",$data['id'])->field("num,service_name")->select();
                })
                ->withAttr("month",function($value,$data){
                    if($data['type'] ==1){
                        return "";
                    }else if($data['type']==2){
                        return "(".$data['month']."个月)";
                    }else if($data['type']==4){
                        return "(".$data['year']."年)";
                    }
                })
                ->withAttr("end_time",function($value,$data){
                    if($data['end_time'] ==0){
                        return "不限制";
                    }
                    return date("Y-m-d",$data['end_time']);
                })
                ->field("a.id,a.card_name,a.type,a.month,a.year,a.start_time,a.end_time,t.mprice,t.price")
                ->join("ticket_money t","a.id = t.card_id")
                ->page($page,$limit)->select();
            $count = $ticket->ticket_list($shop_id)->whereOr([$whereOr1,$whereor])->join("ticket_money t","a.id = t.card_id")->count();
            if($result){
                return json(['code'=>200,"msg"=>"查询成功","total"=>$count,'data'=>$result]);
            }else{
                return json(['code'=>200,"msg"=>"暂无数据","total"=>$count,'data'=>""]);
            }
        } catch (Exception $e) {
            return json(['code'=>500,"msg"=>"服务器内部错误",'data'=>""]);
        }
    }
    //购买服务卡
    public function buy2(){
        $res = $this->request->post();
        $ticket = new TicketModel();
        // 获取店铺信息
        $shop_id = $this->getUserInfo()['shop_id'];
        $shop_code = $ticket->getShopCode($shop_id);
        // 判断服务卡
        if(!isset($res['card_id']) || empty($res['card_id'])){
            return json(['code'=>'-3','msg'=>'请选择服务卡','data'=>'']);
        }
        // 判断传递的金额
        if(!isset($res['price']) || empty($res['price'])){
            return json(['code'=>'-3','msg'=>'参数错误','data'=>'']);
        }
        //判断传递的支付方式
        if(!isset($res['pay']) || empty($res['pay'])){
            return json(['code'=>'-3','msg'=>'请选择支付方式','data'=>'']);
        }
        //判断服务人员
        $worker = Db::name("shop_worker")->where("id",intval($res['waiter']))->find();
        if($worker['sid'] != $shop_id){
            return json(['code'=>'-3','msg'=>'缺少服务人员或服务人员不是本店人员','data'=>'']);
        }
        //判断会员信息
        $member = db::name('member')->where("id",$res['member_id'])->where("shop_code",$shop_code)->find();
        if(!$member){
            return json(['code'=>"-3","msg"=>"会员信息不存在或不是本店会员",'data'=>""]);
        }
        // 获取服务卡信息
        $ticket_card = Db::name('ticket_card')->where("id",intval($res['card_id']))->find();
        //判断商品能否赠送
        if($res['pay']==7 && $ticket_card['give']==0){
            return json(['code'=>"400","msg"=>"暂不支持赠送",'data'=>""]);
        }
        //判断商品剩余数量
        if($ticket_card['exchange_num'] == 0 && $ticket_card['critulation'] !=0){
            return json(['code'=>"400","msg"=>"剩余数量不足",'data'=>""]);
        }
        //判断用户购买数量
        $num = Db::name("ticket_user_pay")->where("ticket_id",$res['card_id'])->where("member_id",$member['id'])->count();
        if($num == $ticket_card['restrict_num'] && $ticket_card['restrict_num'] != 0){
            return json(['code'=>"400","msg"=>"购买数量已超出限制",'data'=>""]);
        }
        //获取会员对应价格
        $amount = Db::name('ticket_money')->where("card_id",intval($res['card_id']))->find();
        $price= Db::name('ticket_money')->where("card_id",intval($res['card_id']))->where("level_id",$member['level_id'])->find();
        if(!$price){
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }
        //判断实际付款金额是否超出范围
        if($res['price'] <$price['mprice']){
            return json(['code'=>"400","msg"=>"超出金额范围",'data'=>""]);
        }
        if($res['pay']==3){
            $money  = Db::name("member_money")->where("member_id",$member['id'])->value("money");
            if($money <$res['price']){
                return json(['code'=>"-200","msg"=>"用户余额不足","data"=>""]);
            }
        }
        $data = db::name("ticket_card")->where("id",intval($res['card_id']))->find();
        if(($data['start_time'] >time()) && ($data['start_time'] !==0)){
            return json(['code'=>"400","msg"=>"未开始售卖","data"=>""]);
        }
        if(($data['end_time'] <time()) && ($data['end_time'] !==0)){
            return json(['code'=>"400","msg"=>"已超出结束时间","data"=>""]);
        }
        if($data['status'] ==0){
            return json(['code'=>"400","msg"=>"对不起，该商品已下架","data"=>""]);
        }
        // 生产订单
        $order = [
            "shop_id" =>$shop_id,
            "member_id" =>$member['id'],
            "sn"=>'XM'.time().$shop_id,
            "type"=>5,
            "pay_sn"=>"",
            "ticket_id"=>$res['card_id'],
            "number" =>1,
            "discount"=>$amount['price']-$res['price'],
            "amount"=>$res['price'],
            "pay_status"=>1,
            "send_way"=>1,
            "pay_way"=>$res['pay'],
            "paytime"=>time(),
            "sendtime"=>time(),
            "overtime"=>time(),
            "dealwithtime"=>time(),
            "order_status"=>2,
            "add_time"=>time(),
            "is_online"=>0,
            "waiter"=>$worker['name'],
            "waiter_id"=>$worker['id'],
            "order_type"=>1,
            "old_amount"=>$amount['price'],
            "order_triage"=>1,
            "remarks"=>$res['remarks'],
        ];
        // 购买记录
        $ticket =[
            "shop_id"=>$shop_id,
            "member_id"=>$member['id'],
            "mobile"=>$member['mobile'],
            "ticket_id"=>$res['card_id'],
            "status"=>0,
            "price" =>$amount['price'],
            "real_price"=>$res['price'],
            "start_time"=>0,
            "end_time"=>0,
            "create_time"=>time(),
            "waiter"=>$worker['name'],
            "waiter_id"=>$worker['id'],
            "over_time"=>$data["day"] ==0?0:strtotime('+'.$data["day"].'day'),
            "type" =>$data['type'],
            "month"=>$data['month'],
            "year" =>$data['year'],
            "day"=>$data['use_day'],
            "level_id"=>$member['level_id'],
        ];
        $pay_statistics = [
            "shop_id"=>$shop_id,
            "order_sn"=>$order['sn'],
            "type"=>4,
            "data_type"=>1,
            "pay_way"=>$res['pay'],
            "price"=>$res['price'],
            "create_time"=>time(),
            "title"=>"购买服务卡",
        ];
        //用户明细
        $details = [
            "member_id"=>$member['id'],
            "mobile"=>$member['mobile'],
            "remarks"=>'',
            "reason"=>'购买服务卡花费'.$res['price']."元",
            "addtime"=>time(),
            "amount" => $res['price'],
        ];
        $db_order = db::name("order");
        $msgdata = "";
        $db_order->startTrans();
        try{
            //如果支付方式是余额支付 进行扣款
            if($res['pay']==3){
                $money =$money- $res['price'];
                if($money<0){
                    $db_order->rollback();
                }
                db::name("member_money")->where("member_id",$member['id'])->update(["money"=>$money]);
                $details['reason'] ="购买服务卡消耗余额".$res['price']."元";
                $msgdata = '会员:'.$member['mobile'].",消耗余额:".$res['price']."元".",剩余：".$money."元";
            }
            if($data['critulation'] !==0){
                db::name("ticket_card")->where("id",intval($res['card_id']))->setDec("exchange_num",1);
                $display = db::name("ticket_card")->where("id",intval($res['card_id']))->find();
                if($display['exchange_num']==0){
                    db::name("ticket_card")->where("id",intval($res['card_id']))->update(['display'=>1]);
                }
            }
            $r_order = $db_order->insertGetId($order);
            if(!$r_order){
                $db_order->rollback();
            }
            $ticket['order_id'] = $r_order;
            $details['order_id'] = $r_order;
            $pay_statistics['order_id'] = $r_order;
            if( $res['pay'] == 3 ){
                Db::name("statistics_log")->insert($pay_statistics);
            }else{
                $pay_statistics['type'] = 3;
                Db::name("statistics_log")->insert($pay_statistics);
                $pay_statistics['type'] = 5;
                Db::name("statistics_log")->insert($pay_statistics);
            }
            Db::name("ticket_user_pay")->insert($ticket);
            $result = true;
            if($res['pay'] == 3){
                $result = Db::name("member_details")->insert($details);
            }
            $db_order->commit();
        } catch(\Exception $e){
            $error = $e->getMessage();
            echo $error;
            $db_order->rollback();
        }
        if($result){
            $log = new Adminlog();
            $log->record_insert($member['nickname']."购买了'".$data["card_name"]."'",0,"",$worker['id']);
            return json(['code'=>"200","msg"=>"购买成功","data"=>$msgdata]);
        }else{
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>$error.'错误代码']);
        }
    }
    public function buy(){

        $res = $this->request->post();
        //判断传递的支付方式
        if(!isset($res['pay']) || empty($res['pay'])){
            return json(['code'=>'-3','msg'=>'请选择支付方式','data'=>'']);
        }

        $order_sn = $res['order_sn'];
        if(!isset($res['order_sn']) || empty($res['order_sn'])){//订单号前后给我的订单号
            return json(['code'=>'-3','msg'=>'下单超时，请刷新浏览器后再提交！','data'=>'']);
        }

//        $data = [
//            'shop_id'=>$shop_id,
//            'memberId'=>$member['id'],
//            'card_id'=>$card_id,
//            'waiter'=>$worker['name'],
//            'waiter_id'=>$worker['id'],
//        ];

        require_once APP_PATH . '/../vendor/predis/predis/autoload.php';
        $client     = new Client();
        $client->auth('ddxm661_admin');
        // $key = $order_sn."submit";

        // if( empty($client->get($key))){
        //     $client->set($key,'100');
        //     $client->EXPIRE($key,3);

        // }else{
        //     return json(['code'=>'-3','msg'=>'您操作太频繁，请稍后再试！','data'=>'']);
        // }

        $data = $client->get($order_sn);
        if(empty($data))
        {
        	return json(['code'=>'-3','msg'=>'您操作太频繁，请稍后再试！','data'=>'']);
        }
        //如‘排它锁优化’还存在问题，后面再启用秘钥验证
        //....................验证逻辑

        //启用文件排它锁，防止门店重复提交产生多个订单问题
        $file = fopen('lock.txt','w+');
        //执行加锁
        if(flock($file,LOCK_EX|LOCK_NB))
        {
        	//return json(['code'=>'-3','msg'=>'下单超时，请刷新浏览器后再提交！','data'=>$data]);
	        if(empty($data)){//订单号前后给我的订单号
	            return json(['code'=>'-3','msg'=>'下单超时，请刷新浏览器后再提交！','data'=>'']);
	        }

	        $oo = db::name("order")->where('sn',$order_sn)->find();
	        if($oo){
	            return json(['code'=>'-3','msg'=>'下单失败，您已经购买，请勿重复购买了！','data'=>'']);
	        }

	        $data    = json_decode($data,true);

	        $shop_id = $data['shop_id'];
	        $memberId = $data['memberId'];
	        $card_id = $data['card_id'];
	        $waiter = $data['waiter'];
	        $waiter_id = $data['waiter_id'];
	        $level_id = $data['level_id'];
	        $mobile = $data['mobile'];
	        $nickname = $data['nickname'];
	        $price = $data['price'];

	        $res['price'] = $price;

	        // 获取服务卡信息
	        $ticket_card = Db::name('ticket_card')->where("id",$card_id)->find();
	        //判断商品能否赠送
	        if($res['pay']==7 && $ticket_card['give']==0){
	            return json(['code'=>"400","msg"=>"暂不支持赠送",'data'=>""]);
	        }
	        //判断商品剩余数量
	        if($ticket_card['exchange_num'] == 0 && $ticket_card['critulation'] !=0){
	            return json(['code'=>"400","msg"=>"剩余数量不足",'data'=>""]);
	        }
	        //判断用户购买数量
	        $num = Db::name("ticket_user_pay")->where("ticket_id",$res['card_id'])->where("member_id",$memberId)->count();
	        if($num == $ticket_card['restrict_num'] && $ticket_card['restrict_num'] != 0){
	            return json(['code'=>"400","msg"=>"购买数量已超出限制",'data'=>""]);
	        }
	        //获取会员对应价格
	        $amount = Db::name('ticket_money')->where("card_id",$card_id)->find();
	        $price= Db::name('ticket_money')->where("card_id",$card_id)->where("level_id",$level_id)->find();
	        if(!$price){
	            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
	        }
	        //判断实际付款金额是否超出范围
	        if($res['price'] <$price['mprice']){
	            return json(['code'=>"400","msg"=>"超出金额范围",'data'=>""]);
	        }
	        if($res['pay']==3){
	            $money  = Db::name("member_money")->where("member_id",$memberId)->value("money");

	            $xs = $this->getLimitedPrice1($memberId);
                $mon = $money -$xs;
                if( bccomp( $mon,$res['price'] ) == -1 ){
                    return json(['code'=>"-200","msg"=>"用户余额不足2","data"=>""]);
                }
	        }
	        $data = db::name("ticket_card")->where("id",intval($card_id))->find();
	        if(($data['start_time'] >time()) && ($data['start_time'] !==0)){
	            return json(['code'=>"400","msg"=>"未开始售卖","data"=>""]);
	        }
	        if(($data['end_time'] <time()) && ($data['end_time'] !==0)){
	            return json(['code'=>"400","msg"=>"已超出结束时间","data"=>""]);
	        }
	        if($data['status'] ==0){
	            return json(['code'=>"400","msg"=>"对不起，该商品已下架","data"=>""]);
	        }
	        // 生产订单
	        $order = [
	            "shop_id" =>$shop_id,
	            "member_id" =>$memberId,
	            "sn"=>$order_sn,
	            "type"=>5,
	            "pay_sn"=>"",
	            "ticket_id"=>$card_id,
	            "number" =>1,
	            "discount"=>$amount['price']-$res['price'],
	            "amount"=>$res['price'],
	            "pay_status"=>1,
	            "send_way"=>1,
	            "pay_way"=>$res['pay'],
	            "paytime"=>time(),
	            "sendtime"=>time(),
	            "overtime"=>time(),
	            "dealwithtime"=>time(),
	            "order_status"=>2,
	            "add_time"=>time(),
	            "is_online"=>0,
	            "waiter"=>$waiter,
	            "waiter_id"=>$waiter_id,
	            "order_type"=>1,
	            "old_amount"=>$amount['price'],
	            "order_triage"=>1,
	            "remarks"=>$res['remarks'],
	        ];
	        // 购买记录
	        $ticket =[
	            "shop_id"=>$shop_id,
	            "member_id"=>$memberId,
	            "mobile"=>$mobile,
	            "ticket_id"=>$card_id,
	            "status"=>0,
	            "price" =>$amount['price'],
	            "real_price"=>$res['price'],
	            "start_time"=>0,
	            "end_time"=>0,
	            "create_time"=>time(),
	            "waiter"=>$waiter,
	            "waiter_id"=>$waiter_id,
	            "over_time"=>$data["day"] ==0?0:strtotime('+'.$data["day"].'day'),
	            "type" =>$data['type'],
	            "month"=>$data['month'],
	            "year" =>$data['year'],
	            "day"=>$data['use_day'],
	            "level_id"=>$level_id,
	        ];
	        $pay_statistics = [
	            "shop_id"=>$shop_id,
	            "order_sn"=>$order['sn'],
	            "type"=>4,
	            "data_type"=>1,
	            "pay_way"=>$res['pay'],
	            "price"=>$res['price'],
	            "create_time"=>time(),
	            "title"=>"购买服务卡",
	        ];
	        //用户明细
	        $details = [
	            "member_id"=>$memberId,
	            "mobile"=>$mobile,
	            "remarks"=>'',
	            "reason"=>'购买服务卡花费'.$res['price']."元",
	            "addtime"=>time(),
	            "amount" => $res['price'],
	        ];
	        $db_order = db::name("order");
	        $msgdata = "";
	        $db_order->startTrans();
	        try{
	            //如果支付方式是余额支付 进行扣款
	            if($res['pay']==3){
	                $money =$money- $res['price'];
	                if($money<0){
	                    $db_order->rollback();
	                }
	                $res = db::name("member_money")->where("member_id",$memberId)->update(["money"=>$money]);
	                if( !$res ) {
                        $db_order->rollback();
                    }
	                $details['reason'] ="购买服务卡消耗余额".$res['price']."元";
	                $msgdata = '会员:'.$mobile.",消耗余额:".$res['price']."元".",剩余：".$money."元";
	            }
	            if($data['critulation'] !==0){
	                db::name("ticket_card")->where("id",intval($card_id))->setDec("exchange_num",1);
	                $display = db::name("ticket_card")->where("id",intval($card_id))->find();
	                if($display['exchange_num']==0){
	                    db::name("ticket_card")->where("id",intval($card_id))->update(['display'=>1]);
	                }
	            }
	            $r_order = $db_order->insertGetId($order);
	            if(!$r_order){
	                $db_order->rollback();
	            }
	            $ticket['order_id'] = $r_order;
	            $details['order_id'] = $r_order;
	            $pay_statistics['order_id'] = $r_order;
                Db::name("statistics_log")->insert($pay_statistics);
                // if( $res['pay'] == 3 ){
                //     Db::name("statistics_log")->insert($pay_statistics);
                // }else{
                //     $pay_statistics['type'] = 3;
                //     Db::name("statistics_log")->insert($pay_statistics);
                //     $pay_statistics['type'] = 5;
                //     Db::name("statistics_log")->insert($pay_statistics);
                // }
	            Db::name("ticket_user_pay")->insert($ticket);
	            $result = true;
	            if($res['pay'] == 3){
	                $result = Db::name("member_details")->insert($details);
	            }
	            $db_order->commit();
	        } catch(\Exception $e){

	        	//关闭文件（自动解锁）
        		fclose($file);
	            $error = $e->getMessage();
	            echo $error;
	            $db_order->rollback();
	        }
	        if($result){
	            $log = new Adminlog();
	            $log->record_insert($nickname."购买了'".$data["card_name"]."'",0,"",$waiter_id);

	            //成功了清楚Redis缓存
	            $client->del($order_sn);
	            //打开文件锁
	            flock($file,LOCK_UN);
	            return json(['code'=>"200","msg"=>"购买成功","data"=>$msgdata]);
	        }else{
	            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>$error.'错误代码']);
	        }

        }else{
        	return json(['code'=>'-3','msg'=>'您操作太频繁，请稍后再试！','data'=>'']);
        }
        
        //关闭文件（自动解锁）
        fclose($file);
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

    //购买服务卡----提交订单
    public function buySubmit(){
        $res = $this->request->post();
        $ticket = new TicketModel();
        // 获取店铺信息
        $shop_id = $this->getUserInfo()['shop_id'];
        $shop_code = $ticket->getShopCode($shop_id);
        // 判断服务卡
        if(!isset($res['card_id']) || empty($res['card_id'])){
            return json(['code'=>'-3','msg'=>'请选择服务卡','data'=>'']);
        }
        // 判断传递的金额
        if(!isset($res['price']) || empty($res['price'])){
            return json(['code'=>'-3','msg'=>'参数错误','data'=>'']);
        }

        //判断服务人员
        $worker = Db::name("shop_worker")->where("id",intval($res['waiter']))->find();
        if($worker['sid'] != $shop_id){
            return json(['code'=>'-3','msg'=>'缺少服务人员或服务人员不是本店人员','data'=>'']);
        }
        //判断会员信息
        $member = db::name('member')->where("id",$res['member_id'])->where("shop_code",$shop_code)->find();
        if(!$member){
            return json(['code'=>"-3","msg"=>"会员信息不存在或不是本店会员",'data'=>""]);
        }

        $card_id =  $res['card_id'];

        //获取会员对应价格
//        $amount = Db::name('ticket_money')->where("card_id",intval($res['card_id']))->find();
        $price= Db::name('ticket_money')->where("card_id",intval($res['card_id']))->where("level_id",$member['level_id'])->find();
        if(!$price){
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }
        //判断实际付款金额是否超出范围
        if($res['price'] <$price['mprice']){
            return json(['code'=>"400","msg"=>"超出金额范围",'data'=>""]);
        }

        $data = db::name("ticket_card")->where("id",intval($res['card_id']))->find();
        if(($data['start_time'] >time()) && ($data['start_time'] !==0)){
            return json(['code'=>"400","msg"=>"未开始售卖","data"=>""]);
        }
        if(($data['end_time'] <time()) && ($data['end_time'] !==0)){
            return json(['code'=>"400","msg"=>"已超出结束时间","data"=>""]);
        }
        if($data['status'] ==0){
            return json(['code'=>"400","msg"=>"对不起，该商品已下架","data"=>""]);
        }

        require_once APP_PATH . '/../vendor/predis/predis/autoload.php';
        $client     = new Client();
        $client->auth('ddxm661_admin');
		
        //判断对应的 库存是否存在
//        $order_datas    = $client->get($newIds);
        //订单号
        $sn = 'XM'.time().$shop_id;

        $data = [
            'shop_id'=>$shop_id,
            'memberId'=>$member['id'],
            'card_id'=>$card_id,
            'waiter'=>$worker['name'],
            'waiter_id'=>$worker['id'],
            'level_id'=>$member['level_id'],
            'mobile'=>$member['mobile'],
            'nickname'=>$member['nickname'],
            'price'=> $res['price'],
        ];

        $res = $client->set($sn,json_encode($data));
        $exp = $client->EXPIRE($sn,3600);

        return json(['code'=>"200","msg"=>"下单成功","data"=>$sn]);
    }
    //激活服务卡
    public function active(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        // 判断卡卷id是否存在
        if(!isset($res['card_id']) || empty($res['card_id'])){
            return json(['code'=>"-3",'msg'=>"请选择服务卡",'data'=>""]);
        }
        // 判断激活的服务员
        if(!isset($res['waiter']) || empty($res['waiter'])){
            return json(['code'=>"-3","msg"=>"请选择服务员","data"=>""]);
        }
        $waiter = db::name("shop_worker")->where("id",intval($res['waiter']))->find();
        // 判断激活的服务员
        if(!$waiter){
            return json(['code'=>"-3","msg"=>"服务员数据错误","data"=>""]);
        }
        $db_ticket = db::name("ticket_user_pay");
        $data =$db_ticket->where("id",intval($res['card_id']))->find();
        $ticket_card = Db::name("ticket_card")->where("id",$data['ticket_id'])->find();
        if($data['status'] ==1){
            return json(['msg'=>"-200","msg"=>"该卡已激活",'data'=>""]);
        }
        $member = db::name("member")->where("id",$data['member_id'])->find();
        // 构建更改购买表单数据
        $ticket =[
            'start_time'=>time(),
            "over_time"=>0,
            "end_time"=>$this->getOverTime($ticket_card),
            "status" =>1,
        ];
        $order = db::name("order")->where("id",$data['order_id'])->find();
        //股东数据
//        $statistics = [
//            "order_id" => $data['order_id'],
//            "shop_id"  => $data["shop_id"],
//            "order_sn" => $order["sn"],
//            "type"=>4,
//            "data_type"=> 1,
//            "pay_way"  => $order['pay_way'],
//            "price"    => $order['amount'],
//            "create_time"=>time(),
//            "title"    => "购买服务卡",
//        ];
        // 开启事务
        $db_ticket->startTrans();
        $result = db::name("ticket_user_pay")->where("id",intval($res['card_id']))->update($ticket);
        try{
            $service = Db::name("ticket_service")->where("card_id",$data['ticket_id'])->select();
            foreach($service as  $key=>$value){
                $other = Db::name("ticket_other_restrictions")->where("ticket_service_id",$value['id'])->select();
                $servicemoney = DB::name("ticket_service_money")->where("ts_id",$value['id'])->where("level_id",$member['level_id'])->value("price");
                $use=[
                    "ticket_id"=>$data['id'],
                    "service_id"=>$value['service_id'],
                    "r_num"=>0,
                    "num"=>$value['num'],
                    "s_num"=>$value['num'],
                    "start_year"=>time(),
                    "start_month"=>time(),
                    "start_day"=>time(),
                    "end_year"=>strtotime(date("Y-m-d",strtotime("+1 year"))." +1 day") -1,
                    "end_month"=>strtotime(date("Y-m-d",strtotime("+1 month"))." +1 day") -1,
                    "end_day"=>strtotime(date("Y-m-d",strtotime("+1 day"))." +1 day") -1,
                    "r_year"=>$value['year'],
                    "r_month"=>$value["month"],
                    "r_day"=>$value['day'],
                    "year_num"=>0,
                    "month_num"=>0,
                    "day_num"=>0,
//                    "money"=>db::name("ticket_service_money")->where("ts_id",$value['id'])->where("level_id",$data['level_id'])->value("price"),
                    "money"=>$data['real_price'],
                ];
                $r_user = Db::name("ticket_use")->insertGetId($use);
                if(!$r_user){
                    $db_ticket->rollback();
                }
                foreach($other as $k=>$v){
                    $u_other=[
                        "start_time"=>$v['start_time'],
                        "end_time"=>$v['end_time'],
                        "num" =>$v['num'],
                        "servie_id"=>$r_user,
                    ];
                    db::name("ticket_use_other")->insert($u_other);
                }
            }
            if($ticket_card['type'] !=="1"){
                $log = new Adminlog();
//                Db::name("statistics_log")->insert($statistics);
                $log->record_insert($waiter['name']."激活了id为'".$data["id"]."'的服务卡",0,"",$waiter['id']);
            }
            $db_ticket->commit();
        }catch(\Exception $e){
            $error = $e->getMessage();
            // echo $error;
            $db_ticket->rollback();
        }
        if($result){

            return json(['code'=>"200","msg"=>"激活成功","data"=>""]);
        }else{
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }
    }
    // 获取会员卡到期时间
    public function getOverTime($res){
        // 服务卡类型为1时  次卡 选择天数
        if($res['type'] == "1"){
            if($res['use_day']==0){
                $time = 0;
            }else{
                $time = strtotime('+'.$res["use_day"].'day');
                $time =strtotime(date("Y-m-d",$time)." +1 day") -1;
            }
            // 服务卡类型为2时  月卡 选择月数
        }else if($res['type'] == "2"){
            $time = strtotime('+'.$res["month"].'month');
            $time =strtotime(date("Y-m-d",$time)." +1 day") -1;
            // 服务卡类型为4时  年卡 选择年数
        }else if($res['type'] == "4"){
            $time = strtotime('+'.$res["year"].'year');
            $time =strtotime(date("Y-m-d",$time)." +1 day") -1;
        }
        return $time;
    }
    //会员已购服务卡列表
    public function ticket_buy_list(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        if(!isset($res["member_id"]) || empty($res['member_id'])){
            return json(['code'=>"-3",'msg'=>"请选择会员","data"=>""]);
        }
        $where = [];
        if( isset($res['is_online']) && ($res['is_online'] != '') ){
            $where[] = ['a.is_online','eq',$res['is_online']];
            if( $res['is_online'] == 1 ){
                $where[] = ['a.status','neq',0];
            }
        }else{
            $where[] = ['a.is_online','eq',0];
        }
        $where[] = ['a.member_id','eq',intval($res['member_id'])];
        $where[] = ['a.is_delete','eq',0];
        $ticket = db::name("ticket_user_pay")
            ->alias("a")
            ->where($where)
            ->join("ddxm_ticket_card c","a.ticket_id = c.id")
            ->field("a.id,c.card_name,c.type,a.real_price,c.month,c.year,a.create_time,a.start_time,a.end_time,a.over_time,a.status")
            ->withAttr("type_card",function($value,$data){
                if($data['type']==1){
                    return "次卡";
                }
                if($data['type']==2){
                    return "月卡/".$data['month']."个月";
                }
                if($data['type']==4){
                    return "年卡/".$data['year']."年";
                }
            })
            ->withAttr("create_time",function($value,$data){
                return date("Y-m-d H:i:s",$data['create_time']);
            })
            ->withAttr("start_time",function($value,$data){
                if($data['start_time']==0){
                    return "未激活";
                }else{
                    return date("Y-m-d H:i:s",$data['start_time']);
                }
            })
            ->withAttr("end_time",function($value,$data){
                if($data['end_time']==0){
                    if($data['over_time'] == 0){
                        return "无限制";
                    }else{
                        return date("Y-m-d H:i:s",$data['over_time']);
                    }
                }else{
                    return date("Y-m-d H:i:s",$data['end_time']);
                }
            })
            ->withAttr("status_name",function($value,$data){
                if($data['status']==0){
                    return "未激活";
                }else if($data['status']==1){
                    return "待使用";
                }else if($data['status']==2){
                    return "已使用";
                }else if($data['status']==3){
                    return "已过期";
                }else if($data['status']==4){
                    return "已退卡";
                }
            })
            ->order("a.create_time","desc")
            ->select();
        $count = db::name("ticket_user_pay")->alias('a')->where($where)->count();
        if($ticket){
            return json(['code'=>"200","total"=>$count,"data"=>$ticket]);
        }else{
            return json(['code'=>"200","msg"=>"暂未购买",'data'=>""]);
        }
    }
    //服务卡使用记录
    public function ticket_records(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        if(!isset($res["member_id"]) || empty($res['member_id'])){
            return json(['code'=>"-3","msg"=>"请输入会员信息","data"=>""]);
        }
        if(!isset($res['ticket_id']) || empty($res['ticket_id'])){
            return json(['code'=>"-3","msg"=>"请输入已购服务卡id",'data'=>""]);
        }
        $result = DB::name("ticket_consumption")
            ->where("member_id",intval($res['member_id']))
            ->where("ticket_id",intval($res['ticket_id']))
            ->where("shop_id",intval($shop_id))
            ->field("service_name,waiter,time")
            ->withAttr("time",function($value,$data){
                return date("Y-m-d H:i:s",$data['time']);
            })
            ->order('id','desc')
            ->select();
        $count =   DB::name("ticket_consumption")
            ->where("member_id",intval($res['member_id']))
            ->where("ticket_id",intval($res['ticket_id']))
            ->where("shop_id",intval($shop_id))->count();
        return json(['code'=>"200","total"=>$count,"data"=>$result]);
    }
    //服务卡耗卡列表
    public function ticket_consume_list(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        if(!isset($res['ticket_id']) || empty($res['ticket_id'])){
            return json(['code'=>'-3',"msg"=>"服务卡参数错误","data"=>""]);
        }
        $result = Db::name("ticket_use")
            ->alias("a")
            ->where("a.ticket_id",intval($res['ticket_id']))
            ->field("s.sname,a.num,a.id,a.r_num,a.s_num,s.status,year_num,start_year,end_year,r_year,month_num,start_month,end_month,r_month,day_num,start_day,end_day,r_day")
            ->join("ddxm_service s","a.service_id = s.id")
            //剩余次数 当num==0时是月卡 年卡
            ->withAttr("s_num",function($value,$data){
                if($data['num'] ==0){
                    return "不限制";
                }
                return $data['s_num'];
            })
            //次数 当num==0时是月卡 年卡
            ->withAttr("num",function($value,$data){
                if($data['num'] ==0){
                    return "不限制";
                }
                return $data['num'];
            })
            ->withAttr("type",function($value,$data){
//                if($data['status'] ==1){//
                //当剩余数量大于0时 次卡 返回立即使用
                if($data['s_num']>0){
                    return "立即使用";
                }
                $state = true;
                //当可用数量为0时  月卡 或者年卡
                if($data['num']=="不限制"){
                    // 当r_year 大于0时  有年限制
                    if($data['r_year']>0){
                        if($data['start_year']<time() && $data['end_year']>time()){
                            if($data['year_num']  == $data['r_year']){
                                return "不可用";
                            }
                        }
                    }
                    // 当r_month 大于0时 有月限制
                    if($data['r_month']>0){
                        if($data['start_month']<time() && $data['end_month']>time()){
                            if($data['month_num']  == $data['r_month']){
                                return "不可用";
                            }
                        }
                    }
                    // 当r_day  大于0时  有天限制
                    if($data['r_day']>0){
                        if($data['start_day']<time() && $data['end_day']>time()){
                            if($data['day_num']  == $data['r_day']){
                                return "不可用";
                            }
                        }
                    }
                    // 查询是否有其他限制
                    $other = Db::name("ticket_use_other")->where("start_time","<",time())->where("end_time",">",time())->select();
                    foreach($other as $k => $v){
                        if($v['num']>0 && ($v['r_num'] ==$v['num'])){
                            return "不可用";
                        }
                    }
//                    }
                    //根据返回的状态 回馈给前端
                    if($state){
                        return "立即使用";
                    }else{
                        return "不可用";
                    }
                }else if($data['status'] == 2){
                    return "无";
                }
            })
            ->select();
        if($result){
            return json(['code'=>"200","msg"=>"查询成功","data"=>$result]);
        }else{
            return json(['code'=>"500","msg"=>"服务器内部错误","data"=>$result]);
        }
    }
    //服务卡耗卡
    public function ticket_consume(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        if(!isset($res['waiter_id']) || empty($res['waiter_id'])){
            return json(['code'=>"-3","msg"=>"请选择服务员","data"=>""]);
        }
        if(!isset($res['service_id']) || empty($res['service_id'])){
            return json(['code'=>'-3','msg'=>"请输入对应服务","data"=>""]);
        }
        $waiter_name = db::name("shop_worker")->where("id",intval($res['waiter_id']))->value('name');
        // 获取该服务卡的服务数据
        $data = db::name("ticket_use")->where("id",intval($res['service_id']))->find();
        $service_name = db::name("service")->where("id",$data['service_id'])->value("sname");
        $ticket_user_pay = db::name("ticket_user_pay")->where("id",$data['ticket_id'])->find();
        $member_id = $ticket_user_pay['member_id'];
        $db_ticket = db::name("ticket_use");
        if(  $ticket_user_pay['end_time'] != 0 && ($ticket_user_pay['end_time']<time())){
            return json(['code'=>'-3','msg'=>"服务卡已过期，请联系管理员","data"=>""]);
        }
        if( $ticket_user_pay['is_online'] == 1 ){
            if( $ticket_user_pay['status'] == 0 ){
                return json(['code'=>'-3','msg'=>"请先领取该服务卡","data"=>""]);
            }
            if( empty( $res['code'] ) ){
                return json(['code'=>'-3','msg'=>"请先查看手机核销码","data"=>""]);
            }
            if( $res['code'] != $ticket_user_pay['code'] ){
                return json(['code'=>'-3','msg'=>"核销码错误","data"=>""]);
            }
        }
        $statistics = [
            "order_id"=>$ticket_user_pay['order_id'],
            "shop_id" =>$ticket_user_pay['shop_id'],
            "order_sn"=>db::name("order")->where("id",$ticket_user_pay['order_id'])->value("sn"),
            "type"=>4,
            "data_type"=>1,
            "create_time"=>time(),
            'title'=>'服务卡耗卡',
        ];
        $state = true;
        $db_ticket->startTrans();
        try{
            //如果有核销码,则清空核销码
            Db::name('ticket_user_pay') ->where('id',$ticket_user_pay['id'])->setField('code','');
            if($data['num']>0){
                $price = $this->getStatisticsMoney($ticket_user_pay,intval($res['service_id']));
                $result = db::name("ticket_use")->where("id",intval($res['service_id']))->setDec("s_num",1);
                db::name("ticket_use")->where("id",intval($res['service_id']))->setInc("r_num",1);
                $total = Db::name("ticket_use")->where("ticket_id",$ticket_user_pay['id'])->sum("s_num");
                if($total ==0){
                    $state = false;
                }
                $statistics['price'] = $price;
//                db::name("statistics_log")->insert($statistics);
            }else{
                $price = 0;
                // 判断有年限制
                if($data['r_year']>0){
                    if($data['start_year']<time() && $data['end_year']>time()){
                        if($data['r_year'] == $data['year_num']){
                            $db_ticket->rollback();
                            return json(['code'=>"-3","msg"=>"超出每年限制","data"=>""]);
                        }
                        $ticket['year_num'] = $data['year_num']+1;
                    }else{
                        $ticket['start_year'] = $ticket['end_year'];
                        $ticket['end_year'] = strtotime("+1 year");
                        $ticket['year_num'] = 1;
                    }
                }
                //判断 月限制
                if($data['r_month']>0){
                    if($data['start_month']<time() && $data['end_month']>time()){
                        if($data['r_month'] == $data['month_num']){
                            $db_ticket->rollback();
                            return json(['code'=>"-3","msg"=>"超出每月限制","data"=>""]);
                        }
                        $ticket['month_num'] = $data['month_num']+1;
                    }else{
                        $ticket['start_month'] =  $ticket['end_month'];
                        $ticket['end_month'] = strtotime("+1 month");
                        $ticket['month_num'] = 1;
                    }
                }
                //判断天限制
                if($data['r_day']>0){
                    if($data['start_day']<time() && $data['end_day']>time()){
                        if($data['r_day'] == $data['day_num']){
                            $db_ticket->rollback();
                            return json(['code'=>"-3","msg"=>"超出每日限制","data"=>""]);
                        }
                        $ticket['day_num'] = $data['day_num']+1;
                    }else{
                        $ticket['start_day'] =strtotime(date('Ymd'));
                        $ticket['end_day'] = $ticket['start_day']+ 86399;
                        $ticket['day_num'] = 1;
                    }
                }
                $ticket['r_num'] = $data['r_num']+1;
                if($ticket){
                    $result = db::name("ticket_use")->where("id",intval($res['service_id']))->update($ticket);
                }else{
                    $result = false;
                }
            }
            if($result){
                $consumption = [
                    'member_id' => $member_id,
                    "shop_id"=>$shop_id,
                    "ticket_id"=>$data['ticket_id'],
                    "service_id"=>$data['service_id'],
                    "ts_id" =>$data['id'],
                    "price"=>$price,
                    "service_name"=>$service_name,
                    "waiter"=>$waiter_name,
                    "waiter_id"=>$res['waiter_id'],
                    "time"=>time(),
                    "num"=>1,
                ];
                if(!$state){
                    db::name("ticket_user_pay")->where("id",$data['ticket_id'])->update(['status'=>2]);
                }
                if(Db::name("ticket_consumption")->insert($consumption)){
                    $db_ticket->commit();
                    return json(['code'=>"200","msg"=>"使用成功","data"=>""]);
                }else{
                    $db_ticket->rollback();
                    return json(['code'=>"500","msg"=>"服务器内部错误","data"=>""]);
                }
            }else{
                $db_ticket->rollback();
                return json(['code'=>"500","msg"=>"服务器内部错误","data"=>""]);
            }
        } catch(\Exception $e){
            $error = $e->getMessage();
            /*echo $error;*/
            $db_ticket->rollback();
            return json(['code'=>"500","msg"=>"服务器内部错误","data"=>$error]);
        }
    }
    public function getStatisticsMoney($res,$id){
        $money = $res['real_price'];
        // 获取当前服务信息
        $ticket = db::name('ticket_use')->where("id",$id)->find();
        $s_num = Db::name("ticket_use")->where("ticket_id",$res['id'])->sum("s_num");
        // 获取已经使用的总金额
        $amount = db::name("ticket_consumption")->where("ticket_id",$res['id'])->sum("price");
        if(!$amount){
            $amount = 0;
        }
        $price  = $ticket['money'] /$ticket['num'];
        $price = number_format($price,2,".","");
        $data = 0;
        if(($money - $amount)>$price){
            if($ticket['s_num'] ==1){
                if($s_num==1){
                    $data  = $money - $amount;
                }else{
                    $data = $ticket['money'] - ($price * $ticket['r_num']);
                }
            }else{
                $data = $price;
            }
        }else if(($money - $amount)==$price){
            $data = 0;
        }else{
            $data = $money - $amount;
        }
        return $data;
        /*if($val['s_num'] ==0){
            $amount += $val['money'];
        }else{

        }
        if($val['id'] == $id){
            $price = $val['money'] / $val['num'];
            $price = number_format($price,2,".","");
            if($val['s_num'] ==1){
                $price = $val['money'] - ($price * $val['r_num']);
            }
            $price = number_format($price,2,".","");
        }*/

    }
    public function member_ticket(){
        $res = $this->request->post();
        if(!isset($res['member_id']) || empty($res['member_id'])){
            return json(['code'=>"-3","msg"=>"未选择会员","data"=>""]);
        }
        if(!isset($res['ticket_id']) || empty($res['ticket_id'])){
            return json(['code'=>"-3","msg"=>"未选择服务卡","data"=>""]);
        }
        $member_id = intval($res['member_id']);
        $ticket_id = intval($res['ticket_id']);
        $level_id = db::name("member")->where("id",$member_id)->value("level_id");
        $data = db::name("ticket_money")->where("level_id",$level_id)->where("card_id",$ticket_id)->field("price,mprice")->find();
        if($data){
            return json(['code'=>"200","msg"=>"获取成功","data"=>$data]);
        }else{
            return json(['code'=>"-3","msg"=>"服务器错误","data"=>""]);
        }
    }
    public function ticket_details(){
        $res = $this->request->post();
        if(!isset($res["ticket_id"]) || empty($res['ticket_id'])){
            return json(['code'=>"-3","msg"=>"参数错误,服务卡不存在",'data'=>""]);
        }
        $data = db::name("ticket_card")->where("id",intval($res['ticket_id']))->find();
        $ticket['over_time'] = $data['day'] ==0?"无限制":$data['day']."天";
        if($data['type'] == "1"){
            $ticket['end_time'] = $data['use_day']==0?"无限制":$data['use_day']."天";
        }else if($data['type'] =="2"){
            $ticket['end_time'] = $data['month']==0?"无限制":$data['month']."月";
        }else if($data['type'] =="4"){
            $ticket['end_time'] = $data['year']==0?"无限制":$data['year']."年";
        }
        $ticket['service'] = db::name("ticket_service")
            ->alias("a")
            ->where("a.card_id",intval($res['ticket_id']))
            ->select();
        if($ticket){
            return json(['code'=>"200","msg"=>"获取成功","data"=>$ticket]);
        }else{
            return json(['code'=>"200","msg"=>"暂无数据","data"=>""]);
        }
    }

    /***
     * 服务卡延期
     */
    public function ticketDelay()
    {
        $data = $this->request->post();
        if ( empty($data['id']) || empty($data['expire_time']) )
        {
            return_error('请传入ID或者过期时间');
        }
        $ticketInfo = Db::name('ticket_user_pay') ->where('id',intval($data['id']))->find();

        if ( !$ticketInfo )
        {
            return_error('服务卡不存在');
        }
        if( ($ticketInfo['status'] == 0) )
        {
            return_error('未激活的服务卡禁止延期');
        }
        //最长延期不能超过两个月
        $end_time = $ticketInfo['end_time'];
        $lastTime1 = strtotime(date('Y-m-d H:i:s',strtotime("+4month",$end_time))); //结束时间的四个月后
        $lastTime2 = strtotime(date('Y-m-d H:i:s',strtotime("+4month",time())));    //当前时间的四个月后
        if ( bccomp($lastTime1,$lastTime2) == -1 )
        {
            $lastTime = $lastTime2;
        }else{
            $lastTime = $lastTime1;
        }
        if ( strtotime($data['expire_time']) > $lastTime )
        {
            return_error('最多只能延期4个月,请将时间控制在：'.date('Y-m-d H:i:s',$lastTime));
        }
        if ( Db::name('ticket_delay') ->where( 'tup_id',$data['id']) ->find() )
        {
            return_error('每张服务卡只能延期一次');
        }
        Db::startTrans();
        try{
            $update = [];
            $update = [
                'end_time'  =>strtotime($data['expire_time'].' 23:59:59'),
                'status'    =>1
            ];
            $res = Db::name('ticket_user_pay')->where('id',intval($data['id']))->update($update);
            if ( $res )
            {
                $arr = [
                    'member_id' =>$ticketInfo['member_id'],
                    'tup_id'    =>$ticketInfo['id'],
                    'old_over_time' =>$ticketInfo['end_time'],
                    'new_over_time' =>strtotime($data['expire_time'].' 23:59:59'),
                    'create_time'   =>time()
                ];
                $res = Db::name('ticket_delay') ->insert($arr);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return_error($e->getMessage());
        }
        return_succ([],'延期成功');
    }
}