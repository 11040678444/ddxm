<?php

// +----------------------------------------------------------------------
// | 服务卡模块
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\ticket\TicketModel;
use app\common\controller\Adminbase;
use app\admin\model\Adminlog;

use think\Db;
use think\db\Where;

/**
 *
 * 服务卡
 * Class Ticket
 * @package app\admin\controller
 */
class Ticket extends Adminbase
{

    public function index(){
        return $this->fetch();
    }
    public function add(){
        $ticket = new TicketModel();
        if ($this->request->isAjax()) {
            $res  = $this->request->post();
            $result = $ticket->add_ticket($res);
            return json($result);
        }
        $ticket = new TicketModel();
        $data['shop'] = $ticket->shop();
        $data['level'] = $ticket->level();
        $level = count($data['level']);
        $this->assign("level",$level);
        $this->assign("data",$data);
        return $this->fetch();
    }
    public function service_list(){
        $data = db::name("service")->field("sname,id as s_id")->select();
        if($data){
            return json(['code'=>200,'msg'=>"查询成功",'data'=>$data]);
        }else{
            return json(['code'=>400,'msg'=>"服务器繁忙",'data'=>$data]);
        }
    }

    //服务卡列表
    public function ticket_list(){
        $res = $this->request->get();
        $ticket = new TicketModel();
        $page  = $res['page'];
        $limit = $res['limit'];
        $card_name = input('card_name');
        $where = [];
        $where[] = ["del","=",0];
        $where[] = ["show","=",1];
        if($card_name!=''){
            $where[] = ["card_name",'like','%'.$card_name.'%'];
        }
//        card_name
        $data = $ticket->ticket_list($res,$where)->page($page,$limit)->select();
        $total = $ticket->ticket_list($res,$where)->count();
        $result = array("code" => 0, "count" => $total, "data" => $data);
        return json($result);
    }
    public function ticket_shop(){
        $res = $this->request->post();
        $data = DB::name("ticket_shop")->where("card_id",intval($res['id']))->select();
        if($data){
            return json(['result'=>true,"data"=>$data]);
        }else{
            return json(['result'=>false,"data"=>""]);
        }
    }
    public function ticket_service(){
        $res = $this->request->get();
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
            return json(["code"=>0,"count"=>$total,'data'=>$data]);
        }else{
            return json(["code"=>0,"count"=>0,"data"=>""]);
        }
    }
    public function ticket_service_money(){
        $res = $this->request->post();
        $data = Db::name("ticket_service_money")->where("ts_id",$res['id'])->field("price,level_name")->select();
        if($data){
            return json(['result'=>true,"data"=>$data,"msg"=>"查询成功"]);
        }else{
            return json(['result'=>false,"data"=>$data,"msg"=>"查询失败"]);
        }
    }
    public function ticket_service_other(){
        $res = $this->request->post();
        $data = db::name("ticket_other_restrictions")
        ->where("ticket_service_id",intval($res['id']))
        ->field("start_time,num,end_time")
        ->withAttr('start_time',function ($value,$data) {
            if($data['start_time']==0){
                return $data['start_time'];
            }
            return date("Y-m-d",$data['start_time']);
        })
        ->withAttr('end_time',function ($value,$data) {
            if($data['end_time']==0){
                return $data['end_time'];
            }
            return date("Y-m-d",$data['end_time']);
        })
        ->select();
        if($data){
            return json(['result'=>true,"data"=>$data,"msg"=>"查询成功"]);
        }else{
            return json(['result'=>false,"data"=>$data,"msg"=>"查询失败"]);
        }
    }
    //查看 服务卡 详情
    public function details(){
        $res = $this->request->get();
        //$ticket = new TicketModel();
        $result = Db::name("ticket_card")
        ->where("id",intval($res['id']))
        ->withAttr("service",function($value,$datas){
            $service = Db::name("ticket_service")
            ->where("card_id",intval($datas['id']))
            ->withAttr("money",function($val,$da){
                $money = Db::name("ticket_service_money")->where("ts_id",$da['id'])->field("level_name,price")->select();
                return $money;
            })
            ->withAttr("other",function($valo,$dao){
                $other = Db::name("ticket_other_restrictions")
                ->where("ticket_service_id",$dao['id'])
                ->withAttr("start_time",function($v,$d){
                    if($d['start_time'] == 0){
                        return 0;
                    }else{
                        return date("Y-m-d",$d['start_time'] );
                    }
                })
                ->withAttr("end_time",function($v,$d){
                    if($d['end_time'] == 0){
                        return 0;
                    }else{
                        return date("Y-m-d",$d['end_time'] );
                    }
                })
                ->field("start_time,end_time,num")
                ->select();
                return $other;
            })
            ->withAttr("num",function($valo,$dao){
                if($dao['num'] ==0){
                    return "无限制";
                }
                return $dao['num'];
            })
            ->field("id,service_id,num,service_name,day,month,year")
            ->select();
            return $service;
        })
        ->withAttr("shop",function($value,$data){
            $shop = Db::name("ticket_shop")->where("card_id",$data['id'])->field("shop_name")->select();
            return $shop;
        })
        ->withAttr("money",function($value,$data){
            $price = Db::name("ticket_money")->where("card_id",$data['id'])->field("price,mprice,level_name")->select();
            return $price;
        })
        ->withAttr("critulation",function($value,$data){
            if($data['critulation'] ==0){
                return "无限制";
            }else{
                return $data['critulation'];
            }
        })
        ->withAttr("restrict_num",function($value,$data){
            if($data['restrict_num'] ==0){
                return "无限制";
            }else{
                return $data['restrict_num'];
            }
        })
        ->withAttr("start_time",function($value,$data){
            if($data['start_time'] ==0){
                return "无限制";
            }else{
                return date("Y-m-d",$data['start_time']);
            }
            return "暂无数据";     
        })
        ->withAttr("end_time",function($value,$data){
            if($data['end_time'] ==0){
                return "无限制";
            }else{
                 return date("Y-m-d",$data['end_time']);
            }
            return "暂无数据";     
        })
        ->withAttr("creator",function($value,$data){
            $creator = Db::name("admin")->where("userid",$data['creator_id'])->value("nickname");
            return $creator;
        })
        ->withAttr("modifier",function($value,$data){
            if($data['modifier']){
                return Db::name("admin")->where("userid",$data['creator_id'])->value("nickname");
            }
            return '未修改';
        })
        ->find();
        $this->assign("data",$result);
        return $this->fetch("details");
    }
    public function edit(){
        $res = $this->request->get();
        $ticket = new TicketModel();
        $result = Db::name("ticket_card")
        ->where("id",intval($res['id']))
        ->withAttr("service",function($value,$datas){
            $service = Db::name("ticket_service")
            ->where("card_id",intval($datas['id']))
            ->withAttr("money",function($val,$da){
                $money = Db::name("ticket_service_money")->where("ts_id",$da['id'])->select();
                return $money;
            })
            ->withAttr("other",function($valo,$dao){
                $other = Db::name("ticket_other_restrictions")->where("ticket_service_id",$dao['id'])->select();
                return $other;
            })
            ->select();
            return $service;
        })
        ->withAttr("service_count",function($value,$data){
            $count = Db::name("ticket_service")
            ->where("card_id",intval($data['id']))
            ->count();
            return $count;
        })
        ->withAttr("type",function($value,$data){
            $type = $this->getType($data['type']);
            return $type;
        })
        ->withAttr("shop",function($value,$data){
            return $this->editShop($data['id']);
        })
        ->withAttr("money",function($value,$data){
            $price = Db::name("ticket_money")->where("card_id",$data['id'])->select();
            return $price;
        })
        ->find();
        $this->assign("data",$result);
        return $this->fetch();
    }
     public function editShop($id){
        $ticket = new TicketModel();
        $data = $ticket->getshop($id);
        return $data;
    }
    public function getType($val){
        $data =db::name("ticket_card_type")->select();
        foreach($data as $k=>$v){
           
            if($v['id'] ==intval($val)){
                $data[$k]['check'] = "checked";
            }else{
                $data[$k]['check'] = "";
            }
        }
        return $data;
    }
    public function ticketDel(){
        $res = $this->request->post();
        $result = Db::name("ticket_card")->where("id",intval($res['id']))->update(['del'=>1]);
        $log = new Adminlog();
        $log->record("删除成功'".$res['id']."'",0,"删除服务卡-".$res['id']."");
        if($result){
            return json(['result'=>true,'msg'=>"删除成功","data"=>""]);
        }else{
            return json(['result'=>false,'msg'=>"系统错误，请稍后","data"=>""]);
        }
    }
    public function ticket_timer(){
        // status =0  服务卡未激活时  过期了的
        $data = db::name("ticket_user_pay")->where("status",0)->where("over_time",">",0)->where("over_time","<",time())->select();
        // status = 1 服务卡已激活  结束时间到了  完结
        $datas = db::name("ticket_user_pay")->where("status",1)->where("end_time",">",0)->where("end_time","<",time())->select();
        foreach($data as $k => $v){
            $res = $this->ticket_sub_card($v);
        }
        foreach($datas as $key => $val){
            if($val['type'] ==1){
                $res = $this->ticket_timer_card($val);
                DB::name("ticket_user_pay")->where("id",$val['id'])->update(['status'=>3]);
            }else if($val['type']==2 || $val['type']==4){
                DB::name("ticket_user_pay")->where("id",$val['id'])->update(['status'=>3]);
            }
        }
    }
    // 
    public function ticket_timer_card($data){
        $ticket = db::name("ticket_use")->where("ticket_id",$data['id'])->select();
        $amount = db::name("ticket_consumption")->where("ticket_id",$data['id'])->sum("price");
        $price  = $ticket['real_price'] - $amount;
        foreach($ticket as $key => $val){
            $ticket_price = db::name("ticket_consumption")->where("ts_id",$val['id'])->sum("price");
            $s_price = $val['money'] - $ticket_price;
            $consumption = [
                "member_id" =>$data['member_id'],
                "shop_id" =>$data['shop_id'],
                "ticket_id"=>$data['ticket_id'],
                "service_id"=>$val['service_id'],
                "service_name"=>db::name("ticket_service")->where("id",$val['service_id'])->value("service_name"),
                "waiter" =>"系统自动",
                "waiter_id"=>0,
                "time"=>time(),
                "num" =>$val['s_num'],
                "ts_id"=>$val['id'],
                "price"=>$s_price,
            ];
            $statistics = [
                "order_id"=>$data['order_id'],
                "shop_id" =>$data['shop_id'],
                "order_sn"=>db::name("order")->where("id",$data['order_id'])->value("sn"),
                "type"=>4,
                "data_type"=>1,
                "create_time"=>time(),
                'title'=>'服务卡耗卡',
                "price"=>$s_price,
            ];
            db::name("statistics_log")->insert($statistics);
            db::name("ticket_consumption")->insert($consumption);
            db::name("ticket_use")->where("id",$val['id'])->update(['s_num'=>0]);
        }
        
    }
    //服务卡是次卡时处理
    public function ticket_sub_card($data){
        $ticket['status'] = 3;
        $order = DB::name("order")->where("id",$data['order_id'])->find();
        $type  = "";
        if($data['type'] == 1){
            $type = "次卡";
        }else if($data['type'] == 2){
            $type = "月卡";
        }else if($data['type'] == 4){
            $type = "年卡";
        }
        $statistics = [
            "order_id" => $data['order_id'],
            "shop_id"  => $data["shop_id"],
            "order_sn" => $order["sn"],
            "type"=>4,
            "data_type"=> 1,
            "pay_way"  => $order['pay_way'],
            "price"    => $order['amount'],
            "create_time"=>time(),
            "title"    => "服务卡".$type."激活超时",
        ];
        DB::name("ticket_user_pay")->where("id",$data['id'])->update($ticket);
        db::name("statistics_log")->insert($statistics);
    }   
}
