<?php
// +----------------------------------------------------------------------
namespace app\admin\model\order;
use app\admin\common\Logs;
use app\admin\model\Adminlog;
use app\admin\model\BaseModel;
use app\admin\controller\Shareholder;
use think\Db;
class OrderRefundModel extends BaseModel
{
    protected $autoWriteTimestamp = false;

    protected $table = 'tf_after_sale';


    //获取器(门店代码对应的门店)
    public function getShopIdAttr($value)
    {
        $data = self::getShopList();
        $shopArr = [];
        foreach ($data as $v){
            $shopArr[$v['id']] = $v['name'];
        }
        $shopArr[0] = '捣蛋熊猫总店';
        $shop_name = isset($shopArr[$value])?$shopArr[$value]:'未知门店';
        return $shop_name;
    }

    public function getAddtimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        }
        return '暂无';
    }

    public function getFinishTimeAttr($value)
    {
        if ($value) {
            return date('Y-m-d H:i',$value);
        }
        return '暂未处理';
    }
    //列表数据
    public function getOrderRefundListDatas($where)
    {
        return $this->table($this->table)
            ->alias('a')
            ->field('a.as_id,a.as_sn,a.member_id,a.add_time,a.status,a.finish_time,b.nickname as name')
            ->join('tf_member b','a.member_id = b.id','LEFT')
            ->where($where)
            ->group('a.as_id')
            ->order('a.add_time DESC');
    }

    //一对一模型(用户信息)
    public function memberInfo()
    {
        return $this->belongsTo('app\admin\model\user\UserModel','member_id','id')
            ->field('id,shop_code,nickname');
    }

    //一对一模型(用户信息)
    public function declareInfo()
    {
        return $this->belongsTo('app\admin\model\user\UserDeclareModel','member_id','member_id')
            ->field('member_id,name,idcard,front_idcard,rev_idcard');
    }

    //订单详情数据
    public function getDetailData($id = '')
    {
        $datas = $this->table($this->table)
            ->alias('a')
            ->field('a.as_id,a.as_sn,a.order_id,a.member_id,a.goods_id,a.content,a.staff_reply,a.price as r_price,a.pic,a.goods_num,a.add_time,a.status,a.finish_time,b.title,c.attr_pic as thumb,b.type,c.price,c.oldprice,c.attr_name,c.attr_names')
            ->join('tf_item b','a.goods_id = b.id','LEFT')
            ->join('tf_item_attr c','b.id = c.item_id','LEFT')
            ->where(['a.as_id'=>$id])
            ->find()->toArray();
        $datas['thumb'] = config('domino.domino').$datas['thumb'];
        if($datas['pic'] == ''){
            $datas['pic'] = '';
        }elseif (is_numeric(strpos($datas['pic'],','))){
            $datas['pic'] = explode(',',$datas['pic']);
            foreach ($datas['pic'] as $key => $v){
                $datas['pic'][$key] = config('domino.domino').$v;
            }
        }else{
            $datas['pic'] = config('domino.domino').$datas['pic'];
        }
        $orderDatas = [];
        if(OrderModel::get($datas['order_id']) != null){
            $orderDatas = (new OrderModel())
                ->where(['id'=>$datas['order_id']])
                ->field('pay_way,send_way,postage,sn,mobile,realname,detail_address')
                ->find()->toArray();
        }
        $datas['order_info'] = $orderDatas;
        return $datas;
    }
    // 修改服务订单
    public function editServiceRefund($value){
        $data =$value['data'];
        //获取传递的金额
        $price = $value['array'];
        // 获取传递的退款方式
        $type  = $value['remarks']['type'];
        //获取传递的订单id
        $id    = $value['id'];
        //获取订单信息
        $order = Db::name("order")->where('id',intval($id))->find();
        //构造退单信息
        // 判断退款方式
        if($type=="balance"){
             if($order['member_id'] =="0"){
                return ['result'=>false,'msg'=>"&nbsp;&nbsp;非会员!<br>不能退款到余额!",'data'=>'','code'=>400];
            }
            $res = $this->refundType($order['member_id']);
            if(!$res){
                return ['result'=>false,'msg'=>"会员账号错误",'data'=>'','code'=>400];
            }
        }
        $refund['o_sn'] = $order['sn'];
        $refund['order_id'] = $id;
        //根据传递的数据获取退货原因 reasons 为其他的退货原因
        $refund['reason'] = isset($value['remarks']['reasons'])?$value['remarks']['reasons']:$value['remarks']['reason'];
        $refund['remarks'] = $value['remarks']['remarks'];
        $refund['add_time'] =time();
        $refund['dealwith_time'] = time();
        $refund['shop_id'] =$order['shop_id'];
        $refund['type'] =1;
        $refund['r_status'] = 1;
        $refund['r_sn'] = "OR".time().$order['shop_id'];//退款单号设定规则  O=>order + R=>refund + 时间戳 + 门店id;
        $refund['otype'] = 2; // otype订单类型 1为商品   2为服务
        $refund['creator'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
        $refund['creator_id'] = session("admin_user_auth")['uid'];
        $refund['r_type']  = $type=="balance"?"余额退款":($type=="cash"?"现金退款":"银行卡");
        $money = 0;
        $num = 0;
        foreach ($data as $key=>$value) {
            $result = $this->editservicevalidation($value,$value['id'],$value['order_id']);
            if(!$result['result']){
                return $result;
            }else{
            $money += (intval($value['refund_price'] *100) * intval($value['refund_num']))/100;
            $num += $value['refund_num'];
            }
        }
        $refund['r_amount'] = $money;
        $refund['r_number'] = $num;
        foreach($data as $k => $v){
            $res = $this->refundServiceList($v,$refund['r_sn'],$order);
            if(!$res){
                $msg =['result'=>false,'msg'=>$v['subtitle'].'添加错误'];
                return $msg;
            }
        }
        Db::name("order_refund")->insert($refund);
        $refundId = Db::name("order_refund")->getLastInsID();

        $goods_num = Db::name("service_goods")->where("order_id",intval($id))->sum("num");
        $goods_refund = Db::name("service_goods")->where("order_id",intval($id))->sum("refund");
        if($goods_num == $goods_refund){
            $status['order_status'] = -6;
        }else if($goods_refund >0){
            $status['order_status'] = -3;
        }
        Db::name("order")->where("id",intval($id))->update($status);
        if($type=="balance"){
            $balance = $this->refundBalance($money,$order['member_id'],$order['service_money_id']);
        }else if($type=="card"){
            $this->details($order['member_id'],$money,"银行卡");
        }
        $log = new Adminlog();
        $log->record("服务退单'".$refundID."'成功",0);
        $msg = ['result'=>true,'msg'=>"退货成功"];
        return $msg;
    }
    public function refundBalance($money,$user,$id){
        
        // 获取新建数据库会员表
        $member =  Db::name("member")->where("id",$user)->find();
        
        // 查询数据库
        $amount = db::name("member_money")->where("mobile",$member['mobile'])->where("member_id",$member['id'])->find();
        if($amount){
            $member_money['money'] = number_format($amount['money'], 2,".","") + $money;
            $res = db::name("member_money")->where("mobile",$member['mobile'])->where("member_id",$member['id'])->update($member_money);
        }else{
            $member_money['member_id'] = $member['id'];
            $member_money['mobile'] = $member['mobile'];
            $member_money['money'] = number_format($member['money'], 2,".","") + $money;
            db::name("member_money")->insert($member_money);
        }
        $this->details($member['id'],$money,"余额");
    }
    public function getmember($user){
        return Db::name("member")->where("mobile",$user)->find();

    }
    public function details($user,$money,$type){        
        // 获取服务器会员表        
        // 获取新建数据库会员表
        $member = Db::name("member")->where("id",$user)->find();
        $details['member_id'] = !isset($member)?"匿名用户":$member['id'];
        $details['mobile']  = !isset($member['mobile'])?0:$member['mobile'];
        $details['remarks'] = "退单";
        $details['reason']  = "商品退单余额增加".$money."元";
        $details['addtime'] = time();
        $details['amount']  = $money;
        $details['type'] = 2;
        db::name("member_details")->insert($details);
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
    // 商品退单   $value 退单明细  $r_sn 退单id  $order订单数据
    public function refundServiceList($value,$r_sn,$order){
        $order = Db::name("order_refund_goods");
        /*$db  = db::connect(config('ddxx'));*/
        $goods['refund_id'] =$r_sn;
        $goods['og_id'] = $value['service_id'];
        $goods['r_attr_pic'] =db::name("service")->where("id",intval($value['service_id']))->value("cover");
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
    //修改商品订单
    public function editOrderRefund($value){
        // 获取ajax提交的数据
        $data =$value['data'];
        //获取传递的金额
        $price = $value['array'];
        // 获取传递的退款方式
        $type  = $value['remarks']['type'];
        //获取传递的订单id
        $id    = $value['id'];
        //获取订单信息
        $order = Db::name("order")->where('id',intval($id))->find();
        // 判断退款方式
        if($type=="balance"){
            if($order['member_id'] =="0"){
                return ['result'=>false,'msg'=>"&nbsp;&nbsp;非会员!<br>不能退款到余额!",'data'=>'','code'=>400];
            }
            $res = $this->refundType($order['member_id']);
            if(!$res){
                return ['result'=>false,'msg'=>"会员账号错误",'data'=>'','code'=>400];
            }
        }
        //构造退单信息
        $refund['o_sn'] = $order['sn'];
        $refund['order_id'] = $id;
        //根据传递的数据获取退货原因 reasons 为其他的退货原因
        $refund['reason'] = isset($value['remarks']['reasons'])?$value['remarks']['reasons']:$value['remarks']['reason'];
        $refund['remarks'] = $value['remarks']['remarks'];
        $refund['add_time'] =time();
        $refund['dealwith_time'] = time();
        $refund['shop_id'] =$order['shop_id'];
        $refund['type'] =1;
        $refund['r_status'] = 1;
        $refund['r_sn'] = "OR".time().$order['shop_id'];//退款单号设定规则  O=>order + R=>refund + 时间戳 + 门店id;
        $refund['otype'] = 1; // otype订单类型 1为商品   2为服务
        $refund['creator'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
        $refund['creator_id'] = session("admin_user_auth")['uid'];
        $refund['r_type']  = $type=="balance"?"余额退款":($type=="cash"?"现金退款":"银行卡");
        $money = 0;
        $num = 0;
        foreach ($data as $key=>$value) {
            $result = $this->editvalidation($value,$value['id'],$value['order_id']);
            if(!$result['result']){
                return $result;
            }else{
            $money += (intval($value['refund_price'] *100) * intval($value['refund_num']))/100;
            $num += $value['refund_num'];
            }
        }
        $refund['r_amount'] = $money;
        $refund['r_number'] = $num;

        foreach($data as $k => $v){
            $res = $this->refundOrderList($v,$refund['r_sn'],$order);
            if(!$res){
            	$msg =['result'=>false,'msg'=>$v['subtitle'].'添加错误'];
               	return $msg;
            }
        }
        $res  = Db::name("order_refund")->insert($refund);
        $refundId = Db::name("order_refund")->getLastInsID();

        $goods_num = Db::name("order_goods")->where("order_id",intval($id))->sum("num");
        $goods_refund = Db::name("order_goods")->where("order_id",intval($id))->sum("refund");
        if($goods_num == $goods_refund){
            $status['order_status'] = -6;
        }else if($goods_refund >0){
            $status['order_status'] = -3;
        }
        Db::name("order")->where("id",intval($id))->update($status);
        if($type=="balance"){
            $balance = $this->refundBalance($money,$order['member_id'],$order['service_money_id']);
        }else if($type=="card"){
            $this->details($order['member_id'],$money,"银行卡");
        }
        $log = new Adminlog();
        $log->record("新增退单'".$refundID."'",0);
        $msg = ['result'=>true,'msg'=>"退货成功"];
        return $msg;
    }
    public function refundOrderList($data,$r_sn,$order){
        $db_purchase = db::name("purchase_price");
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
            DB::name("shop_item")->where("shop_id",intval($order['shop_id']))->where("item_id",intval($data['item_id']))->setInc("stock",intval($data['refund_num']));
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
            echo $error;
            $db_purchase->rollback();
        }
        return false;
    }
    // 服务卡退货时 数据获取
    public function ticketrefund($val){
        $data = DB::name("ticket_user_pay")
        ->alias("a")
        ->where("a.id",intval($val['id']))
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
        
        if($data['status']==0){
            $data['card']['money'] =0;
            $data['card']['balance']= $data['real_price'];
            $data['end'] = "未激活";
        }else{
            if($data['type'] ==2){
                $month = $this->month_numbers(date("Y-m-d",$data['start_time']),date("Y-m-d",time()));
                if($month == $data['month']){
                    $money =0;
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
                    $money =0;
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
        return $data;    
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
    // 服务卡退单金额
    public function ticketrefundmoney($id){
        $data = db::name('ticket_use')
        ->alias("a")
        ->where("a.ticket_id",$id)
        ->field("a.num,a.s_num,a.money,a.service_id,s.sname")
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
    // 服务卡退单流程
    public function orderTicketRefund($val){
        $ticket = db::name("ticket_user_pay")->where("id",intval($val['id']))->find();
        $order = db::name("order")->where("id",$ticket['order_id'])->find();
        $refund = db::name("order_refund")->where("order_id",$order['id'])->find();
        $number = false;
        $model=new Shareholder();
        if($ticket['status'] ==0){
            $number = true;
        }else if($ticket['status'] ==1){
            if($ticket['type'] ==1){
                $data = $this->ticketrefundmoney($ticket['id']);
                if($data['money']==0){
                    $number = true;
                }
            }
        }
        //构建order_refund 退单数据
        $update_refund = [
            "update_time"=>time(),
            "dealwith_time"=>time(),
            "r_status"=>1,
            "creator_id"=>session("admin_user_auth")['uid'],
            "creator"=>Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            "status"=>1,
        ];
        $update_ticket = [
            "refund"=>Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            "refund_time"=>time(),
            "refund_id"=>session("admin_user_auth")['uid'],
            "status"=>4,
            "end_time"=>$ticket['status']==0?time():$this->getOverTime($ticket),
        ];
        $update_order = [
            "returntime" =>time(),
            "order_status"=>-6,
        ];
        $details = [
            "member_id"=>$ticket['member_id'],
            "mobile"=>$ticket['mobile'],
            "remarks"=>"",
            "reason"=>"服务卡退卡增加".$refund["r_amount"]."元",
            "addtime"=>time(),
            "amount"=>$refund["r_amount"],
            "order_id"=>$order['id'],
        ];
        $statistics =[
            "order_id" =>$order['id'],
            "shop_id" =>$order['shop_id'],
            "order_sn" =>$order["sn"],
            "type"=>4,
            "data_type"=>2,
            "pay_way"=>$refund['otype']==1?5:($refund['otype']==2?3:4),
            "price"=>-$refund["r_amount"],
            "create_time"=>time(),
            "title"=>"服务卡退单",
        ];
        $data = [
            "type"=>4,
            "member_id"=>$ticket['member_id'],
            "pay_way"=>$refund['r_type'],
            "waiter_id"=>$order['waiter_id'],
            "price"=>-$refund['r_amount'],
            "title"=>"服务卡退单充值",
            "remarks"=>"服务卡退单",
            "details"=>1,
            'is_admin'  =>0
        ];
        $order_db = Db::name("order");
        $order_db->startTrans();
        try{
            $ticket_card = db::name("ticket_card")->where("id",$ticket['ticket_id'])->value("type");
            if($number && $ticket_card == 1){
                db::name("ticket_card")->where("id",$ticket['ticket_id'])->setInc("exchange_num");
                db::name("ticket_card")->where("id",$ticket['ticket_id'])->update(['display'=>0]);
            }
            db::name("order_refund")->where("id",$refund['id'])->update($update_refund);
            db::name("ticket_user_pay")->where("id",intval($val['id']))->update($update_ticket);
            db::name("order")->where("id",$ticket['order_id'])->update($update_order);           
            //余额退款
            if($refund['otype'] !== 2){
                $res_model  = $model->refund_recharge($data);
                if(!$res_model){
                    $order_db->rollback();
                    return json(["result"=>false,'msg'=>"系统繁忙，请稍后再试","data"=>"res_model"]);
                }
            }else{
                $member_money = db::name("member_money")->where("member_id",$ticket['member_id'])->value("money");
                DB::name("member_money")->where("member_id",$ticket['member_id'])->update(["money"=>$member_money+$refund["r_amount"]]);
                db::name("member_details")->insert($details);
            }
            //2020-10-09按财务需求，更正任何退单数据，股东数据必进一个“余额消耗”，因此注销条件判断
//            if($ticket['type'] == 1){
                db::name("statistics_log")->insert($statistics);
//            }
            $order_db->commit();
            $msg =['result'=>true,"msg"=>"退卡成功","data"=>""];
        }catch(\Exception $e){
            $error = $e->getMessage();
            $order_db->rollback();
            $msg =['result'=>false,"msg"=>"服务器错误,请稍后重试","data"=>$error];
        }
        return $msg;
    }
    public function getOverTime($val){
        if($val['type'] ==1){
            $end_time = time();
        }else if($val['type'] == 2 || $val['type'] ==4){   
            $month = $this->month_numbers(date("Y-m-d",$val['start_time']),date("Y-m-d",time())); 
            $end_time = strtotime("+".$month." months +1day", $val['start_time']);
        }
        return $end_time;
    }
    public function orderTicketRefundold($val){
        $ticket = db::name("ticket_user_pay")->where("id",intval($val['id']))->find();
        $order = db::name("order")->where("id",$ticket['order_id'])->find();
        $number = Db::name("ticket_use")->where("ticket_id",intval($val['id']))->count();
        $end_time = time();
        $number = false;
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
            }else if($ticket['type'] == 2 || $ticket['type'] ==4){
                $month = $this->month_numbers(date("Y-m-d",$ticket['start_time']),date("Y-m-d",time()));
                $money = $ticket['real_price'] / $ticket['month'] *$month;
                $money = $ticket['real_price'] - (number_format($money,2,".",""));
                $end_time = strtotime("+".$month." months +1day", $data['start_time']);
            }
        }
        $otype = $val['data']['type']=="cash"?1:($val['data']['type'] =="balance"?2:3);
        $order_db = Db::name("order");
        $refund = [
            'order_id'=>$order['id'],
            'o_sn' =>$order['sn'],
            "r_sn" =>"OR".time().$order['shop_id'],
            'r_number'=>$number,
            'r_status'=>1,
            'reason'=>$val['data']['reason']?$val['data']['reason']:"",
            "remarks"=>$val['data']['remarks']?$val['data']['remarks']:"",
            "add_time"=>time(),
            "dealwith_time"=>time(),
            "type"=>1,
            "shop_id"=>$order['shop_id'],
            'r_amount'=>$money,
            "otype" =>$otype,
        ];

        $m_ticket = [
            "refund"=>session("admin_user_auth")['uid'],
            "refund_time"=>time(),
            "end_time" =>time(),
            "status"=>4,
        ];
        $m_order = [
            "returntime" =>time(),
            "order_status"=>-6,
        ];
        $details = [
            "member_id"=>$ticket['member_id'],
            "mobile"=>$ticket['mobile'],
            "remarks"=>"",
            "reason"=>"服务卡退卡增加".$money."元",
            "addtime"=>time(),
            "amount"=>$money,
            "order_id"=>$order['id'],
        ];
        $order_db->startTrans();
        try{
            $ticket_card = db::name("ticket_card")->where("id",$ticket['ticket_id'])->value("type");
            if($number && $ticket_card == 1){
                db::name("ticket_card")->where("id",$ticket['ticket_id'])->setInc("exchange_num");
                db::name("ticket_card")->where("id",$ticket['ticket_id'])->update(['display'=>0]);
            }
            db::name("order_refund")->insert($refund);
            db::name("ticket_user_pay")->where("id",intval($val['id']))->update($m_ticket);
            db::name("order")->where("id",$ticket['order_id'])->update($m_order);
            db::name("member_details")->insert($details);
            $member_money = db::name("member_money")->where("member_id",$ticket['member_id'])->value("money");
            DB::name("member_money")->where("member_id",$ticket['member_id'])->update(["money"=>$member_money+$money]);
            $order_db->commit();
            $result = true;
        }catch(\Exception $e){
            $error = $e->getMessage();
            $order_db->rollback();
            $result = false;
        }
        return $result;
    }
    public function insertpurchase($purchase){
        $res = Db::name("purchase_price")->insert($purchase);
        return $res;
    }
    public function GetPurchase($shop_id,$item_id){
        $data =DB::name("purchase_price")->where("shop_id",intval($shop_id))->where("item_id",intval($item_id))
                ->field("shop_id,item_id,md_price,store_cose,stock")->order("id desc")->find();
        return $data;
    }
    public function getItem($id){
        $item = Db::name("item")->where("id",intval($id))->find();
        return $item;
    }
    // 修改时的数据验证(商品)
    public function editvalidation($value,$id,$order_id){
        //$array = array_keys($value);
        $data  = db::name("order_goods")->where("id",intval($id))->where("order_id",$order_id)->field('id,order_id,subtitle,num,refund,real_price,item_id')->find();
        if(!$data){
           $msg = ["result"=>false,'msg'=>'系统错误，请稍后重试'];
           return $msg;
        }else{
            $val = array_diff_assoc($value,$data);
            if(intval($val['refund_num'])>intval($data['num'])){
                $msg=['result'=>false,'msg'=>$data['subtitle']."退货数量大于购买数量"];
                return $msg;
            }
            if(intval($val['refund_num'])>intval($data['num']-$data['refund'])){
                $msg=['result'=>false,'msg'=>$data['subtitle']."退货数量大于剩余数量"];
                return $msg;
            }
            if($val['refund_price'] >$data['real_price']){
                $msg =['result'=>false,'msg'=>$data['subtitle'].'退货金额大于成交金额'];
                return $msg;
            }

        }
        return ['result'=>true];
        //dump();
    }
    // 修改时的数据验证(服务)
    public function editservicevalidation($value,$id,$order_id){
        //$array = array_keys($value);
        $data  = db::name("service_goods")->where("id",intval($id))->where("order_id",$order_id)->field('id,order_id,service_name,num,refund,real_price,service_id')->find();
        if(!$data){
           $msg = ["result"=>false,'msg'=>'系统错误，请稍后重试'];
           return $msg;
        }else{
            $val = array_diff_assoc($value,$data);
            if(intval($val['refund_num'])>intval($data['num'])){
                $msg=['result'=>false,'msg'=>$data['service_name']."退货数量大于购买数量"];
                return $msg;
            }
            if(intval($val['refund_num'])>intval($data['num']-$data['refund'])){
                $msg=['result'=>false,'msg'=>$data['service_name']."退货数量大于剩余数量"];
                return $msg;
            }
            if($val['refund_price'] >$data['real_price']){
                $msg =['result'=>false,'msg'=>$data['service_name'].'退货金额大于成交金额'];
                return $msg;
            }

        }
        return ['result'=>true];
        //dump();
    }
    //处理售后
    public function dealwith($data,$id)
    {
        $msg = '处理售后,id为：'.$id;
        Logs::actionLogRecord($msg);
        return self::update($data,['as_id'=>$id]);
    }

    //删除订单
    public function deletes($id)
    {
        $orderInfo = self::get($id);
        $msg = '将退货订单删除,订单号: '.$orderInfo->as_sn;
        Logs::actionLogRecord($msg);
        LogsController::actionLogRecord($msg);
        $result = $orderInfo->destroy(['as_id'=>$id]);
        if($result){
            return true;
        }
        return false;
    }

}
