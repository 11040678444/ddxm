<?php
namespace app\admin\controller;
use app\common\controller\Adminbase;
use app\admin\model\refund\RefundModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
class Refund extends Adminbase{
    //退货单列表
    public function index(){
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            return $this->fetch();
        }else{
            $refund = new RefundModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $result = db::name("reject")->where("del",1)->order("create_time","desc")->page($page,$limit)->select();
            $data = $refund->index($result);
            $count =db::name("reject")->where("del",1)->count();
            return json(["code"=>0,"count"=>$count,"data"=>$data]);
        }
    }
    //新增退货单
    public function add_refund(){
        if($this->request->isPost()){
            $res = $this->request->post();
            $refund = new RefundModel();
            $data = $refund->add_refund($res);
            return $data;
        }else{
            $data['supplier'] = db::name("supplier")->where("del",0)->field("supplier_name as name,id")->select();
            $data['shop']  = db::name('shop')->field("name,id")->select();
            $data['item'] = DB::name("item_category")->where("pid",0)->where("status",1)->select();
            $this->assign("data",$data);
            return $this->fetch();
        }
    }
    //发货数据
    public function out_refund(){
        $res = $this->request->post();
        $data = db::name("reject")
        ->where("id",intval($res['id']))
        ->withAttr("shop",function($value,$data){
            return Db::name("shop")->where("id",$data['shop_id'])->value("name");
        })
        ->withAttr("create_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        ->find();
        if($data){
            return json(['result'=>true,'msg'=>"获取成功","data"=>$data]);
        }else{
            return json(['result'=>false,"msg"=>"系统繁忙","data"=>""]);
        }
    }
    //已退货商品
    public function out_item(){
        $res = $this->request->get();
        $id = intval($res['id']);
        $data = db::name("reject_item")
        ->where("reject_id",$id)
        ->withAttr("item",function($value,$data){
            return db::name("item")->where("id",$data['item_id'])->value("title");
        })
        ->withAttr("bar_code",function($value,$data){
            return Db::name("item")->where("id",$data['item_id'])->value("bar_code");
        })
        ->select();
        $count = db::name("reject_item")->where("reject_id",$id)->count();
        return json(['code'=>0,"data"=>$data,"count"=>$count]);
    }
    //退货商品
    public function refund_item(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $shop_id = $res['shop'];
        $where = [];
        $where[] = ['a.status','=',1];

        if(isset($res['data']['name']) && !empty($res['data']['name'])){
           $where[] = ['a.title',"like","%{$res['data']['name']}%"]; 
        }
        if(isset($res['id']) && !empty($res['id'])){
            $id = explode(",",$res['id']);
            $where[] =['a.id',"not in",$id];
        }
        if(isset($res['data']['code']) && !empty($res['data']['code'])){
           $where[] = ['a.bar_code',"like","%{$res['data']['code']}%"]; 
        }
        if(isset($res['data']['parent']) && !empty($res['data']['parent'])){
            if(isset($res['data']['child']) && !empty($res['data']['child'])){
               $where[] = ['a.type',"=",intval($res['data']['child'])]; 
            }else{
                $where[] = ['i.pid',"=",intval($res['data']['parent'])]; 
            }
        }
        $where[] = ['a.item_type','eq',2];
        $where[] =["s.shop_id","=",intval($shop_id)];
        $where[] =["p.shop_id","=",intval($shop_id)];
        $where[] =["s.stock",">",0];
        $data = db::name("item")
        ->alias("a")
        ->where($where)
        ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status,s.stock,p.store_cose as price")    //,p.store_cose as price
        ->withAttr("p_type",function($value,$data){
            return db::name("item_category")->where("id",$data['pid'])->value("cname");
        })
        ->join("shop_item s","a.id=s.item_id")
        ->join("item_category i","a.type = i.id")
        ->join('purchase_price p','a.id=p.item_id','left')
        ->page($page,$limit)
        ->group('a.id')
        ->select();
        $count = db::name("item")
        ->alias("a")
        ->where($where)
        ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status")
        ->join("shop_item s","a.id=s.item_id")
        ->join("item_category i","a.type = i.id")
        ->join('purchase_price p','a.id=p.item_id','left')->group('a.id')->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }
    //出库
    public function out_shop(){
        $res = $this->request->post();
        $refund = new RefundModel();
        $data = $refund->out_shop($res);
        return $data;
    }
    public function del(){
        $res = $this->request->post();
        $id = intval($res['id']);
        $del['del'] =2;
        $del['del_time'] = time();
        $del['del_id'] = session("admin_user_auth")['uid'];
        $del['del_user'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
        $res = db::name("reject")->where("id",$id)->update($del);
        if($res){
            return json(['result'=>true,"msg"=>"删除成功","data"=>$data]);
        }else{

            return json(['result'=>false,"msg"=>"数据错误","data"=>""]);
        }
    }
    
}