<?php

// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------

namespace app\admin\model\ticket;

use think\Model;

use think\Db;
class TicketModel extends Model {
    protected $table = 'ddxm_ticket_card';
    public function shop() {
        
        $data= db::name("shop")->field("id,name")->select();
        return $data;
    }
    public function level(){
        $data= Db::name("member_level")->field("id,level_name")->select();
        return $data;
    }
    //添加服务卡
    public function add_ticket($data){
        $data = $data['data'];
        //数据建模
        if($this->setTime($data['start_time'])>$this->setTime($data['end_time'])){
            $msg = ['result'=>false,"msg"=>"开始时间大于结束时间","data"=>""];
            return $msg;
        }
        if(empty($data['shop_id'])){
            return (['result'=>false,"msg"=>"请选择门店信息","data"=>""]);
        }
        $ticket['card_name'] = $data['card_name'];
        $ticket['cover']  = "";
        $ticket['critulation'] = $data['citulation'];
        $ticket['exchange_num'] = $data['citulation'];
        $ticket['restrict_num'] = $data['restrict_num'];
        $ticket['day'] = intval($data['over_day']);
        $ticket['use_day'] = isset($data['use_day'])?intval($data['use_day']):0;
        $ticket['month'] = isset($data['over_month'])?intval($data['over_month']):0;
        $ticket['year'] = isset($data['over_year'])?intval($data['over_year']):0;
        $ticket['start_time'] = $this->setTime($data['start_time']);
        $ticket['end_time'] = ($this->setTime($data['end_time']))==0?0:($this->setTime($data['end_time']))+86399;
        $ticket['integral_price'] =0;
        $ticket['create_time'] = time();
        $ticket['status'] = 1;
        $ticket['creator_id'] = session("admin_user_auth")['uid'];
        $ticket['type'] = $data['type'];
        $ticket['all_shop'] = isset($data['all_shop'])?$data['all_shop']:'0';
        $res = $this->insertGetId($ticket);
        $ticket_service = $this->add_ticket_service($data,intval($res));
        if(!$ticket_service){
            $msg =["result"=>false,"msg"=>'服务器繁忙!请稍后重试','data'=>""];
            return $msg;
        }

        $ticket_shop = $this->add_ticket_shop($data,intval($res));
        if(!$ticket_shop){
            $msg = ["result"=>false,"msg"=>'服务器繁忙!请稍后重试','data'=>""];
            return $msg;
        }
        $ticket_price = $this->add_ticket_price($data,intval($res));
        if(!$res){
            $msg = ["result"=>false,"msg"=>'服务器繁忙!请稍后重试','data'=>""];
            return $msg;
        }else{
            return ["result"=>true,'msg'=>"添加成功","data"=>""];
        }
    }
    public function add_ticket_price($data,$id){
        foreach($data['level_id'] as $k=>$v){
            $ticket['card_id'] = $id;
            $ticket['level_id'] = $v;
            $ticket['price'] = $data['price'][$k];
            $ticket['mprice'] = empty($data['mprice'][$k])?$data['price'][$k]:$data['mprice'][$k];
            $ticket['level_name'] = $data['level_name'][$k];
            $res = Db::name("ticket_money")->insert($ticket);
            if(!$res){
                return false;
            }
        }
        return true;
    }
    public function add_ticket_shop($data,$id){
        foreach($data['shop_id'] as $k =>$v){
            $shop['shop_id'] = $v;
            $shop['card_id'] = $id;
            $shop['shop_name'] = $data['shop_name'][$k];
            $res = db::name("ticket_shop")->insert($shop);
            if(!$res){
                return false;
            }
        }
        return true;
    }
    public function setTime($val){
        if($val ==""){
            return 0;
        }
        $val = str_replace("年","-",$val);
        $val = str_replace("月","-",$val);
        $val = str_replace("日","",$val);
        return strtotime($val);
    }
    public function add_ticket_service($data,$id){
        foreach($data['service_name'] as $k=>$v){
            $ticket['card_id'] = $id;
            $ticket['service_id'] = $data['service_id'][$k];
            $ticket['service_name'] = $v;
            $ticket['num'] = isset($data['service_num'][$k])?$data['service_num'][$k]:0;
            $ticket['day'] = $data['day'][$k];
            $ticket['month'] = $data['month'][$k];
            $ticket['year'] = $data['year'][$k];
            $sid = Db::name("ticket_service")->insertGetId($ticket);
            $other = $this->ticket_other($data,intval($sid),$k);
            if(!$other){

                return false;
            }
            $level_price = $this->level_price($data,$sid,$k);
            if(!$level_price){
                return fasle;
            }
        } 
        return true;
    }
    public function ticket_other($data,$id,$k){
        $other_time = $data['other_time'][$k];
        $other_num  = $data['other_num'][$k];
        foreach($other_time as $key =>$val){
            $other['start_time'] = ($this->setTime(explode("~",$val)[0]));
            $other['end_time'] = ($this->setTime(explode("~",$val)[1]))==0?0:($this->setTime(explode("~",$val)[1]))+86399;
            $other['num'] = $other_num[$key];
            $other['ticket_service_id'] = $id;
            $res = db::name("ticket_other_restrictions")->insert($other);
            if(!$res){
                return false;
            }
        }
        return true;
    }
    public function level_price($data,$id,$k){
        foreach($data['level_id'] as $key=>$val){
            $ticket['level_id'] = $val;
            $ticket["level_name"] = $data['level_name'][$key];
            $ticket['ts_id'] = $id;
            $ticket['price'] = $data['service_level_price'][$key][$k];
            $res = db::name("ticket_service_money")->insert($ticket);
            if(!$res){
                return false;
            }
        }
        return true;
    }
    public function ticket_list($res,$where){
        return $this->where($where)
        ->field("id,card_name,cover,critulation,restrict_num,start_time,end_time,create_time,status,update_time,use_day,type,all_shop,day,month,year,type")
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
        ->order("create_time","desc");
    }
    public function getCoverAttr($val){
        if($val==null){

        }
    }
    public function getStartTimeAttr($val){
        if(!empty($val)){
            return date("Y-m-d",$val);
        }
        if($val ==0){
            return "无限制";
        }
        return "暂无数据";       
    }
    public function getEndTimeAttr($val){
        if(!empty($val)){
            return date("Y-m-d",$val);
        }
        if($val ==0){
            return "无限制";
        }
        return "暂无数据";       
    }
    public function getCreateTimeAttr($val){
        if(!empty($val)){
            return date("Y-m-d H:i:s",$val);
        }
        return "暂无数据";       
    }
    public function getUpdateTimeAttr($val){
        if(!empty($val)){
            return date("Y-m-d H:i:s",$val);
        }
        return "未修改";       
    }
    public function getTypeAttr($val,$data){
        switch ($val){
            case "1":
                $data = '次卡/'.$data['use_day'];
                break;  
            case "2":
                $data = '月卡/'.$data['month'];
                break;
            case "3":
                $data = '季卡';
                break;
            case "4":
                $data = '年卡/'.$data['year'];
                break;
            default:
                $data = "数据错误";
        }
        return $data;
    }
    public function getStatusAttr($val){
        if(empty($val)){
            return "下架";
        }
        return "上架";
    }
    public function getshop($val){
        $data =db::name("shop")->field("id,name")->select();
        foreach($data as $key =>$value){
            $res = Db::name("ticket_shop")->where("shop_id",$value['id'])->where("card_id",$val)->find();
            if($res){
                $data[$key]['check'] = "checked";
            }else{
                $data[$key]["check"] = "";
            }
        }
        return $data;
    }
}
