<?php
// +----------------------------------------------------------------------
// | 服务卡模块
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\admin\model\cash\CashModel;
use app\common\controller\Adminbase;
use app\admin\model\Adminlog;
use think\Db;
use think\db\Where;
class Cash extends Adminbase{

    public function index(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            return $this->fetch();
        }else{
            $search = $res['search'];
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $model = new CashModel();
            $where = [];
            if(isset($res['search']) && !empty($res['search'])){
               
                $where[] =['title',"like","%{$search}%"];
            }
            $where[] =['del',"=",0];
            $data = $model->index($res)->where($where)
            ->withAttr("days",function($value,$data){
                if($data['days'] =="day"){
                    $day = "天";
                }else if($data['days'] =="month"){
                    $day = "月";
                }
                $html = $data['time_num'].$day;
                return $html;
            })
            ->withAttr("create_time",function($value,$data){
                return date("Y-m-d H:i:s",$data['create_time']);
            })
            ->withAttr("status",function($value,$data){
                if($data['status'] ==1){
                    return "正常";
                }
                if($data['status']==2){
                    return "关闭";
                }
            })
            ->field("id,title,price,item,service,ticket,num,update,days,time_num,o_price,remarks,code,sn,create_time,creator,creator_id,end_time,state,refund,del,state as status")
            ->page($page,$limit)
            ->order("create_time","desc")
            ->select();
            $data  = $model->getIndex($data);
            $count = $model->index($res)->where($where)->count();
            return json(["code" => 0, "count" => $count, "data" => $data]);
        }
    }
    public function add(){
        /*echo (date("Y-m-d H:i:s",time()));exit;*/
        if($this->request->isAjax()){
            $res = $this->request->post();
            $model = new CashModel();
            $data = $model->add($res);
            return $data;
        }else{
            return $this->fetch();
        }
    }
    //获取可用产品
    public function product(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d', 10);
        $model = new CashModel();
        $where = [];
        if(isset($res['item']) && !empty($res['item'])){
            $where[] =['id',"not in",$res['item']];
        }
        $data = $model->product($res)->where($where)->page($page,$limit)->select();
        $count = $model->product($res)->where($where)->count();
        $result = array("code" => 0, "count" => $count, "data" => $data);
        return json($result);
    }
    //部分商品信息
    public function product_list(){
        $res = $this->request->post();
        $model = new CashModel();
        $data = $model->product_list($res);
        if($data){
            return json(['result'=>true,"msg"=>"获取成功","data"=>$data]);
        }else{
            return json(['result'=>false,"msg"=>"数据错误,联系管理员",'data'=>$data]);
        }
    }
    public function del(){
        $res = $this->request->post();
        $id =intval($res['id']);
        $cash = [
            "del"=>1,
            "del_time"=>time(),
            "del_operator"=>db::name("admin")->where("userid",session("admin_user_auth")['uid'])->value("nickname"),
            "del_operator_id"=>session("admin_user_auth")['uid'],
        ];
        $result = db::name("cash")->where("id",$id)->update($cash);
        if($result){
            return json(['result'=>true,"msg"=>'删除成功','data'=>""]);
        }else{
            return json(['result'=>false,"msg"=>"系统错误，请稍后重试","data"=>""]);
        }
    }
    //服务卷分发管理
    public function manage(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            return $this->fetch();
        }else{
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            if(isset($res['search']) && !empty($res['search'])){
                $search = $res['search'];
                $where[] =['title|mobile',"like","%{$search}%"];
            }
            $model = new CashModel();
            $data = $model->manage()->where($where)->page($page,$limit)->order("create_time","desc")->select();
            $count = $model->manage()->where($where)->count();
            $result = array("code" => 0, "count" => $count, "data" => $data);
            return json($result);
        }
    }
    //服务劵分发
    public function manage_add(){
        if($this->request->isAjax()){
            $res = $this->request->post();
            $data = db::name("cash")->where("id",intval($res['cash_id']))->where("state",1)->where("del",0)->find();
            if(!$data){
                return json(['result'=>false,'msg'=>"数据有变化，请稍后重试",'data'=>"代金券不存在或已关闭"]);
            }
           /* if($data['update']){*/
            $data_attr = db::name("cash_attr")->where("cash_id",intval($res['cash_id']))->where("update",intval($data['update']))->field("cash_id as id,update,update_time,update_id,update_name,item,service,ticket,title,price,num,days,time_num,o_price,remarks,end_time,button_title")->find();
            $data_attr['create_time'] = $data['create_time'];
            $data_attr['creator_id'] = $data['creator_id'];
            $data_attr['creator'] = $data['creator'];
            $data_attr['state'] = $data['state'];
            if($data_attr['day'] =="day"){
                $day = "天";
            }else if($data_attr['day'] =="month"){
                $day = "月";
            }
            $data_attr['day'] = $data_attr['time_num'].$day;
            $cash_attr = $data_attr;
            /*}else{
                $cash_attr = $data;
            }*/
            if($cash_attr['title'] != $res['cash_title']){
                return json(['result'=>false,'msg'=>"数据有变化，请稍后重试",'data'=>"代金券名称变化"]);
            }
            $member = db::name("member")->where("mobile",$res['phone'])->find();
            if(!$member){
                return json(['result'=>false,"msg"=>"该手机号不是会员",'data'=>""]);
            }
            $over_time = strtotime('+'.$cash_attr["time_num"].$cash_attr['days']);
            $over_time =strtotime(date("Y-m-d",$over_time)." +1 day") -1;
            $cash = [
                "title"=>$cash_attr['title'],
                "price" =>$cash_attr['price'],
                "update" =>$cash_attr['update'],
                "o_price"=>$cash_attr['o_price'],
                "member_id"=>$member['id'],
                "mobile"=>$member['mobile'],
                "type"=>1,
                "cash_id"=>$cash_attr['id'],
                "over_time"=>$over_time,
                "state"=>0,
                "create_time"=>time(),
                "s_time"=>0,
                "button_title"=>$data_attr['button_title'],
            ];
            $db = db::name("cash_user");
            $db->startTrans();
            try{
                for($i =0;$i<intval($res['num']);$i++){
                    db::name("cash_user")->insert($cash);
                }
                $db->commit();
                return json(['result'=>true,"msg"=>"发放成功","data"=>$res['num']]);
            } catch (Exception $e) {
                $db->rollback();
                $error = $e->getMessage();  
                return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
            }
        }else{
            $res = $this->request->get();
            return $this->fetch();
        }
    }
    public function cash_list(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d', 10);
        $where =[];
        $where[] = ['state',"=",1];
        $where[] = ['del',"=",0];
        $search = $res['search'];
        if(isset($res['search']) && !empty($res['search'])){               
            $where[] =['title',"like","%{$search}%"];
        }
        $data = db::name("cash")
        ->where($where)
        ->withAttr("o_price",function($value,$data){
            if($data['o_price'] ==0){
                return "无限制";
            }
            return $data['o_price'];
        })
        ->page($page,$limit)
        ->select();
        $model = new CashModel();
        $data = $model ->getIndex($data);
        $count = db::name("cash")->where($where)->count();
        $result = array("code" => 0, "count" => $count, "data" => $data);
        return json($result);
    }
    public function edit(){
        if($this->request->isAjax()){
            $res = $this->request->post();
            $model = new CashModel();
            $data = $model->edit($res);
            return $data;
        }else{
            $res = $this->request->get();

            $data = db::name("cash")->where("id",intval($res['id']))->find();
            /*if($data['item'] =="1"){
                $data['item_array'] = db::name("cash_item")
                ->alias("a")
                ->field("i.title,i.id")
                ->where("a.cash_id",$data['id'])->where("a.type","item")->where("a.update",0)
                ->join("item i","a.p_id = i.id")
                ->select();
            }else{
                $data['item_array'] = "";
            }
            if($data['service'] ==1){

                $data['service_array'] = db::name("cash_item")
                ->alias("a")
                ->field("i.sname as title,i.id")
                ->where("a.cash_id",$data['id'])->where("a.type","service")->where("a.update",0)
                ->join("service i","a.p_id = i.id")
                ->select();
            }else{
                $data['service_array'] = "";
            }
            if($data['ticket'] ==1){
                $data['ticket_array'] = db::name("cash_item")
                ->alias("a")
                ->field("i.card_name as title,i.id")
                ->where("a.cash_id",$data['id'])->where("a.type","ticket")->where("a.update",0)
                ->join("ticket_card i","a.p_id = i.id")
                ->select();
            }else{
                $data['ticket_array'] = "";
            }*/
            $data_attr = db::name("cash_attr")->where("cash_id",intval($res['id']))->where("update",$data['update'])->field("cash_id as id,update,update_time,update_id,update_name,item,service,ticket,title,price,num,days,time_num,o_price,remarks,end_time")->find();
            $data_attr['refund'] = $data['refund'];

            if($data_attr['item'] ==1){
                $data_attr['item_array'] = db::name("cash_item")
                ->alias("a")
                ->field("i.title,i.id")
                ->where("a.cash_id",$data_attr['cash_id'])->where("a.type","item")->where("a.update",intval($data['update']))
                ->join("item i","a.p_id = i.id")
                ->select();
            }else{
                $data_attr['item_array'] = "";
            }
            if($data_attr['service'] ==1){
                $data_attr['service_array'] = db::name("cash_item")
                ->alias("a")
                ->field("i.sname as title,i.id")
                ->where("a.cash_id",$data_attr['cash_id'])->where("a.type","service")->where("a.update",intval($data['update']))
                ->join("service i","a.p_id = i.id")
                ->select();
            }else{
                $data_attr['service_array'] = "";
            }
            if($data_attr['ticket'] ==1){
                $data_attr['ticket_array'] = db::name("cash_item")
                ->alias("a")
                ->field("i.card_name as title,i.id")
                ->where("a.cash_id",$data_attr['cash_id'])->where("a.type","ticket")->where("a.update",intval($data['update']))
                ->join("ticket_card i","a.p_id = i.id")
                ->select();
            }else{
                $data_attr['ticket_array'] = "";
            }
            $this->assign("data",$data_attr);
            
            return $this->fetch();
        }
    }
}
