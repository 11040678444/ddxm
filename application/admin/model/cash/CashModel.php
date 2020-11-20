<?php
// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------
namespace app\admin\model\cash;
use app\admin\model\Adminlog;
use think\Model;
use think\Db;
class CashModel extends Model {
    protected $table = 'ddxm_cash';
    
    protected $table_user = 'ddxm_cash_user';
    public function index($res){
        return $this->table($this->table);
    }
    public function getIndex($data){
        foreach($data as $key =>$val){
           /* if($val['update'] =="0"){
                continue;
            }*/
            $cash = db::name("cash_attr")->where("cash_id",$val['id'])->where("update",$val['update'])
            ->field("cash_id as id,update,update_time,update_id,update_name,item,service,ticket,title,price,num,days,time_num,o_price,remarks,end_time")
            ->find();

            $cash['create_time'] = $val['create_time'];
            $cash['creator_id'] = $val['creator_id'];
            $cash['creator'] = $val['creator'];
            $cash['state'] = $val['state'];
            $cash['status'] = $val['status'];
            if($cash['days'] =="day"){
                $day = "天";
            }else if($cash['days'] =="month"){
                $day = "月";
            }
            $cash['days'] = $cash['time_num'].$day;
            $data[$key] = $cash;
        }
        return $data;
    }
    public function product($res){
        $data = $res['data'];
        $search = $res['search'];
        if($data=="item"){
            $sql = db::name("item")->where("title","like","%{$search}%")->field("id,title");
        }else if($data=="service"){
            $sql  = db::name("service")->where("sname","like","%{$search}%")->field("id,sname as title");
        }else if($data == "ticket"){
            $sql = db::name("ticket_card")->where("card_name","like","%{$search}%")->field("id,card_name as title");
        }
        return $sql;
    }
    public function manage(){
        return $this->table($this->table_user);
    }
    public function getCreateTimeAttr($val){
        return date("Y-m-d H:i:s",$val);
    }
    public function getOverTimeAttr($val){
        return date("Y-m-d",$val);
    }
    public function getStateAttr($val){
        $data =[
            0=>"待使用",
            1=>"已使用",
            2=>"已过期",
        ];
        return $data[$val];
    }
    public function add($res){
        $o_price = $res['other'] ==1?$res['other_price']:0;
        if(!isset($res['product']) || empty($res['product'])){
            return json(['result'=>false,"msg"=>"请选择所属项目","data"=>"product=null"]);
        }
        $product =$res['product'];
        $item = 0;
        $service = 0;
        $ticket  = 0;
        for($i = 0;$i<count($product);$i++){
            $array = $product[$i];
            if($array == "item"){
                $item = $res[$array."_select"];
            }
            if($array == "service"){
                $service = $res[$array."_select"];
            }
            if($array == "ticket"){
                $ticket = $res[$array."_select"];
            }
            $title = $array=="item"?"商品":($array=="ticket"?"服务卡":"服务");
            if($res[$array."_select"] =="1" && count($res[$array]) ==0){
                return json(['result'=>false,'msg'=>"请选择".$title,"data"=>""]);
            }
        }
        $button_title = $this->getButtonTitle($item,$service,$ticket);
        $cash = [
            "title"=>$res['title'],
            "price"=>$res['price'],
            "num"=>$res['num'],
            "o_price"=>$o_price,
            "days"=>$res['days'],
            "time_num"=>$res['time_num'],
            "sn"=> dechex(time()),
            "create_time" =>time(),
            "creator"=>db::name("admin")->where("userid",session("admin_user_auth")['uid'])->value("nickname"),
            "creator_id"=>session("admin_user_auth")['uid'],
            "state"=>1,
            "item"=>$item,
            "service"=>$service,
            "ticket"=>$ticket,
            "refund"=>$res['refund'],
            "update"=>0,
        ];
        $db = db::name("cash");
        $db->startTrans();
        try{
            $id = db::name("cash")->insertGetId($cash);
            if($item    == 1){
                foreach($res['item'] as $ik => $iv){
                    $item_array =[
                        "cash_id"=>$id,
                        "type"=>"item",
                        "p_id"=>$iv,
                        "update"=>0,
                    ];
                    db::name("cash_item")->insert($item_array);
                }
            }
            $cash_attr = [
                "cash_id"=>$id,
                "update"=>0,
                "update_time"=>time(),
                "update_id"=>session("admin_user_auth")['uid'],
                "update_name"=>db::name("admin")->where("userid",session("admin_user_auth")['uid'])->value("nickname"),
                "item"=>$item,
                "service"=>$service,
                "ticket"=>$ticket,
                "title"=>$res['title'],
                "price"=>$res['price'],
                "num"=>$res['num'],
                "days"=>$res['days'],
                "time_num"=>$res['time_num'],
                "o_price"=>$o_price,
                /*"end_time"=>$*/
                "button_title"=>$button_title,
            ];
            db::name("cash_attr")->insert($cash_attr);
            if($service == 1){
                foreach($res['service'] as $sk => $sv){
                    $service_array =[
                        "cash_id"=>$id,
                        "type"=>"service",
                        "p_id"=>$sv,
                        "update"=>0,
                    ];
                    db::name("cash_item")->insert($service_array);
                }
            }
            if($ticket  == 1){
                 foreach($res['ticket'] as $tk => $tv){
                    $ticket_array =[
                        "p_id"=>$tv,
                        "type"=>"ticket",
                        "cash_id"=>$id,
                        "update"=>0,
                    ];
                    db::name("cash_item")->insert($ticket_array);
                }
            }
            $db->commit();
            return json(['result'=>true,"msg"=>"添加成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
       
    }
    public function getButtonTitle($item,$service,$ticket){
        $item = intval($item);
        $service = intval($service);
        $ticket = intval($ticket);
        if($item == 2 && $service == 2 && $ticket == 2){
            return "全部可用";
        }else if($item == 2 && $service == 2 && $ticket == 0){
            return "全部商品和服务可用";
        }else if($item == 2 && $service == 0 && $ticket == 2){
            return "全部商品和服务卡可用";
        }else if($item == 0 && $service == 2 && $ticket == 2){
            return "全部服务和服务卡可用";
        }else if($item == 2 && $service == 0 && $ticket == 0){
            return "全部商品可用";
        }else if($item == 0 && $service == 2 && $ticket == 0){
            return "全部服务可用";
        }else if($item == 0 && $service == 0 && $ticket == 2){
            return "全部服务卡可用";
        }else if($item == 1 && $service == 0 && $ticket == 0){
            return "仅部分商品可用";
        }else if($item == 0 && $service == 1 && $ticket == 0){
            return "仅部分服务可用";
        }else if($item == 0 && $service == 0 && $ticket == 1){
            return "仅部分服务卡可用";
        }else{
            return "只有部分可用";
        }
    }
    public function product_list($res){
        $cash = db::name("cash")->where("id",intval($res['data']))->find();
        if($res['type'] == "item"){
            $data = db::name("cash_item")
            ->alias("a")
            ->where("a.cash_id",intval($res['data']))
            ->where("a.type",$res['type'])
            ->where("a.update",intval($cash['update']))
            ->field("i.title")
            ->join("item i","a.p_id = i.id")
            ->select();
        }else if($res['type'] =="ticket"){
            $data = db::name("cash_item")
            ->alias("a")
            ->where("a.cash_id",intval($res['data']))
            ->where("a.type",$res['type'])
            ->where("a.update",intval($cash['update']))
            ->field("t.card_name as title")
            ->join("ticket_card t","a.p_id=t.id")
            ->select();
        }else if($res['type'] == "service"){
            $data = db::name("cash_item")
            ->alias("a")
            ->where("a.cash_id",intval($res['data']))
            ->where("a.type",$res['type'])
            ->where("a.update",intval($cash['update']))
            ->field("s.sname as title")
            ->join("service s","a.p_id = s.id")
            ->select();
        }
        return $data;
    }
    public function edit($res){
        $cash = db::name("cash")->where("id",intval($res['id']))->find();
        $o_price = $res['other'] ==1?$res['other_price']:0;
        if(!isset($res['product']) || empty($res['product'])){
            return json(['result'=>false,"msg"=>"请选择所属项目","data"=>"product=null"]);
        }
        $update = $cash['update'] +1;
        $product =$res['product'];
        $item = 0;
        $service = 0;
        $ticket  = 0;
        for($i = 0;$i<count($product);$i++){
            $array = $product[$i];
            if($array == "item"){
                $item = $res[$array."_select"];
            }
            if($array == "service"){
                $service = $res[$array."_select"];
            }
            if($array == "ticket"){
                $ticket = $res[$array."_select"];
            }
            $title = $array=="item"?"商品":($array=="ticket"?"服务卡":"服务");
            if($res[$array."_select"] =="1" && count($res[$array]) ==0){
                return json(['result'=>false,'msg'=>"请选择".$title,"data"=>""]);
            }
        }
        $button_title = $this->getButtonTitle($item,$service,$ticket);
        $cash_attr = [
            "cash_id"=>intval($res['id']),
            "title" =>$res['title'],
            "update"=>$update,
            "update_time"=>time(),
            "update_id"=>session("admin_user_auth")['uid'],
            "update_name"=>db::name("admin")->where("userid",session("admin_user_auth")['uid'])->value("nickname"),
            "item"=>$item,
            "service"=>$service,
            "ticket"=>$ticket,
            "price"=>$res['price'],
            "num"=>$res['num'],
            "days"=>$res['days'],
            "time_num"=>$res['time_num'],
            "o_price"=>$o_price,
            "remarks"=>$remarks,
            "button_title"=>$button_title,
        ];
        $db = db::name("cash_attr");
        $db->startTrans();
        try{
            db::name("cash")->where("id",intval($res['id']))->update(['update'=>$update]);
            $id = db::name("cash_attr")->insertGetId($cash_attr);
            if($item    == 1){
                foreach($res['item'] as $ik => $iv){
                    $item_array =[
                        "cash_id"=>$cash['id'],
                        "type"=>"item",
                        "p_id"=>$iv,
                        "update"=>$update,
                    ];
                    db::name("cash_item")->insert($item_array);
                }
            }
            if($service == 1){
                foreach($res['service'] as $sk => $sv){
                    $service_array =[
                        "cash_id"=>$cash['id'],
                        "type"=>"service",
                        "p_id"=>$sv,
                        "update"=>$update,
                    ];
                    db::name("cash_item")->insert($service_array);
                }
            }
            if($ticket  == 1){
                 foreach($res['ticket'] as $tk => $tv){
                    $ticket_array =[
                        "p_id"=>$tv,
                        "type"=>"ticket",
                        "cash_id"=>$cash['id'],
                        "update"=>$update,
                    ];
                    db::name("cash_item")->insert($ticket_array);
                }
            }
            $db->commit();
            return json(['result'=>true,"msg"=>"修改成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
}
