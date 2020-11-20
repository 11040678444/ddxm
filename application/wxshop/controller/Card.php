<?php
namespace app\wxshop\controller;

use app\index\model\Adminlog;
use app\index\model\ticket\ticketModel;
use app\wxshop\model\member\MemberTicketModel;
use think\Db;
use think\Request;
use think\Query;
/**
商城,用户卡券信息
 */
class Card extends Token
{
    /***
     *  卡券列表
     * 0 待领取 1待使用 ，2已用完，3已过期
     */
    public function cardList(){
        $data = $this ->request ->param();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $where = [];
        if( isset($data['status']) && $data['status']!='' ){
            $where[] = ['a.status','eq',$data['status']];
        }
        $where[] = ['a.member_id','eq',self::getUserId()];
        $where[] = ['a.is_online','eq',1];
        $where[] = ['a.start_time','<=',time()];    //待领取时：累计送的是下个月，得到了能领取时间 才能查询出来
        $ticket = Db::name("ticket_user_pay")
            ->alias("a")
            ->join("ddxm_ticket_card c","a.ticket_id = c.id")
            ->where($where)
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
                    return "未领取";
                }else{
                    return date("Y-m-d",$data['start_time']);
                }
            })
            ->withAttr("end_time",function($value,$data){
                if($data['end_time']==0){
                    if($data['over_time'] == 0){
                        return "无限制";
                    }else{
                        return date("Y-m-d",$data['over_time']);
                    }
                }else{
                    return date("Y-m-d",$data['end_time']);
                }
            })
            ->withAttr("status_name",function($value,$data){
                if($data['status']==0){
                    return "未领取";
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
            ->page($page,$limit)
            ->select();
        $count = db::name("ticket_user_pay")->alias('a')->where($where)->count();

        //计算当月微信累计消费
        $accumulative_total = 0;        //当月累计消费
        $map = [];
        $map[] = ['member_id','eq',self::getUserId()];
        $map[] = ['status','eq',0];
//        $map[] = ['start_time','<=',time()];
//        $map[] = ['end_time','>=',time()];
        $or = Db::name('grand_total') ->where($map)->order('id desc')->find();
        if( $or ){
            //查询订单
            $emp =[];
            $emp[] = ['member_id','eq',self::getUserId()];
            $emp[] = ['type','neq',3];
            $emp[] = ['pay_status','eq',1];
            $emp[] = ['is_online','eq',1];
            $emp[] = ['refund_status','eq',0];
            $emp[] = ['refund_type','eq',0];
            $emp[] = ['pay_way','eq',1];
            $emp[] = ['paytime','>=',$or['start_time']];
            $emp[] = ['paytime','<=',$or['end_time']];
            $accumulative_total = Db::name('order')->where($emp)->sum('amount');
        }
        return json(['code'=>200,'msg'=>'获取成功',"count"=>$count,'accumulative_total'=>$accumulative_total,"data"=>$ticket]);
    }

    /***
     * 服务卡领取
     */
    public function active(){
        $res = $this->request->param();
        // 判断卡卷id是否存在
        if(!isset($res['card_id']) || empty($res['card_id'])){
            return json(['code'=>-3,'msg'=>"请选择服务卡",'data'=>""]);
        }
        $shop_id = self::getUserInfo()['shop_id'];
        $waiter = Db::name('shop_worker')
            ->where('sid',$shop_id)
            ->where('status',1)
            ->where('post_id',1)
            ->find();
        // 判断激活的服务员
        if(!$waiter){
            return json(['code'=>"-3","msg"=>"服务员数据错误","data"=>""]);
        }
        $db_ticket = db::name("ticket_user_pay");
        $data =$db_ticket->where("id",intval($res['card_id']))->where('member_id',self::getUserId())->find();
        $ticket_card = Db::name("ticket_card")->where("id",$data['ticket_id'])->find();
        if($data['status'] ==1){
            return json(['msg'=>-200,"msg"=>"该卡已领取",'data'=>""]);
        }
        //判断是否已经过了领取时间
        if( time() > $data['end_time'] ){
            return json(['msg'=>-200,"msg"=>"该卡已过期",'data'=>""]);
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
        // 开启事务
        $db_ticket->startTrans();
        $result = db::name("ticket_user_pay")->where("id",intval($res['card_id']))->update($ticket);
        db::name('member_ticket')->where('card_id',$res['card_id'])->update(['receive_time'=>time(),'status'=>1,'use_expire_time'=>$this->getOverTime($ticket_card)]);
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
                $log->record_insert($waiter['name']."激活了id为'".$data["id"]."'的服务卡",0,"",$waiter['id']);
            }
            $db_ticket->commit();
        }catch(\Exception $e){
            $error = $e->getMessage();
            $db_ticket->rollback();
        }
        if($result){
            return json(['code'=>200,"msg"=>"领取成功","data"=>""]);
        }else{
            return json(['code'=>500,"msg"=>"领取失败，请稍候",'data'=>""]);
        }
    }

    /***
     * 服务卡使用
     */
    public function use_ticket(){
        $res = $this->request->param();
        // 判断卡卷id是否存在
        if(!isset($res['card_id']) || empty($res['card_id'])){
            return json(['code'=>-3,'msg'=>"请选择服务卡",'data'=>""]);
        }
        $pay_ticket = Db::name("ticket_user_pay")->where('member_id',self::getUserId())->where("id",intval($res['card_id']))->find();
        if($pay_ticket['status'] == 0){
            return json(['msg'=>-200,"msg"=>"请先领取服务卡",'data'=>""]);
        }
        if($pay_ticket['status'] == 3){
            return json(['msg'=>-200,"msg"=>"服务卡已过期",'data'=>""]);
        }
        $code = '';
        for ( $i=0;$i<6;$i++ ) {
            $code .= rand(0,9);
        }
        $res = Db::name('ticket_user_pay')->where("id",intval($res['card_id']))->setField('code',$code);
        if( $res ){
            return json(['code'=>200,'msg'=>'请根据核销码到门店使用','data'=>['code'=>$code]]);
        }else{
            return json(['code'=>100,'msg'=>'获取失败,请联系管理员']);
        }
    }

    /***
     * 卡券详情
     */
    public function cardInfo(){
        $res = $this->request->param();
        if(!isset($res['card_id']) || empty($res['card_id'])){
            return json(['code'=>-3,"msg"=>"请输入已购服务卡id",'data'=>""]);
        }
        //耗卡记录
        $result = DB::name("ticket_consumption")->alias('a')
            ->where("member_id",self::getUserId())
            ->where("ticket_id",intval($res['card_id']))
            ->field("service_name,waiter,time,shop_id,b.name")
            ->join('shop b','a.shop_id=b.id')
            ->withAttr("time",function($value,$data){
                return date("Y-m-d H:i:s",$data['time']);
            })
            ->order('a.id','desc')
            ->select();
        $count =   DB::name("ticket_consumption")
            ->where("member_id",self::getUserId())
            ->where("ticket_id",intval($res['card_id']))
            ->count();
        //卡片详情
        $info = (new MemberTicketModel())
            ->where('card_id',$res['card_id'])
            ->where("member_id",self::getUserId())
            ->field('id,create_time,receive_expire_time,receive_time,use_expire_time')
            ->find();
        if( !$info ){
            return json(['code'=>-3,"msg"=>"服务卡有误",'data'=>""]);
        }
        return json(['code'=>200,"total"=>$count,"data"=>['info'=>$info,'history'=>$result]]);
    }

    /***
     * 获取服务卡到期时间
     * @param $res
     * @return false|int
     */
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

}