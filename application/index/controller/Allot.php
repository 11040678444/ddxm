<?php
namespace app\index\controller;
use app\common\controller\Adminbase;
use app\index\model\allot\AllotModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
/**
 * 出库管理模块
 */
class Allot extends Base
{
    //调拨单
    public function index(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        $res['limit'] = $this->request->param('limit/d', 10);
        $res['page'] = $this->request->param('page/d', 1);
        $allot = new AllotModel();
        $array = $allot->allot_index($res,$shop_id);
        $data =$array['data'];
        $count =$array['count'];
        if($data){
            return json(["code"=>"200","count"=>$count,"data"=>$data]);
        }else{
            return json(["code"=>"200","count"=>$count,"msg"=>"暂无数据","data"=>$data]);
        }
        
    }
    //新增调拨单
    public function allot_add(){
        if($this->request->isPost()){
            $res = $this->request->post();
            $res['shop_id'] = $this->getUserInfo()['shop_id'];

            if(intval($res['data']['allot_in']) == intval($res['shop_id']))
            {
                return json(['code'=>"300","msg"=>"调入仓库不能与调出仓库一直","data"=>""]);
                exit;
            }

            $allot = new AllotModel();
            $data = $allot->allot_add($res);
            return $data;
        }else{
            $data['shop'] = Db::name("shop")->field("id,name")->select();
            $data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->field("id,cname")->select();
            $this->assign("data",$data);
            return $this->fetch();
        }
    }
    //调拨单商品信息
    public function allot_item(){
        $res = $this->request->post();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $shop_id = $this->getUserInfo()['shop_id'];
        $where = [];
        $where[] = ['a.status','=',1];

        if(isset($res['name']) && !empty($res['name'])){
            $where[] = ['a.bar_code',"like","%{$res['name']}%"];
        }
        if(isset($res['id']) && !empty($res['id'])){
            $id = explode(",",$res['id']);
            $where[] =['a.id',"not in",$id];
        }
        if(isset($res['code']) && !empty($res['code'])){
            $where[] = ['a.title',"like","%{$res['code']}%"];
        }
        if(isset($res['parent']) && !empty($res['parent'])){
            if(isset($res['child']) && !empty($res['child'])){
                $where[] = ['a.type',"=",intval($res['child'])];
            }else{
                $where[] = ['i.pid',"=",intval($res['parent'])];
            }
        }
        $where[] =["s.shop_id","=",intval($shop_id)];
        $where[] =["s.stock",">",0];
        $data = db::name("item")
            ->alias("a")
            ->where($where)
            ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status,s.stock")
            ->withAttr("p_type",function($value,$data){
                return db::name("item_category")->where("id",$data['pid'])->value("cname");
            })
            ->join("shop_item s","a.id=s.item_id")
            ->join("item_category i","a.type = i.id")
            ->page($page,$limit)
            ->select();
        $count = db::name("item")
            ->alias("a")
            ->where($where)
            ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status")
            ->join("shop_item s","a.id=s.item_id")
            ->join("item_category i","a.type = i.id")->count();
        if($data){
            return json(["code"=>200,"count"=>$count,"data"=>$data]);
        }else{
            return json(["code"=>200,"count"=>$count,"msg"=>"暂无数据","data"=>$data]);
        }
    }
    //获取商品分类
     public function item_category(){
        $res = $this->request->post();
        $id =!isset($res['id'])?0:intval($res['id']);
        $data = db::name("item_category")->where("pid",$id)->where("status",1)->field("id,cname")->select();
        $count = count($data);
        if($data){
            return json(['result'=>true,"msg"=>"查询成功","data"=>$data,"count"=>$count]);
        }else{
            return json(['result'=>false,"msg"=>"系统繁忙","data"=>""]);
        }
    }
    //调拨单出库列表
    public function  out_shop_list(){
        $res = $this->request->post();
        $id = intval($res['id']);
        if(!$id){
            return json(['code'=>"-3","msg"=>"数据错误","data"=>""]);
        } 
        $allot = new AllotModel();
        $data = $allot->out_shop_list($id);
        if($data){
            return json(['code'=>"200","msg"=>"请求成功","data"=>$data]);
        }else{
            return json(['code'=>"500","msg"=>"服务器错误,请稍后重试!","data"=>""]);
        }
    }
    //调拨单调拨时商品列表
    public function out_shop_item(){
        $res= $this->request->post();
        $id = $res['id'];
        if(!$id){
            return json(['code'=>"500","msg"=>"数据错误",'data'=>""]);
        }
        $data = db::name("allot_item")
        ->alias("a")
        ->where("a.allot_id",$id)
        ->field("a.item,a.item_id,a.num,i.bar_code")
        ->join("item i","a.item_id = i.id")
        ->select();
        $count = db::name("allot_item")
        ->where("allot_id",$id)
        ->count();
        return json(['code'=>200,'count'=>$count,"data"=>$data]);
    }
    //出库
    public function out_shop(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->out_shop(intval($res['id']),$res['worker_id']);
        return $data;
    }
    //确认收货列表
    public function allot_confirm_list(){
        $res = $this->request->post();
        $id = intval($res['id']);
        $data = Db::name("allot")->where("id",$id)
        ->withAttr("create_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        ->withAttr("out_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['out_time']);
        })
        ->withAttr("out_shop",function($value,$data){
            return db::name("shop")->where("id",$data['out_shop'])->value("name");
        })
        ->withAttr("in_shop",function($value,$data){
            return db::name("shop")->where("id",$data['in_shop'])->value("name");
        })
        ->withAttr("in_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['in_time']);
        })
        ->withAttr("in_admin_user",function($value,$data){
            if($data['in_admin_id'] >0){
                return $data['in_admin_user'];
            }else if($data['in_admin_id'] ==0){
                return "到期时间";
            }else{
                return "<b style='color:red'>系统自动收货<b>";
            }       
        })
        ->find();
        if($data){
            return json(['code'=>200,"msg"=>"获取成功","data"=>$data]);
        }else{
            return json(['code'=>200,"msg"=>"系统繁忙，数据错误","data"=>""]);
        }
        
    }
    public function allot_confirm_list_table(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->allot_confirm_list(intval($res['id']));
        return $data;
    }
    // 门店确认收货
    public function confirm_shop(){
        $res = $this->request->post();
        $shop_id = $this->getUserInfo()['shop_id'];
        $allot = new AllotModel();
        $data = $allot->confirm_shop($res,$shop_id);
        return $data;
    }
    // 取消发货
    public function shop_cancel(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->shop_cancel($res);
        return $data;
    }
    //删除调拨单
    public function del(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->del($res);
        return $data;
    }
    //打印数据
    public function prints(){
        $res = $this->request->post();
        $data = db::name("allot")
        ->where("id",intval($res['id']))
        ->field("sn,out_shop,in_shop,create_time,id,remark")
        ->withAttr("out_shop",function($value,$data){
            return db::name("shop")->where("id",$data['out_shop'])->value("name");
        })
        ->withAttr("in_shop",function($value,$data){
            return db::name("shop")->where("id",$data['in_shop'])->value("name");
        })
        ->withAttr("create_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        ->withAttr("worker",function($value,$data){
            return $this->getUserInfo()["name"];
        })
        ->withAttr("item",function($value,$data){
            return db::name("allot_item")
            ->alias("a")
            ->where("allot_id",$data['id'])
            ->field("a.item,a.num,a.item_id,i.bar_code")
            ->join("item i","a.item_id = i.id")
            ->select();
        })
        ->find();
        if($data){
            return json(['code'=>"200","msg"=>"获取成功","data"=>$data]);
        }else{
            return json(['code'=>"200","msg"=>"暂无数据","data"=>""]);
        }
    }
}
