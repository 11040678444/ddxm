<?php
/*
	订单模型
*/
namespace app\index\model\Order;
use app\index\model\log;

use think\Model;
use think\Cache;
use think\Db;

class OrderRefund extends Model
{
    protected $table = 'ddxm_order_refund';
    public function order_refund($order_id,$shop_id){
        $id  = $val['order_id'];
        $data = $this->where("order_id",intval($order_id['order_id']))->where("shop_id",$shop_id)
        ->field("id,r_sn,r_number,r_amount,create_time as add_time,dealwith_time,r_status,reason,remarks")
        ->select();
        return $data;

    }
    public function getAddTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }else{
            return "暂无";
        }
    }
    public function getDealwithTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }else{
            return "暂无";
        }
    }
    public function getRStatusAttr($val){
        $status=[
            0=>'申请中',
            1=>'完成',
            2=>'取消退货'
        ];
        return $status[$val];
    }
    // 订单退货处理model  $order_id 传递的order_id 订单id    $shop_id 传递的门店id
    public function ordergoods($order_id,$id){
        $data = db::name("order_goods")
                ->where("order_id",intval($order_id))
                ->field("subtitle,item_id,num,price,real_price,real_price as refund_price,num as refund_num")
                ->select();
        return $data;
    }
   
    // 商品退单   $value 退单明细  $r_sn 退单id  $order订单数据
    public function refundServiceList($value,$r_sn,$order){
        $order = Db::name("order_refund_goods");
        $db  = db::connect(config('ddxx'));
        $goods['refund_id'] =$r_sn;
        $goods['og_id'] = $value['service_id'];
        $goods['r_attr_pic'] =$db->name("service")->where("s_id",intval($value['service_id']))->value("cover");
        $goods['r_subtitle'] = $value['service_name'];
        $goods['r_attr_name'] = '';
        $goods['r_num'] = $value['refund_num'];
        $goods['r_price'] = $value['refund_price'];
        $res = $order->insert($goods);
        Db::name("service_goods")->where("order_id",intval($value['order_id']))
                ->where("service_id",intval($value['service_id']))->setInc("refund",intval($value['refund_num']));
        $orderGoods  = db::name("service_goods")->where("order_id",intval($value['order_id']))->where("service_id",intval($value['service_id']))->find();
        if($orderGoods['num'] === $orderGoods['refund']){
            db::name("service_goods")->where("order_id",intval($value['order_id']))->where("service_id",intval($value['service_id']))->update(['status'=>2]);
        }
        if($res){
            return true;
        }else{
            return false;
        }
    }
    public function refundBalance($money,$user,$id){
        $member =  Db::name("member")->where("mobile",$user)->find();
        // 查询数据库
        $amount = db::name("member_money")->where("mobile",$member['mobile'])->where("member_id",$member['id'])->find();
        if($amount){
            $money = $member['money'] + $money;
            $member_money['money'] = round($money,2);
            $res = db::name("member_money")->where("mobile",$member['mobile'])->where("member_id",$member['id'])->update($member_money);
        }else{
            $member_money['member_id'] = $member['id'];
            $member_money['mobile'] = $member['mobile'];
            $money = $member['money'] + $money;
            $member_money['money'] = round($money,2);
            db::name("member_money")->insert($member_money);
        }
        $this->details($member['id'],$money,"余额");
    }
    public function details($user,$money,$type){
        // 获取新建数据库会员表
        $member = Db::name("member")->where("mobile",$user)->find();
        $details['member_id'] = isset($user)?"匿名用户":$user;
        $details['mobile']  = isset($member['mobile'])?0:$member['mobile'];
        $details['remarks'] = "";
        $details['reason']  = "商品退单余额增加".$money."元";
        $details['addtime'] = time();
        $details['amount']  = $money;
        $details['type'] = $type;
        db::name("member_details")->insert($details);
    }
    // 商品退单   $data 退单明细  $r_sn 退单id  $order订单数据
    public function refundOrderList($data,$r_sn,$order){
//        /$order = db::name("order");
        $db  = db::connect(config('ddxx'));
        $db_purchase = $db->name("purchase_price");
        $db_purchase->startTrans();
        try{
            $goods['refund_id'] = $r_sn;
            $goods['og_id'] =intval($data['item_id']);
            $goods['r_subtitle'] =$data['subtitle'];
            $goods['r_price'] = $data['refund_price'];
            $goods['r_num'] = intval($data['refund_num']);
            $item = $this->getItem($data['item_id']);
            $goods['r_attr_pic'] = $item['pics'];
            $goods['r_attr_name'] = "";
            $res = db::name("order_refund_goods")->insert($goods);
            $db->name("shop_item")->where("shop_id",intval($order['shop_id']))->where("item_id",intval($data['item_id']))->setInc("stock",intval($data['refund_num']));
            $purchase = $this->GetPurchase($order['shop_id'],$data['item_id']);
            $e_purchase = $purchase;
            $e_purchase['stock'] =  intval($purchase['stock']) + intval($data['refund_num']);
            $e_purchase['type'] = 7;
            $e_purchase['time'] = time();
            $e_purchase["pd_id"] =0;
            Db::name("order_goods")->where("order_id",intval($data['order_id']))->where("item_id",intval($data['item_id']))->setInc("refund",intval($data['refund_num']));
            $orderGoods  = db::name("order_goods")->where("order_id",intval($data['order_id']))->where("item_id",intval($data['item_id']))->find();
            if($orderGoods['num'] === $orderGoods['refund']){
                db::name("order_goods")->where("order_id",intval($data['order_id']))->where("item_id",intval($data['item_id']))->update(['status'=>2]);
            }
            $result = $this->insertpurchase($e_purchase);
            if(!$result){
                $db_purchase->rollback();
            }
            $db_purchase->commit();
            return true;
        }catch(\Exception $e){
            $error = $e->getMessage();
            //echo $error;
            $db_purchase->rollback();
        }
        return false;
    }
     public function refundType($user){
        // 获取新建数据库会员表
        $member = Db::name("member")->where("id",intval($user))->find();
        if($member){
            return true;
        }else{
            return false;
        }
    }
    public function editvalidation($value,$id,$order_id){
        //$array = array_keys($value);
        $data  = db::name("order_goods")->where("id",intval($id))->where("order_id",$order_id)->field('id,order_id,subtitle,num,refund,real_price,item_id')->find();
        if(!$data){
           $msg = ['code'=>400,'msg'=>'系统错误，请稍后重试','data'=>''];
           return $msg;
        }else{
            $val = array_diff_assoc($value,$data);
            if(intval($val['refund_num'])>intval($data['num'])){

                $msg = ['code'=>400,'msg'=>$data['subtitle']."退货数量大于购买数量",'data'=>''];
                return $msg;
            }
            if(intval($val['refund_num'])>intval($data['num']-$data['refund'])){
                $msg = ['code'=>400,'msg'=>$data['subtitle']."退货数量大于剩余数量",'data'=>''];
                return $msg;
            }
            if($val['refund_price'] >$data['real_price']){
                $msg = ['code'=>400,'msg'=>$data['subtitle']."退货金额大于成交金额",'data'=>''];
                return $msg;
            }

        }
        return ['result'=>true];
        //dump();
    }
    public function editservicevalidation($value,$id,$order_id){
        //$array = array_keys($value);
        $data  = db::name("service_goods")->where("id",intval($id))->where("order_id",$order_id)->field('id,order_id,service_name,num,refund,real_price,service_id')->find();
        if(!$data){
           $msg = ["result"=>false,'msg'=>'系统错误，请稍后重试'];
           return $msg;
        }else{
            $val = array_diff_assoc($value,$data);
            if(intval($val['refund_num'])>intval($data['num'])){
                $msg = ['code'=>400,'msg'=>$data['subtitle']."退货数量大于购买数量",'data'=>''];
                return $msg;
            }
            if(intval($val['refund_num'])>intval($data['num']-$data['refund'])){
                $msg = ['code'=>400,'msg'=>$data['subtitle']."退货数量大于剩余数量",'data'=>''];
                return $msg;
            }
            if($val['refund_price'] >$data['real_price']){
                $msg = ['code'=>400,'msg'=>$data['subtitle']."退货金额大于成交金额",'data'=>''];
                return $msg;
            }

        }
        return ['result'=>true];
        //dump();
    }
    public function ticketrefund($res){
        $data =  DB::name("ticket_user_pay")
        ->alias("a")
        ->where("a.id",intval($res['id']))
        ->where("a.shop_id",$res['shop_id'])
        ->field("a.id,a.member_id,a.order_id,a.mobile,a.ticket_id,a.status,a.waiter,a.waiter_id,o.sn,a.start_time,a.end_time,a.type,a.month,a.year,a.real_price")
        ->join("order o","a.order_id = o.id")
        ->withAttr("card",function($value,$data){
            if($data['type'] ==1){
                return $this->ticketrefundmoney($data['id']);
            }else if($data['type']==2){
                return ['type'=>"月卡"];
            }else if($data['type']==4){
                return ['type'=>"年卡"];
            }
        })
        ->withAttr("start",function($value,$data){
            if($data['status'] ==0){
                return "未激活";
            }
            return date("Y-m-d",$data['start_time']);
        })
        ->find();
        if(!$data){
            return json(['code'=>"500","msg"=>"不是该门店订单/订单不存在","data"=>""]);
        }
        if($data['status']>1){
            return json(['code'=>"500","msg"=>"该订单不可退单","data"=>""]);
        }
        if($data['status']==0){
            $data['card']['money'] =0;
            $data['card']['balance']= $data['real_price'];
            $data['end'] = "未激活";
        }else{
            if($data['type'] ==2){
                $month = $this->month_numbers(date("Y-m-d",$data['start_time']),date("Y-m-d",time()));
                if($month == $data['month']){
                    $money =$data['real_price'];
                    $data['end'] = date("Y-m-d", $data["end_time"]);
                }else{
                    $money = $data['real_price'] / $data['month'];
                    $money = number_format($money,2,".","");
                    $data['end'] = date("Y-m-d", strtotime("+".$month." months +1day", $data['start_time']));
                }
                $data['card']['money'] =$money;
                $data['months'] = $month;
            }else if($data['type'] ==4){
                $month = $this->month_numbers(date("Y-m-d",$data['start_time']),date("Y-m-d",time()));
                $year =$data['year'] *  12;
                if($month == $year){
                    $money =$data['real_price'];
                    $data['end'] = date("Y-m-d", $data["end_time"]);        
                }else{
                    $money = $data['real_price'] / $year;
                    $money = number_format($money,2,".","");
                    $data['end'] = date("Y-m-d", strtotime("+".$month." months +1day", $data['start_time']));
                }
                $data['card']['money'] =$money;
                $data['months'] = $month;
            }else{
                $data['end'] = date("Y-m-d",$data['end_time']);
            }
            $balance=$data["real_price"] - $data['card']['money'];
            $data['card']['balance'] = number_format($balance,2,".","");
        }
        return json(["code"=>"200","msg"=>"获取成功","data"=>$data]);
    }
    // 服务卡退单金额
    public function ticketrefundmoney($id){
        $data = db::name('ticket_use')
        ->alias("a")
        ->where("a.ticket_id",$id)
        ->field("a.num,a.s_num,a.r_num,a.money,a.service_id,s.sname")
        ->join("service s","a.service_id = s.id")
        ->select();
        $money = 0;
        foreach($data as $key => $val){
            $data[$key]['price'] = ($val['money']/$val['num']) *$val['r_num'];

            $money += $data[$key]['price'];
        }
        $money = number_format($money,2,".","");
        return ['data'=>$data,"money"=>$money,'type'=>"次卡"];
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
    public function orderTicketRefund($val){
        $ticket = db::name("ticket_user_pay")->where("id",intval($val['id']))->where("shop_id",$val["user"]['shop_id'])->find();
        if(!$ticket){
            return json(['code'=>"-3","msg"=>"不是本门店订单或该订单不存在","data"=>""]);
        }
        $order = db::name("order")->where("id",$ticket['order_id'])->find();
        if( ($order['pay_way'] == 12) && ($val['type'] != 'card') ){
            return json(['code'=>"-3","msg"=>"超级汇买订单只能使用银行卡退货/退款","data"=>""]);
        }
        $number = Db::name("ticket_use")->where("ticket_id",intval($val['id']))->count();
        $end_time = time();
        if($ticket['status'] ==0){
            $money = $ticket['real_price'];
            $number = true;
        }else if($ticket['status'] ==1){
            if($ticket['type'] ==1){
                $data = $this->ticketrefundmoney($ticket['id']);
                if($data['money']==0){
                    $number = true;
                }
                $money = $ticket['real_price'] - $data['money'];
            }else if($ticket['type'] ==2){
                $month = $this->month_numbers(date("Y-m-d",$ticket['start_time']),date("Y-m-d",time()));
                $money = $ticket['real_price'] / $ticket['month'] *$month;
                $money = $ticket['real_price'] - (number_format($money,2,".",""));
                $end_time = strtotime("+".$month." months +1day", $data['start_time']);
            }else if($ticket['type'] ==4){
                $month = $this->month_numbers(date("Y-m-d",$ticket['start_time']),date("Y-m-d",time()));
                $money = $ticket['real_price'] / $ticket['year'] /12*$month;
                $money = $ticket['real_price'] - (number_format($money,2,".",""));
                $end_time = strtotime("+".$month." months +1day", $data['start_time']);
            }
        }
        $otype = $val['type']=="cash"?1:($val['type'] =="balance"?2:3);
        $refund = [
            'order_id'=>$order['id'],
            'o_sn' =>$order['sn'],
            "r_sn" =>"OR".time().$order['shop_id'],
            'r_number'=>$number,
            'r_status'=>0,
            'reason'=>$val['reason']?$val['reason']:"",
            "remarks"=>$val['remarks']?$val['remarks']:"",
            "create_time"=>time(),
            "dealwith_time"=>0,
            "type"=>1,
            "r_type"=>$order['pay_way'],
            "shop_id"=>$order['shop_id'],
            'r_amount'=>$money,
            "otype" =>$otype,
            "worker_id"=>$val['user']['id'],
            "worker"=>$val['user']['name'],
        ];
        $order_db  = db::name("order_refund");
        $order_db->startTrans();
        try{
            $order_db->insert($refund);
            DB::name("order")->where("id",$order['id'])->setInc("refund_num");
            $order_db->commit();
            return json(['code'=>"200","msg"=>"申请成功","data"=>""]);
        }catch(\Exception $e){
            $error = $e->getMessage();
            $order_db->rollback();
            return json(['code'=>"500","msg"=>"服务器内部错误","data"=>$error]);
        }
    }
}
