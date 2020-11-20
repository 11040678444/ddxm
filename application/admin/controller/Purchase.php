<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\purchase\PurchaseModel;
/*use think\Db;*/

use app\stock\model\purchase\PurchaseItemModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
use app\admin\model\allot\AllotModel;

/**
 * 采购管理模块
 */
class Purchase extends Adminbase
{
    protected function initialize()
    {
        parent::initialize();
      /*  $this->AdminUser = new AdminUser;*/
    }

    /**
     * 商品管理模块列表
     */
    //采购单首页
    public function index()
    {
        $purchase = new PurchaseModel();
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $data["supplier"] = db::name("supplier")->where("del",0)->field("id,supplier_name as name")->select();
            $this->assign("data",$data);
            return $this->fetch();
        }else{
            $purchase = new PurchaseModel();
            $where = [];
            $field = $res['field'];
            if(isset($field['supplier'])  && !empty($field['supplier'])){
                $where[] = ['a.supplier_id',"=",$field['supplier']];
            }
            if(isset($field['status'])  && !empty($field['status'])){
                $where[] = ['a.status',"=",$field['status']];
            }
            if(isset($field['start_time'])  && !empty($field['start_time'])){
                $start_time = strtotime($field['start_time']);
                $where[] = ['a.create_time',">",$start_time];
            }
            if(isset($field['end_time'])  && !empty($field['end_time'])){
                $end_time = strtotime($field['end_time']) + 86399;
                $where[] = ['a.create_time',"<",$end_time];
            }
            if(isset($field['search'])  && !empty($field['search'])){
                $where[] = ['a.sn',"like","%{$field['search']}%"];
            }
            if(isset($field['item_name'])  && !empty($field['item_name'])){
                $where[] = ['b.item_name',"like","%{$field['item_name']}%"];
            }
            $data = db::name("purchase") ->alias('a')
                ->join('purchase_item b','a.id=b.purchase_id')
                ->where($where)
                ->order("a.create_time","desc")
                ->group('a.id')
                ->field('a.*')
                ->select();
            $data = $purchase->purchase_index($data);
            $count = Db::name("purchase")->alias('a')
                ->join('purchase_item b','a.id=b.purchase_id')
                ->where($where)->group('a.id')->count();
            return json(["code"=>0,"count"=>$count,"data"=>$data]);
        }
        
    }
    //新增采购单
    public function purchaseAdd(){
        $res = $this->request->post();
        if(!isset($res) || empty($res)){
            $data['shop'] = db::name("shop")->field("id,name")->select();
            $data['supplier'] = db::name("supplier")->where("del",0)->field("id,supplier_name")->select();
            $data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->field("id,cname")->select();
            $this->assign("data",$data);
            return $this->fetch();
        }else{
            $purchase = new PurchaseModel();
            $result = $purchase ->purchaseAdd($res);
            return $result;
        }
    }

    //编辑
    public function purchaseEdit()
    {
        if(request()->isAjax())
        {
            $data = request()->param('data');

            //判断状态
            $status = db('purchase')->where(['id'=>input('remarks')['id']])->value('status');

            $status != 1 ? return_error('非采购状态') : '';

            //处理数据
            $add_data = [];
            $up_data = [];
            foreach ($data as $key=>$val)
            {
                $val['s_num'] = $val['num'];
                if(isset($val['id']))
                {
                    empty($val['id']) ? return_error('参数错误') : '';
                    array_push($up_data,$val);
                }else{
                    empty($val['purchase_id']) ? return_error('参数错误') : '';
                    array_push($add_data,$val);
                }
            }

            $res = (new PurchaseItemModel())->changePurchaseItem($add_data,$up_data);

            db('purchase')->update(input('remarks'));
            !empty($res) ? return_succ([],'编辑成功') : return_error('编辑失败');

        }else{
            $id = input('id');

            empty($id) ? $this->error('参数错误') : '';

            $list = db('purchase_item')->alias('pi')
                    ->field('pi.*,p.remark')
                    ->join('purchase p','p.id = pi.purchase_id')
                    ->where(['purchase_id'=>$id])
                    ->select();

            $this->assign('data',$list);
            $this->assign('purchase_id',$id);
            return $this->fetch();
        }
    }

    //删除商品
    public function delItem()
    {
        try
        {
            if(\request()->isAjax())
            {
                $id = input('id/d');

                empty($id) ? return_error('参数错误') : '';

                $res = db('purchase_item')->where(['id'=>$id])->delete();

                !empty($res) ? return_succ($id,'删除成功') : return_error('删除失败');
            }
        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

    // 采购单商品选择列表
    public function purchase_item(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
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
        $where[] = ['a.item_type','in','2,3'];
        $data = db::name("item")
        ->alias("a")
        ->where($where)
        ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status")
        ->withAttr("p_type",function($value,$data){
            return db::name("item_category")->where("id",$data['pid'])->value("cname");
        })
        ->join("item_category i","a.type = i.id")
        ->page($page,$limit)
        ->select();
        $count = db::name("item")
        ->alias("a")
        ->where($where)
        ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status")
        ->join("item_category i","a.type = i.id")->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }
    //获取商品分类
    public function item_category(){
        $res = $this->request->post();
        $data = db::name("item_category")->where("pid",intval($res['id']))->where("status",1)->field("id,cname")->select();
        $count = count($data);
        if($data){
            return json(['result'=>true,"msg"=>"查询成功","data"=>$data,"count"=>$count]);
        }else{
            return json(['result'=>false,"msg"=>"系统繁忙","data"=>""]);
        }
    }
    //采购单入库页面加载数据
    public function purchase_storage(){
        $res = $this->request->post();
        $purchase = new purchaseModel();
        $data = $purchase->purchase_storage($res);
        if($data){
            return json(['result'=>true,"msg"=>"",'data'=>$data]);
        }else{
            return json(['result'=>false,"msg"=>"系统繁忙",'data'=>$data]);
        }
    }
    //获取商品入库列表
    public function purchase_storage_item(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $data = db::name("purchase_item")
        ->where("purchase_id",intval($res['id']))
        ->where("s_num","neq",0)
        ->withAttr("r_num",function($value,$data){
            return intval($data['num']) - intval($data['s_num']);
        })
        ->withAttr("l_num",function($value,$data){
            return $data['s_num'];
        })
        ->page($page,$limit)
        ->select();
        $count = db::name("purchase_item")->where("purchase_id",intval($res['id']))->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }
    // 商品入库
    public function storage_item(){
        $res = $this->request->post();
        $res['data'] = json_decode($res['data'],true);

        //判断当前门店是否存在对应商品的盘盈/盘亏单
        $Allot = (new AllotModel)->isStockException($res['shop_id'],array_column($res['data'],'item_id'));

        if(!isset($res) || empty($res)){
            return json(['result'=>false,"msg"=>"未上传数据","data"=>""]);
        }
        $status = Db::name("purchase")->where("id",intval($res['purchase_id']))->value("status");
        if($status==3){
            return json(["result"=>false,"msg"=>"错误操作","data"=>$status]);
        }else if($status ==4){
            return json(["result"=>false,"msg"=>"错误操作","data"=>$status]);
        }else if($status ==5){
            return json(["result"=>false,"msg"=>"错误操作","data"=>$status]);
        }else if($status ==6){
            return json(["result"=>false,"msg"=>"错误操作","data"=>$status]);
        }
        $purchase = new PurchaseModel();
        $data = $purchase->storage_item($res);
        return $data;
    }
    //入库记录
    public function record(){
        $res = $this->request->get();
        $id = intval($res['id']);
        $data = Db::name("purchase")->where("id",$id)
        ->withAttr("create_time",function($value,$data){
            if($data['create_time']){
                return date("Y-m-d H:i:s",$data['create_time']);

            }
            return "暂无数据";
        })
        ->find();
        $this->assign("data",$data);
        return $this->fetch();
    }
    //采购单信息
    public function item_table(){
        $res  = $this->request->get();
        $id   = intval($res['id']);
        $data = db::name("purchase_item")
        ->where("purchase_id",$id)
        ->withAttr("r_num",function($value,$data){
            return $data['num'] - $data['s_num'];
        })
        ->select();
        $count = db::name("purchase_item")->where("purchase_id",$id)->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }
    public function store_item(){
        $res = $this->request->get();
        $id  = intval($res['id']);
        $data = Db::name("purchase_store")
        ->alias("a")
        ->where("a.purchase_id",$id)
        ->field("a.id,a.pd_id,a.num,a.shop_id,a.item_name,a.item_id,a.operator,a.time,s.name as shop_name,p.num as c_num")
        ->withAttr("time",function($value,$data){
            return date("Y-m-d H:i:s",$data['time']);
        })
        ->join("shop s","a.shop_id = s.id")
        ->join("purchase_item p","a.pd_id = p.id")
        ->select();
        $count = Db::name("purchase_store")->where("purchase_id",$id)->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }
    // 关闭采购单
    public function purchase_close(){
        $res = $this->request->post();
        $purchase['status'] = 4;
        $result = Db::name("purchase")->where("id",intval($res['id']))->update($purchase);
        if($result){
            return json(['result'=>true,"msg"=>"修改成功","data"=>""]);
        }else{
            return json(['result'=>false,"msg"=>"系统错误!请稍后重试","data"=>""]);
        }
    }
    //删除采购单
    public function purchase_del(){
        $res = $this->request->post();
        $purchase['status'] = 6;
        $result = Db::name("purchase")->where("id",intval($res['id']))->update($purchase);
        if($result){
            return json(['result'=>true,"msg"=>"修改成功","data"=>""]);
        }else{
            return json(['result'=>false,"msg"=>"系统错误!请稍后重试","data"=>""]);
        }
    }
    public function refund_storage(){
        $res = $this->request->post();
        $id = $res['id'];
        $purchase = new PurchaseModel();
        $data = $purchase->refund_storage($id);
        return $data;
    }
}
