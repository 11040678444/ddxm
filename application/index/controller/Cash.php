<?php
/**

 服务卡控制器
*/
namespace app\index\controller;
use app\index\model\cash\CashModel;
use app\index\model\log;
use app\index\model\Adminlog;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
class Cash extends Base{

    //获取可选择的代金券列表
    public function member_cash(){
        $res  = $this->request->post();
        $data = $res['data'];
        $member_id = $res['member_id'];
        if(!isset($member_id) || empty($member_id)){
            return json(['code'=>"-3","msg"=>"会员信息错误","data"=>"会员信息为空"]);
        }
        $shop_id = $this->getUserInfo()["shop_id"];
        $shop_code = db::name("shop")->where("id",$shop_id)->value("code");
        $member = db::name("member")->where("id",$member_id)->find();
        if($member['shop_code'] !== $shop_code){
            return json(["code"=>"-3","msg"=>"不是本门店会员","data"=>""]);
        }
        $cash = db::name('cash_user')->where("member_id",$member_id)->select();//->where("state","=",0)->where("over_time",">",time())
        if($data){
            $data = $this->getCashMemberNumber($data,$cash);
        }
        $count = count($cash);
        if($count){
            return json(["code"=>"200","msg"=>"获取成功","data"=>$data,"count"=>$count]);
        }else{
            return json(['code'=>"200","msg"=>'暂无数据',"data"=>$data,"count"=>$count]);
        }      
    }
    public function getCashMemberNumber($data,$cash){
        $count = 0;
        foreach($cash as $k => $v){
            if($v['state'] ==1){
                $cash[$k]['r_state'] =0;//已使用
                continue;
            }
            if($v['over_time']<time()){
                $cash[$k]['r_state'] =1;//已过期
                continue;
            }
            $cash_attr = db::name("cash_attr")->where("cash_id",$v['cash_id'])->where("update",$v['update'])->find();
            $dataArray = $this->getDataId($data,$cash_attr);
            if($dataArray){
                $cash[$k]['r_state'] =3;//可用
            }else{
                $cash[$k]['r_state'] =2;//不可用
            }
        }
        return $cash;
    }
    //获取代金券列表
    public function cash_list(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()["shop_id"];
        $shop_code = db::name("shop")->where("id",$shop_id)->value("code");
        $member = db::name("member")->where("mobile",$res['mobile'])->find();
        if($member['shop_code'] !== $shop_code){
            return json(["code"=>"-3","msg"=>"不是本门店会员","data"=>""]);
        }
        $state = isset($res['state'])?intval($res['state']):0;
        $where =[];
        $where[] = ['member_id',"=",$member['id']];
        /*$where[] = ['over_time',">",time()];
        $where[] = ['state',"=",$state];*/
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $data = db::name("cash_user")->where($where)->field("id,title,over_time,state,update,price,o_price,create_time,button_title,cash_id")
        ->withAttr("over_time",function($value,$data){
            return date("Y-m-d",$data["over_time"]);
        })
        ->withAttr("create_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        ->page($page,$limit)->select();
        $count = db::name("cash_user")->where($where)->count();
        if($data){
            return json(['code'=>"200","count"=>$count,"data"=>$data,"msg"=>"获取成功"]);
        }else{
            return json(['code'=>"200","count"=>$count,"data"=>$data,"msg"=>"暂无数据"]);
        }
    }
    //选择商品后刷新可用代金券数量
    public function item_member_cash(){
        $res  = $this->request->post();
        $data = $res['data'];
        $member_id = $res['member_id'];
        if(!isset($member_id) || empty($member_id)){
            return json(['code'=>"-3","msg"=>"会员信息错误","data"=>"会员信息为空"]);
        }
        $shop_id = $this->getUserInfo()["shop_id"];
        $shop_code = db::name("shop")->where("id",$shop_id)->value("code");
        $member = db::name("member")->where("id",$member_id)->find();
        if($member['shop_code'] !== $shop_code){
            return json(["code"=>"-3","msg"=>"不是本门店会员","data"=>""]);
        }
        $cash = db::name('cash_user')->where("member_id",$member_id)->where("state","=",0)->where("over_time",">",time())->select();
        if($data){
            $count = $this->getCashNumber($data,$cash);
        }else{
            $count = count($cash);
        }
        if($count){
            return json(["code"=>"200","msg"=>"获取成功","data"=>"","count"=>$count]);
        }else{
            return json(['code'=>"200","msg"=>'暂无数据',"data"=>"","count"=>$count]);
        }
    }
    // 根据根据传递的用户数据和代金券数据进行匹配
    public function getCashNumber($data,$cash){
        $count = 0;
        foreach($cash as $k => $v){
            $cash_attr  = db::name("cash_attr")->where("cash_id",$v['cash_id'])->where("update",$v['update'])->find();
            $dataArray = $this->getDataId($data,$cash_attr);
            if($dataArray){
                $count ++;
            }
        }
        return $count;
    }
    // 每个代金券和传递的商品数据进行匹配
    public function getDataId($data,$cash_attr){
        $total = 0;
        foreach($data as $k=>$v){
            if($cash_attr[$v['type']]==0){
                continue;
            }else if($cash_attr[$v['type']]==1){
                if(db::name("cash_item")->where("cash_id",$cash_attr['cash_id'])->where("type",$v['type'])->where("p_id",$v['id'])->where("update",$cash_attr['update'])->find()){
                    $total += $v['amount'];
                }
            }else if($cash_attr[$v['type']]==2){
                $total +=$v['amount'];
            } 
        }
        if($total>=$cash_attr['o_price']){
            return true;
        }else{
            return false;
        }
    }
    public function consume_cash($cash){
        $db = db::name("cash_user");
        $db->startTrans();
        try{
            $state = true;
            foreach($cash as $k =>$v){
                $data['state'] = 1;
                $data['s_time'] = time();
                $res = db::name("cash_user")->where("id",intval($v))->update($data);
                if(!$res){
                    $state = false;
                    break;
                }
            }
            if($state){
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return false;
        }
    }
}